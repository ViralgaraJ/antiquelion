<?php
/*
Plugin Name: Antique Bids
Plugin URI: http://localhost/antique_site
Description: Live bidding and auction system for antiques.
Version: 1.1.0
Author: DeepMind Antigravity Pair Program
Short Name: antique_bids
*/

require_once osc_plugins_path() . 'antique_bids/ModelBids.php';

/**
 * "USD 12,500" style amount in the listing's own currency.
 */
function antique_bids_money($amount, $currency) {
    if (function_exists('antique_money')) {
        return antique_money($amount, $currency);
    }
    return trim($currency . ' ' . number_format((float)$amount, 2));
}

// Register Plugin Install / Uninstall callbacks
osc_register_plugin(osc_plugin_path(__FILE__), 'antique_bids_install');
osc_add_hook(osc_plugin_path(__FILE__) . '_uninstall', 'antique_bids_uninstall');

function antique_bids_install() {
    // Already populated by manual installation, keep hook template empty
}

function antique_bids_uninstall() {
    // Keep hook template empty
}

// Hook to run background checks on page initialization (lazy cron)
osc_add_hook('init', 'antique_bids_lazy_cron');

// Hook to display bidding card on top of item page sidebar
osc_add_hook('item_sidebar_top', 'antique_bids_sidebar_widget');

// Hooks to register core AJAX handlers
osc_add_hook('ajax_place_bid', 'antique_bids_ajax_place_bid');
osc_add_hook('ajax_cancel_winner_offer_second', 'antique_bids_ajax_cancel_winner_offer_second');

// Hooks to display/save auction settings when posting or editing a listing
osc_add_hook('item_form', 'antique_bids_item_form');
osc_add_hook('item_edit', 'antique_bids_item_edit');
osc_add_hook('posted_item', 'antique_bids_posted_item');
osc_add_hook('edited_item', 'antique_bids_edited_item');
osc_add_hook('delete_item', 'antique_bids_delete_item');

/**
 * Auction summary for listing cards, or null when the item is not a running/closed auction.
 * array(live, end (ISO), left ("2d 4h"), amount, label, bids). One query per request.
 */
function antique_bids_card_info($itemId) {
    static $rows = null;
    if ($rows === null) {
        $rows = array();
        $prefix = DB_TABLE_PREFIX;
        $res = ModelBids::newInstance()->dao->query(
            "SELECT a.fk_i_item_id, a.dt_end_date, a.d_starting_price, a.b_active,
                    MAX(b.d_amount) AS high, COUNT(b.pk_i_id) AS bids
             FROM `{$prefix}t_item_auctions` a
             LEFT JOIN `{$prefix}t_item_bids` b ON b.fk_i_item_id = a.fk_i_item_id AND b.s_status IN ('active', 'won')
             WHERE a.b_active = 1
             GROUP BY a.fk_i_item_id, a.dt_end_date, a.d_starting_price, a.b_active"
        );
        if ($res) {
            foreach ($res->result() as $r) {
                $rows[(int)$r['fk_i_item_id']] = $r;
            }
        }
    }

    $itemId = (int)$itemId;
    if (!isset($rows[$itemId])) {
        return null;
    }
    $r = $rows[$itemId];
    $end = strtotime($r['dt_end_date']);
    $live = $end > time();
    $bids = (int)$r['bids'];

    // A finished auction nobody bid on is just a listing again
    if (!$live && $bids == 0) {
        return null;
    }

    return array(
        'live' => $live,
        'end' => date('c', $end),
        'left' => $live ? antique_bids_time_left($end - time()) : 'Ended',
        'amount' => $bids > 0 ? (float)$r['high'] : (float)$r['d_starting_price'],
        'label' => $bids > 0 ? ($live ? 'Current bid' : 'Winning bid') : 'Starting bid',
        'bids' => $bids
    );
}

/**
 * "2d 4h", "5h 12m", "8m" for a number of seconds.
 */
function antique_bids_time_left($seconds) {
    $d = floor($seconds / 86400);
    $h = floor(($seconds % 86400) / 3600);
    $m = max(1, floor(($seconds % 3600) / 60));
    if ($d > 0) {
        return $d . 'd ' . $h . 'h';
    }
    if ($h > 0) {
        return $h . 'h ' . $m . 'm';
    }
    return $m . 'm';
}

/**
 * IDs of running auctions, soonest-ending first.
 */
function antique_bids_ending_soon($limit = 4) {
    $prefix = DB_TABLE_PREFIX;
    $res = ModelBids::newInstance()->dao->query(
        "SELECT a.fk_i_item_id FROM `{$prefix}t_item_auctions` a
         JOIN `{$prefix}t_item` i ON i.pk_i_id = a.fk_i_item_id
         WHERE a.b_active = 1 AND a.dt_end_date > NOW()
           AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0
         ORDER BY a.dt_end_date ASC LIMIT " . (int)$limit
    );
    $ids = array();
    if ($res) {
        foreach ($res->result() as $r) {
            $ids[] = (int)$r['fk_i_item_id'];
        }
    }
    return $ids;
}

// ?sAuction=1 on the search page lists only auctions that are still running
osc_add_hook('search_conditions', 'antique_bids_search_conditions');

function antique_bids_search_conditions($params = array()) {
    if (Params::getParam('sAuction') != '1') {
        return;
    }
    $search = Search::newInstance();
    $search->addJoinTable('antique_bids_auction', DB_TABLE_PREFIX . 't_item_auctions al_auction', DB_TABLE_PREFIX . 't_item.pk_i_id = al_auction.fk_i_item_id', 'INNER');
    $search->addConditions('al_auction.b_active = 1 AND al_auction.dt_end_date > NOW()');
}

/**
 * Renders the live bidding card widget
 */
/**
 * 0 = available, 1 = marked sold, 2 = reserved (the theme stores this per listing)
 */
function antique_bids_sold_state($itemId) {
    if (!function_exists('starter_item_extra')) {
        return 0;
    }
    $extra = starter_item_extra($itemId);
    return isset($extra['i_sold']) ? (int)$extra['i_sold'] : 0;
}

function antique_bids_sidebar_widget() {
    $itemId = osc_item_id();
    $userId = osc_logged_user_id();
    $isOwner = (osc_is_web_user_logged_in() && osc_item_user_id() == $userId);
    $soldState = antique_bids_sold_state($itemId);
    $model = ModelBids::newInstance();

    $auction = $model->getAuction($itemId);
    if (!$auction || $auction['b_active'] == 0) {
        return; // Bidding not active for this item
    }

    // declare the result straight away instead of waiting for the next cron run
    if (strtotime($auction['dt_end_date']) < time() && $auction['b_processed'] == 0) {
        antique_bids_process_expired_auctions();
        $auction = $model->getAuction($itemId);
    }
    $isExpired = (strtotime($auction['dt_end_date']) < time());

    $currency = osc_item_currency() ? osc_item_currency() : 'USD';
    $state = antique_bids_state($itemId);
    $reserve = (float)$auction['d_reserve_price'];
    $bidsHistory = $model->getBidHistory($itemId, 5);

    $wonBid = null;
    if ($isExpired) {
        $prefix = DB_TABLE_PREFIX;
        $won_res = $model->dao->query("SELECT b.*, u.s_name as user_name, u.s_email as user_email
            FROM `{$prefix}t_item_bids` b JOIN `{$prefix}t_user` u ON b.fk_i_user_id = u.pk_i_id
            WHERE b.fk_i_item_id = " . (int)$itemId . " AND b.s_status = 'won' LIMIT 1");
        if ($won_res && $won_res->numRows() > 0) {
            $wonBid = $won_res->row();
        }
    }
    $isWinner = ($wonBid && osc_is_web_user_logged_in() && $wonBid['fk_i_user_id'] == $userId);
    $reserveFailed = ($isExpired && !$wonBid && $state['hasBids'] && $reserve > 0 && $state['amount'] < $reserve);

    if ($isExpired && $wonBid) {
        $statLabel = 'Winning bid';
        $statAmount = $wonBid['d_amount'];
    } elseif ($state['hasBids']) {
        $statLabel = $isExpired ? 'Highest bid' : 'Current bid';
        $statAmount = $state['amount'];
    } else {
        $statLabel = 'Starting bid';
        $statAmount = $auction['d_starting_price'];
    }
    $canBid = (!$isExpired && !$soldState && osc_is_web_user_logged_in() && !$isOwner);
    ?>
    <div class="antique-bidding-card" id="al-auction" data-item="<?php echo (int)$itemId; ?>">
        <div class="bid-header">
            <h3>Auction</h3>
            <?php if ($isExpired): ?>
                <span class="live-indicator expired"><i class="fa fa-circle"></i> Closed</span>
            <?php else: ?>
                <span class="live-indicator"><i class="fa fa-circle"></i> Live</span>
            <?php endif; ?>
        </div>

        <div class="bid-stats">
            <div class="stat-col">
                <span class="label" id="live-bid-label"><?php echo $statLabel; ?></span>
                <span class="value gold-accent" id="live-highest-bid"><?php echo osc_esc_html(antique_bids_money($statAmount, $currency)); ?></span>
            </div>
            <div class="stat-col">
                <span class="label">Time left</span>
                <span class="value <?php echo $isExpired ? 'text-danger' : ''; ?>" id="countdown-timer" data-endtime="<?php echo osc_esc_html($state['end']); ?>">
                    <?php echo $isExpired ? 'Ended' : '&nbsp;'; ?>
                </span>
            </div>
        </div>

        <div class="al-bid-meta">
            <span id="al-bid-count"><?php echo $state['bids'] == 1 ? '1 bid' : $state['bids'] . ' bids'; ?></span>
            <?php if ($reserve > 0): ?>
                <span class="al-bid-reserve <?php echo $state['reserve'] == 'met' ? 'is-met' : ''; ?>" id="al-bid-reserve"><?php echo $state['reserve'] == 'met' ? 'Reserve met' : 'Reserve not met'; ?></span>
            <?php endif; ?>
        </div>

        <?php if (!$isExpired && osc_is_web_user_logged_in() && !$isOwner): ?>
            <div class="al-bid-status <?php echo $state['isLeader'] ? 'is-leading' : ($state['outbid'] ? 'is-outbid' : ''); ?>" id="al-bid-status"<?php echo ($state['isLeader'] || $state['outbid']) ? '' : ' hidden'; ?>>
                <?php if ($state['isLeader']): ?>
                    You are the highest bidder. Your maximum: <strong><?php echo osc_esc_html(antique_bids_money($state['myMax'], $currency)); ?></strong>
                <?php elseif ($state['outbid']): ?>
                    You have been outbid. Your maximum was <strong><?php echo osc_esc_html(antique_bids_money($state['myMax'], $currency)); ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isExpired): ?>
            <?php if ($isOwner): ?>
                <?php if ($wonBid): ?>
                    <div class="seller-console-card">
                        <h4>Auction result</h4>
                        <p class="console-desc">Bidding has closed. Contact the winner to arrange payment and delivery.</p>
                        <div class="console-winner-details">
                            <div class="winner-row"><strong>Name:</strong> <span><?php echo osc_esc_html($wonBid['user_name']); ?></span></div>
                            <div class="winner-row"><strong>Email:</strong> <span><?php echo osc_esc_html($wonBid['user_email']); ?></span></div>
                            <div class="winner-row"><strong>Amount:</strong> <span class="gold-accent font-700"><?php echo osc_esc_html(antique_bids_money($wonBid['d_amount'], $currency)); ?></span></div>
                        </div>
                        <button type="button" id="cancel-winner-btn" class="btn btn-secondary cancel-winner-btn" data-itemid="<?php echo $itemId; ?>">
                            Cancel winner and offer to the next bidder
                        </button>
                        <div id="seller-console-feedback"></div>
                    </div>
                <?php elseif ($reserveFailed): ?>
                    <div class="bid-expired-notice">The auction ended at <strong><?php echo osc_esc_html(antique_bids_money($state['amount'], $currency)); ?></strong>, below your reserve price, so there is no winner. You can run it again from Edit listing.</div>
                <?php else: ?>
                    <div class="bid-expired-notice">The auction ended with no bids. Try a lower starting bid and run it again from Edit listing.</div>
                <?php endif; ?>
            <?php elseif ($isWinner): ?>
                <div class="bid-winner-notice">
                    <h4><i class="fa fa-trophy"></i> You won this auction</h4>
                    <p class="winner-desc">Your bid of <strong><?php echo osc_esc_html(antique_bids_money($wonBid['d_amount'], $currency)); ?></strong> won. Please contact <strong><?php echo osc_esc_html(osc_item_contact_name()); ?></strong> within 24 hours to agree payment and delivery.</p>
                    <a href="#contact" class="btn btn-primary contact-seller-win-btn">Contact the seller</a>
                </div>
            <?php elseif ($wonBid): ?>
                <div class="bid-expired-notice">Bidding has closed. The winning bid was <strong><?php echo osc_esc_html(antique_bids_money($wonBid['d_amount'], $currency)); ?></strong>.</div>
            <?php elseif ($reserveFailed): ?>
                <div class="bid-expired-notice">Bidding has closed. The reserve price was not met, so the item was not sold.</div>
            <?php else: ?>
                <div class="bid-expired-notice">Bidding has closed for this item.</div>
            <?php endif; ?>
        <?php elseif ($soldState): ?>
            <div class="bid-expired-notice"><?php echo $soldState == 1 ? 'This item has been sold. Bidding is closed.' : 'This item is reserved. Bidding is paused.'; ?></div>
        <?php elseif (!osc_is_web_user_logged_in()): ?>
            <div class="bid-action-form guest">
                <a href="<?php echo osc_user_login_url(); ?>" class="btn btn-secondary login-to-bid">Sign in to bid</a>
            </div>
        <?php elseif ($isOwner): ?>
            <div class="bid-action-form owner">
                <span class="owner-notice">This is your listing, so you cannot bid on it.</span>
            </div>
        <?php else: ?>
            <form class="bid-action-form" id="al-bid-form" novalidate>
                <label class="al-bid-input-label" for="user-bid-amount">Your maximum bid</label>
                <div class="al-bid-row">
                    <div class="input-wrap">
                        <span class="currency-label"><?php echo osc_esc_html($currency); ?></span>
                        <input type="number" id="user-bid-amount" inputmode="decimal" min="<?php echo (float)$state['nextMin']; ?>" step="any" placeholder="<?php echo osc_esc_html(number_format($state['nextMin'])); ?> or more" autocomplete="off">
                    </div>
                    <button type="submit" id="place-bid-btn" class="btn btn-primary">Place bid</button>
                </div>
                <button type="button" class="al-bid-quick" id="al-bid-quick"<?php echo $state['isLeader'] ? ' hidden' : ''; ?>>Bid <span><?php echo osc_esc_html(antique_bids_money($state['nextMin'], $currency)); ?></span></button>
            </form>
            <div id="bid-feedback-msg" role="status" aria-live="polite"></div>
            <p class="al-bid-help">Enter the most you are willing to pay. We bid for you automatically, only as much as needed to keep you in the lead, and other bidders never see your maximum. A bid in the last 2 minutes extends the auction by 2 minutes.</p>
        <?php endif; ?>

        <div class="bid-history">
            <h4>Bid history</h4>
            <ul id="bid-history-list">
                <?php if (empty($bidsHistory)): ?>
                    <li class="no-bids-msg"><?php echo $isExpired ? 'No bids were placed.' : 'No bids yet. Be the first to bid.'; ?></li>
                <?php else: ?>
                    <?php foreach ($bidsHistory as $bid): ?>
                        <li>
                            <span class="bidder-name"><i class="fa <?php echo $bid['s_status'] == 'won' ? 'fa-trophy' : 'fa-user-circle'; ?>"></i> <?php echo osc_esc_html(antique_bids_mask_name($bid['user_name'], $bid['fk_i_user_id'])); ?></span>
                            <span class="bid-amt"><?php echo osc_esc_html(antique_bids_money($bid['d_amount'], $currency)); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <input type="hidden" id="bid-item-id" value="<?php echo $itemId; ?>">
        <input type="hidden" id="csrf-token" value="<?php echo osc_csrfguard_generate_token(); ?>">
    </div>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var card = document.getElementById('al-auction');
        if (!card) { return; }
        var itemId = card.getAttribute('data-item');
        var currency = <?php echo json_encode($currency); ?>;
        var expired = <?php echo $isExpired ? 'true' : 'false'; ?>;
        var canBid = <?php echo $canBid ? 'true' : 'false'; ?>;
        var nextMin = <?php echo json_encode((float)$state['nextMin']); ?>;
        var lastAmount = <?php echo json_encode((float)$statAmount); ?>;
        var lastBids = <?php echo (int)$state['bids']; ?>;
        var stateUrl = <?php echo json_encode(osc_base_url(true) . '?page=ajax&action=runhook&hook=get_highest_bid_val&itemId=' . (int)$itemId); ?>;
        var historyUrl = <?php echo json_encode(osc_base_url(true) . '?page=ajax&action=runhook&hook=get_bids_history&itemId=' . (int)$itemId); ?>;
        var bidUrl = <?php echo json_encode(osc_ajax_hook_url('place_bid')); ?>;
        var $ = function (id) { return document.getElementById(id); };

        function money(v) {
            var n = parseFloat(v), whole = Math.abs(n - Math.round(n)) < 0.005;
            return currency + ' ' + n.toLocaleString('en-US', {minimumFractionDigits: whole ? 0 : 2, maximumFractionDigits: whole ? 0 : 2});
        }
        function esc(t) {
            return String(t).replace(/[&<>"']/g, function (m) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m]; });
        }
        function feedback(ok, text) {
            var el = $('bid-feedback-msg');
            if (!el) { return; }
            el.innerHTML = '<span class="' + (ok ? 'text-success success-alert' : 'text-danger error-alert') + '">' + esc(text) + '</span>';
            el.style.display = 'block';
        }

        // countdown; the end moves when a late bid extends the auction
        var timer = $('countdown-timer');
        var endTime = new Date(timer.getAttribute('data-endtime')).getTime();
        function tick() {
            if (expired) { return; }
            var d = endTime - Date.now();
            if (d <= 0) {
                expired = true;
                timer.textContent = 'Ended';
                timer.classList.add('text-danger');
                var form = $('al-bid-form');
                if (form) { form.outerHTML = '<div class="bid-expired-notice">Bidding has closed. Reload the page to see the result.</div>'; }
                return;
            }
            var days = Math.floor(d / 86400000), h = Math.floor(d % 86400000 / 3600000), m = Math.floor(d % 3600000 / 60000), s = Math.floor(d % 60000 / 1000);
            timer.textContent = (days > 0 ? days + 'd ' : '') + h + 'h ' + m + 'm ' + s + 's';
            timer.classList.toggle('is-ending', d < 3600000);
        }
        tick();
        setInterval(tick, 1000);

        function renderHistory() {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', historyUrl, true);
            xhr.onload = function () {
                try {
                    var data = JSON.parse(xhr.responseText), html = '';
                    if (!data || !data.length) { return; }
                    for (var i = 0; i < data.length; i++) {
                        html += '<li><span class="bidder-name"><i class="fa ' + (data[i].won ? 'fa-trophy' : 'fa-user-circle') + '"></i> ' + esc(data[i].user_name) + '</span><span class="bid-amt">' + esc(money(data[i].d_amount)) + '</span></li>';
                    }
                    $('bid-history-list').innerHTML = html;
                } catch (e) {}
            };
            xhr.send();
        }

        function applyState(st) {
            if (!st || !st.success) { return; }
            var newEnd = new Date(st.end).getTime();
            if (newEnd && newEnd !== endTime) { endTime = newEnd; tick(); }
            if (st.hasBids) { $('live-bid-label').textContent = 'Current bid'; }
            $('live-highest-bid').textContent = money(st.amount);
            $('al-bid-count').textContent = st.bids == 1 ? '1 bid' : st.bids + ' bids';
            var r = $('al-bid-reserve');
            if (r) { r.textContent = st.reserve === 'met' ? 'Reserve met' : 'Reserve not met'; r.classList.toggle('is-met', st.reserve === 'met'); }
            var status = $('al-bid-status');
            if (status) {
                status.classList.toggle('is-leading', !!st.isLeader);
                status.classList.toggle('is-outbid', !!st.outbid);
                status.hidden = !(st.isLeader || st.outbid);
                if (st.isLeader) { status.innerHTML = 'You are the highest bidder. Your maximum: <strong>' + esc(money(st.myMax)) + '</strong>'; }
                else if (st.outbid) { status.innerHTML = 'You have been outbid. Your maximum was <strong>' + esc(money(st.myMax)) + '</strong>'; }
            }
            nextMin = parseFloat(st.nextMin);
            var input = $('user-bid-amount');
            if (input) { input.setAttribute('min', nextMin); input.setAttribute('placeholder', nextMin.toLocaleString('en-US') + ' or more'); }
            var quick = $('al-bid-quick');
            if (quick) { quick.querySelector('span').textContent = money(nextMin); quick.hidden = !!st.isLeader; }
            if (st.bids !== lastBids || parseFloat(st.amount) !== lastAmount) { renderHistory(); }
            lastBids = st.bids; lastAmount = parseFloat(st.amount);
        }

        // other people's bids appear without reloading
        setInterval(function () {
            if (expired || document.hidden) { return; }
            var xhr = new XMLHttpRequest();
            xhr.open('GET', stateUrl, true);
            xhr.onload = function () { try { applyState(JSON.parse(xhr.responseText)); } catch (e) {} };
            xhr.send();
        }, 8000);

        if (canBid) {
            var form = $('al-bid-form'), input = $('user-bid-amount'), btn = $('place-bid-btn');
            $('al-bid-quick').addEventListener('click', function () { input.value = nextMin; input.focus(); });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var amount = parseFloat(input.value);
                if (isNaN(amount) || amount <= 0) { feedback(false, 'Please enter your maximum bid.'); return; }
                if (amount < nextMin) { feedback(false, 'Your bid must be at least ' + money(nextMin) + '.'); return; }
                if (!window.confirm('Confirm your maximum bid of ' + money(amount) + '?\n\nIf you win, you are committing to buy this item.')) { return; }

                btn.disabled = true;
                btn.textContent = 'Placing...';
                var xhr = new XMLHttpRequest();
                xhr.open('POST', bidUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onload = function () {
                    btn.disabled = false;
                    btn.textContent = 'Place bid';
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if (res.success) {
                            feedback(res.leader, res.message);
                            input.value = '';
                            applyState(res);
                            renderHistory();
                        } else {
                            feedback(false, res.error);
                        }
                    } catch (err) { feedback(false, 'Something went wrong. Please reload the page and try again.'); }
                };
                xhr.onerror = function () { btn.disabled = false; btn.textContent = 'Place bid'; feedback(false, 'Connection problem. Please try again.'); };
                xhr.send('itemId=' + encodeURIComponent(itemId) + '&bidAmount=' + encodeURIComponent(amount) + '&octoken=' + encodeURIComponent($('csrf-token').value));
            });
        }

        var cancelBtn = $('cancel-winner-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                if (!window.confirm('Cancel the winning bidder and offer the item to the next highest bidder? This cannot be undone.')) { return; }
                var out = $('seller-console-feedback');
                cancelBtn.disabled = true;
                var xhr = new XMLHttpRequest();
                xhr.open('POST', <?php echo json_encode(osc_ajax_hook_url('cancel_winner_offer_second')); ?>, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onload = function () {
                    cancelBtn.disabled = false;
                    try {
                        var res = JSON.parse(xhr.responseText);
                        out.innerHTML = '<span class="' + (res.success ? 'text-success success-alert' : 'text-danger error-alert') + '">' + esc(res.success ? res.message : res.error) + '</span>';
                        out.style.display = 'block';
                        if (res.success) { setTimeout(function () { window.location.reload(); }, 1800); }
                    } catch (e) {}
                };
                xhr.send('itemId=' + encodeURIComponent(itemId) + '&octoken=' + encodeURIComponent($('csrf-token').value));
            });
        }
    });
    </script>
    <?php
}

/**
 * Handle bid submission AJAX action
 */
function antique_bids_ajax_place_bid() {
    ob_clean();
    header('Content-Type: application/json');

    // 1. Session Auth check
    if (!osc_is_web_user_logged_in()) {
        echo json_encode(array('success' => false, 'error' => 'You must log in to place a bid.'));
        exit;
    }

    // 2. CSRF token check
    $token = Params::getParam('octoken');
    if (!osc_csrfguard_validate_token($token)) {
        echo json_encode(array('success' => false, 'error' => 'CSRF security token expired. Please reload page.'));
        exit;
    }

    // 3. Sanitized Inputs
    $itemId = (int)Params::getParam('itemId');
    $bidAmount = (float)Params::getParam('bidAmount');
    $userId = osc_logged_user_id();
    $ipAddress = osc_get_ip();

    // 4. Owner bidding check
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) {
        echo json_encode(array('success' => false, 'error' => 'Item does not exist.'));
        exit;
    }

    if ($item['fk_i_user_id'] == $userId) {
        echo json_encode(array('success' => false, 'error' => 'You cannot bid on your own listing.'));
        exit;
    }

    if ($item['b_active'] != 1 || $item['b_enabled'] != 1 || $item['b_spam'] == 1) {
        echo json_encode(array('success' => false, 'error' => 'This listing is not available for bidding.'));
        exit;
    }

    $soldState = antique_bids_sold_state($itemId);
    if ($soldState) {
        echo json_encode(array('success' => false, 'error' => $soldState == 1 ? 'This item has been sold. Bidding is closed.' : 'This item is reserved. Bidding is paused.'));
        exit;
    }

    // 5. Model execution
    $res = ModelBids::newInstance()->placeBid($itemId, $userId, $bidAmount, $ipAddress, $item['fk_c_currency_code']);

    if (is_array($res)) {
        $currency = $item['fk_c_currency_code'];
        $isLeader = ($res['leader'] == $userId);
        if ($isLeader) {
            $message = 'You are the highest bidder. We will bid for you up to ' . antique_bids_money($bidAmount, $currency) . ' if someone bids against you.';
            if (!$res['reserve_met']) {
                $message .= ' The reserve price has not been met yet.';
            }
        } else {
            $message = 'You have been outbid: another bidder set a higher maximum. Try a higher amount.';
        }
        if ($res['extended']) {
            $message .= ' The auction has been extended by 2 minutes.';
        }
        if ($res['outbid'] && $res['outbid'] != $userId) {
            antique_bids_send_outbid_email($itemId, $res['outbid'], $res['price']);
        }
        echo json_encode(array_merge(antique_bids_state($itemId), array(
            'success' => true,
            'leader'  => $isLeader,
            'message' => $message
        )));
    } else {
        echo json_encode(array('success' => false, 'error' => $res));
    }
    exit;
}

/**
 * "S***u": bidder names are shown masked to everyone else, as on major auction sites.
 */
function antique_bids_mask_name($name, $userId) {
    if (osc_is_web_user_logged_in() && (int)$userId == (int)osc_logged_user_id()) {
        return 'You';
    }
    $name = trim((string)$name);
    if ($name === '') {
        return 'Bidder';
    }
    $first = function_exists('mb_substr') ? mb_substr($name, 0, 1) : substr($name, 0, 1);
    $last = function_exists('mb_substr') ? mb_substr($name, -1) : substr($name, -1);
    return $first . '***' . $last;
}

/**
 * Live state of an auction for the widget and its polling: current bid, bid count,
 * end time (moves when a late bid extends it), reserve status and the viewer's position.
 */
function antique_bids_state($itemId) {
    $model = ModelBids::newInstance();
    $auction = $model->getAuction($itemId);
    if (!$auction) {
        return array('success' => false);
    }
    $highest = $model->getHighestBid($itemId);
    $amount = $highest ? (float)$highest['d_amount'] : (float)$auction['d_starting_price'];
    $reserve = (float)$auction['d_reserve_price'];
    $uid = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
    $myMax = $uid ? $model->getProxyMax($itemId, $uid) : 0;

    return array(
        'success'    => true,
        'amount'     => $amount,
        'hasBids'    => (bool)$highest,
        'nextMin'    => $highest ? $amount + max(1, (float)$auction['d_min_increment']) : (float)$auction['d_starting_price'],
        'bids'       => $model->countBids($itemId),
        'end'        => date('c', strtotime($auction['dt_end_date'])),
        'reserve'    => $reserve > 0 ? ($highest && $amount >= $reserve ? 'met' : 'not_met') : 'none',
        'isLeader'   => $uid && $highest && (int)$highest['fk_i_user_id'] == $uid,
        'myMax'      => $myMax,
        'outbid'     => $uid && $myMax > 0 && !($highest && (int)$highest['fk_i_user_id'] == $uid)
    );
}

// Hook to pull bid history list
osc_add_hook('ajax_get_bids_history', 'antique_bids_ajax_history');
function antique_bids_ajax_history() {
    ob_clean();
    header('Content-Type: application/json');
    $itemId = (int)Params::getParam('itemId');
    $history = array();
    // Public endpoint: only expose what the widget displays (no IPs / user IDs)
    foreach (ModelBids::newInstance()->getBidHistory($itemId, 5) as $bid) {
        $history[] = array(
            'user_name' => antique_bids_mask_name($bid['user_name'], $bid['fk_i_user_id']),
            'd_amount'  => $bid['d_amount'],
            'won'       => $bid['s_status'] == 'won'
        );
    }
    echo json_encode($history);
    exit;
}

// Hook to pull highest bid dynamically
osc_add_hook('ajax_get_highest_bid_val', 'antique_bids_ajax_highest');
function antique_bids_ajax_highest() {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(antique_bids_state((int)Params::getParam('itemId')));
    exit;
}

/**
 * Render blank auction settings form on Publish page
 */
function antique_bids_item_form($catID = null) {
    antique_bids_render_form(null);
}

/**
 * Render pre-filled auction settings form on Edit page
 */
function antique_bids_item_edit($catID = null, $itemID = null) {
    $auction = ModelBids::newInstance()->getAuction($itemID);
    antique_bids_render_form($auction);
}

/**
 * Renders the custom auction fields card inside listing form
 */
function antique_bids_render_form($auction = null) {
    $enabled = ($auction && $auction['b_active'] == 1);
    $startingPrice = $auction ? (float)$auction['d_starting_price'] : '';
    $minIncrement = $auction ? (float)$auction['d_min_increment'] : 100;
    $reservePrice = ($auction && (float)$auction['d_reserve_price'] > 0) ? (float)$auction['d_reserve_price'] : '';
    $locked = ($auction && ModelBids::newInstance()->hasBids($auction['fk_i_item_id']));
    
    $duration = 7;
    if ($auction) {
        $diff = strtotime($auction['dt_end_date']) - time();
        if ($diff > 0) {
            $duration = round($diff / (24 * 60 * 60));
            if ($duration < 1) $duration = 1;
        }
    }
    ?>
    <fieldset class="hook-block i-shadow round3 antique-bids-post-card">
        <div class="header-section">
            <h3>Sell by auction</h3>
        </div>
        <div class="form-row checkbox-row">
            <label class="toggle-switch">
                <input type="checkbox" name="bids_enabled" id="bids_enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
                <span class="slider-toggle"></span>
            </label>
            <label class="toggle-label" for="bids_enabled">Run this listing as a timed auction</label>
        </div>
        
        <div id="bids-details-panel" class="bids-slide-panel" style="<?php echo $enabled ? 'display: flex;' : 'display: none;'; ?>">
            <?php if ($locked) { ?>
                <p class="bids-locked-note">Bidding has started, so the auction terms can no longer be changed.</p>
            <?php } ?>
            <div class="form-row">
                <label for="bids_starting_price">Starting bid</label>
                <div class="input-wrap-post">
                    <input type="number" name="bids_starting_price" id="bids_starting_price"<?php echo $locked ? ' disabled' : ''; ?> value="<?php echo osc_esc_html($startingPrice); ?>" placeholder="Same as your price">
                </div>
                <span class="help-text">In the same currency as your listing price. Leave empty to start at your price.</span>
            </div>
            
            <div class="form-row">
                <label for="bids_reserve_price">Reserve price <span class="optional">(optional)</span></label>
                <div class="input-wrap-post">
                    <input type="number" name="bids_reserve_price" id="bids_reserve_price"<?php echo $locked ? ' disabled' : ''; ?> value="<?php echo osc_esc_html($reservePrice); ?>" min="0" placeholder="No reserve">
                </div>
                <span class="help-text">The lowest price you will accept. If bidding ends below it, the item is not sold. Bidders only see whether it has been met, never the amount.</span>
            </div>

            <div class="form-row">
                <label for="bids_min_increment">Minimum bid increase</label>
                <div class="input-wrap-post">
                    <input type="number" name="bids_min_increment" id="bids_min_increment"<?php echo $locked ? ' disabled' : ''; ?> value="<?php echo osc_esc_html($minIncrement); ?>" min="1">
                </div>
                <span class="help-text">Each new bid must beat the current bid by at least this amount.</span>
            </div>
            
            <div class="form-row">
                <label for="bids_duration">Auction length</label>
                <div class="input-wrap-post select-wrap">
                    <select name="bids_duration" id="bids_duration"<?php echo $locked ? ' disabled' : ''; ?>>
                        <option value="3" <?php echo $duration == 3 ? 'selected' : ''; ?>>3 days</option>
                        <option value="5" <?php echo $duration == 5 ? 'selected' : ''; ?>>5 days</option>
                        <option value="7" <?php echo $duration == 7 ? 'selected' : ''; ?>>7 days (recommended)</option>
                        <option value="10" <?php echo $duration == 10 ? 'selected' : ''; ?>>10 days</option>
                        <option value="14" <?php echo $duration == 14 ? 'selected' : ''; ?>>14 days</option>
                        <option value="30" <?php echo $duration == 30 ? 'selected' : ''; ?>>30 days</option>
                    </select>
                </div>
                <span class="help-text"><?php echo $locked ? 'Ends ' . osc_esc_html(date('j M Y, H:i', strtotime($auction['dt_end_date']))) . ' (server time).' : 'Counted from when the listing is published.'; ?></span>
            </div>
        </div>
    </fieldset>

    <script type="text/javascript">
    (function() {
        document.addEventListener('change', function(e) {
            if (e.target && e.target.id === 'bids_enabled') {
                var panel = document.getElementById('bids-details-panel');
                if (panel) {
                    if (e.target.checked) {
                        panel.style.display = 'flex';
                        setTimeout(function() {
                            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 100);
                    } else {
                        panel.style.display = 'none';
                    }
                }
            }
        });
    })();
    </script>
    <?php
}

/**
 * Save auction parameters for a newly posted item
 */
function antique_bids_posted_item($item) {
    $itemId = $item['pk_i_id'];
    $enabled = (Params::getParam('bids_enabled') == '1');
    
    if ($enabled) {
        $startingPrice = Params::getParam('bids_starting_price');
        if ($startingPrice === '' || $startingPrice === null) {
            $startingPrice = Params::getParam('price');
            if ($startingPrice === '' || $startingPrice === null || $startingPrice <= 0) {
                $startingPrice = 0;
            }
        }
        
        $minIncrement = Params::getParam('bids_min_increment');
        if ($minIncrement === '' || $minIncrement === null || $minIncrement <= 0) {
            $minIncrement = 100;
        }
        
        $duration = Params::getParam('bids_duration');
        if ($duration === '' || $duration === null || $duration <= 0) {
            $duration = 7;
        }
        
        ModelBids::newInstance()->saveAuction($itemId, $startingPrice, $minIncrement, $duration, true, (float)Params::getParam('bids_reserve_price'));
    }
}

/**
 * Update or disable auction parameters for an edited item
 */
function antique_bids_edited_item($item) {
    $itemId = $item['pk_i_id'];
    $enabled = (Params::getParam('bids_enabled') == '1');
    
    if ($enabled) {
        $startingPrice = Params::getParam('bids_starting_price');
        if ($startingPrice === '' || $startingPrice === null) {
            $startingPrice = Params::getParam('price');
            if ($startingPrice === '' || $startingPrice === null || $startingPrice <= 0) {
                $startingPrice = 0;
            }
        }
        
        $minIncrement = Params::getParam('bids_min_increment');
        if ($minIncrement === '' || $minIncrement === null || $minIncrement <= 0) {
            $minIncrement = 100;
        }
        
        $duration = Params::getParam('bids_duration');
        if ($duration === '' || $duration === null || $duration <= 0) {
            $duration = 7;
        }
        
        ModelBids::newInstance()->saveAuction($itemId, $startingPrice, $minIncrement, $duration, true, (float)Params::getParam('bids_reserve_price'));
    } else {
        // Disable listing auction active state
        $auction = ModelBids::newInstance()->getAuction($itemId);
        if ($auction) {
            ModelBids::newInstance()->saveAuction($itemId, $auction['d_starting_price'], $auction['d_min_increment'], 7, false, $auction['d_reserve_price']);
        }
    }
}

/**
 * Clean up auction and bids when item is deleted
 */
function antique_bids_delete_item($itemID) {
    ModelBids::newInstance()->deleteAuction($itemID);
}

/**
 * Lazy Cron to automatically declare winners when auction timer finishes
 */
function antique_bids_lazy_cron() {
    ModelBids::newInstance()->ensureSchema();

    $last_cron = osc_get_preference('antique_bids_last_cron', 'antique_bids');
    if ($last_cron == '') {
        osc_set_preference('antique_bids_last_cron', time(), 'antique_bids', 'INTEGER');
        $last_cron = time();
    }

    // every minute: winners are declared and emailed close to the real end time
    if (time() - $last_cron > 60) {
        osc_set_preference('antique_bids_last_cron', time(), 'antique_bids', 'INTEGER');
        antique_bids_process_expired_auctions();
    }
}

/**
 * Process expired, unprocessed auctions and declare winners
 */
function antique_bids_process_expired_auctions() {
    $model = ModelBids::newInstance();
    $expired = $model->getExpiredUnprocessedAuctions();
    if (empty($expired)) {
        return;
    }

    foreach ($expired as $auction) {
        $itemId = $auction['fk_i_item_id'];
        // claim the auction first so two requests never email twice
        if (!$model->markAuctionProcessed($itemId)) {
            continue;
        }

        $highest = $model->getHighestBid($itemId);
        $reserve = (float)$auction['d_reserve_price'];
        if ($highest && ($reserve <= 0 || (float)$highest['d_amount'] >= $reserve)) {
            $model->setBidStatus($highest['pk_i_id'], 'won');
            antique_bids_send_winner_emails($itemId, $highest);
            antique_bids_send_lost_emails($itemId, (int)$highest['fk_i_user_id']);
        } elseif ($highest) {
            antique_bids_send_reserve_not_met_emails($itemId, $highest, $reserve);
        } else {
            antique_bids_send_no_bids_email($itemId);
        }
    }
}

/**
 * Sends one branded HTML email. $paragraphs are HTML strings (escape user data first).
 */
function antique_bids_mail($to, $toName, $subject, $heading, $paragraphs, $ctaUrl = '', $ctaLabel = '') {
    if (trim((string)$to) === '') {
        return false;
    }
    $site = osc_esc_html(osc_page_title());
    $body = '<div style="background:#F7F4EE;padding:28px 12px;font-family:Arial,Helvetica,sans-serif;color:#14213D;">'
          . '<div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E6DFD1;border-radius:6px;overflow:hidden;">'
          . '<div style="padding:18px 28px;border-bottom:3px solid #A67C2E;font-family:Georgia,serif;font-size:22px;color:#14213D;">' . $site . '</div>'
          . '<div style="padding:26px 28px;font-size:15px;line-height:1.6;">'
          . '<h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:normal;color:#14213D;">' . osc_esc_html($heading) . '</h1>';
    $body .= '<p style="margin:0 0 14px;">' . sprintf('Hello %s,', osc_esc_html($toName)) . '</p>';
    foreach ($paragraphs as $para) {
        $body .= '<p style="margin:0 0 14px;">' . $para . '</p>';
    }
    if ($ctaUrl !== '') {
        $body .= '<p style="margin:22px 0 6px;"><a href="' . osc_esc_html($ctaUrl) . '" style="display:inline-block;padding:12px 24px;background:#A67C2E;color:#FFFFFF;text-decoration:none;border-radius:4px;font-weight:bold;">' . osc_esc_html($ctaLabel) . '</a></p>';
    }
    $body .= '</div><div style="padding:16px 28px;background:#FBF9F4;border-top:1px solid #EFE9DD;font-size:12px;color:#6B7280;">'
           . sprintf('You are receiving this email because of your activity on %s.', '<a href="' . osc_base_url() . '" style="color:#8A6420;">' . $site . '</a>')
           . '</div></div></div>';

    return @osc_sendMail(array(
        'from'     => _osc_from_email_aux(),
        'to'       => $to,
        'to_name'  => $toName,
        'subject'  => $subject,
        'body'     => $body,
        'alt_body' => trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode(strip_tags(str_replace(array('</p>', '</h1>', '</div>', '<br/>', '<br>'), "\n", $body)), ENT_QUOTES, 'UTF-8')))
    ));
}

/**
 * "You have been outbid" email to the bidder who just lost the lead.
 */
function antique_bids_send_outbid_email($itemId, $userId, $price) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    $user = User::newInstance()->findByPrimaryKey($userId);
    if (!$item || !$user) return;

    $title = osc_esc_html($item['s_title']);
    antique_bids_mail(
        $user['s_email'], $user['s_name'],
        'You have been outbid on ' . $item['s_title'],
        'You have been outbid',
        array(
            'Another bidder has placed a higher maximum bid on <strong>' . $title . '</strong>.',
            'Current bid: <strong>' . osc_esc_html(antique_bids_money($price, $item['fk_c_currency_code'])) . '</strong>. The auction is still running, so you can bid again.'
        ),
        osc_item_url_from_item($item), 'Bid again'
    );
}

/**
 * Send winning bidder email notifications
 */
function antique_bids_send_winner_emails($itemId, $highest) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    $winner = User::newInstance()->findByPrimaryKey($highest['fk_i_user_id']);
    if (!$winner) return;

    $title = osc_esc_html($item['s_title']);
    $amount = osc_esc_html(antique_bids_money($highest['d_amount'], $item['fk_c_currency_code']));
    $itemUrl = osc_item_url_from_item($item);

    antique_bids_mail(
        $winner['s_email'], $winner['s_name'],
        'You won the auction for ' . $item['s_title'],
        'Congratulations, you won!',
        array(
            'Your bid of <strong>' . $amount . '</strong> won the auction for <strong>' . $title . '</strong>.',
            'Please contact the seller, <strong>' . osc_esc_html($item['s_contact_name']) . '</strong>, within 24 hours to agree payment and delivery. Read our safety guide before you pay.'
        ),
        $itemUrl, 'Contact the seller'
    );

    antique_bids_mail(
        $item['s_contact_email'], $item['s_contact_name'],
        'Your auction has ended: ' . $item['s_title'],
        'Your item has a winning bidder',
        array(
            'The auction for <strong>' . $title . '</strong> has ended with a winning bid of <strong>' . $amount . '</strong>.',
            'Winner: <strong>' . osc_esc_html($winner['s_name']) . '</strong> (' . osc_esc_html($winner['s_email']) . ').',
            'Please contact the winner to arrange payment and delivery. If they do not respond within 24 hours, you can cancel their bid and offer the item to the next highest bidder from your listing page.'
        ),
        $itemUrl, 'View your listing'
    );
}

/**
 * "The auction has ended" email to every bidder who did not win.
 */
function antique_bids_send_lost_emails($itemId, $winnerId) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    foreach (ModelBids::newInstance()->getBidderIds($itemId) as $uid) {
        if ($uid == $winnerId || $uid == (int)$item['fk_i_user_id']) continue;
        $user = User::newInstance()->findByPrimaryKey($uid);
        if (!$user) continue;
        antique_bids_mail(
            $user['s_email'], $user['s_name'],
            'Auction ended: ' . $item['s_title'],
            'This auction has ended',
            array(
                'The auction for <strong>' . osc_esc_html($item['s_title']) . '</strong> has ended and another bidder won.',
                'Thank you for bidding. New antiques are listed every day.'
            ),
            osc_search_url(array('page' => 'search', 'sAuction' => 1)), 'See live auctions'
        );
    }
}

/**
 * The auction ended below the reserve: no sale. Tells the seller and the highest bidder.
 */
function antique_bids_send_reserve_not_met_emails($itemId, $highest, $reserve) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    $title = osc_esc_html($item['s_title']);
    $amount = osc_esc_html(antique_bids_money($highest['d_amount'], $item['fk_c_currency_code']));

    antique_bids_mail(
        $item['s_contact_email'], $item['s_contact_name'],
        'Auction ended below your reserve: ' . $item['s_title'],
        'Your reserve price was not met',
        array(
            'The auction for <strong>' . $title . '</strong> has ended. The highest bid was <strong>' . $amount . '</strong>, below your reserve of <strong>' . osc_esc_html(antique_bids_money($reserve, $item['fk_c_currency_code'])) . '</strong>, so there is no winner.',
            'You can contact the highest bidder through the listing if you would like to accept their offer, or run the auction again from Edit listing.'
        ),
        osc_item_edit_url('', $itemId), 'Edit listing'
    );

    $bidder = User::newInstance()->findByPrimaryKey($highest['fk_i_user_id']);
    if ($bidder) {
        antique_bids_mail(
            $bidder['s_email'], $bidder['s_name'],
            'Auction ended: ' . $item['s_title'],
            'The reserve price was not met',
            array(
                'The auction for <strong>' . $title . '</strong> has ended. Your bid of <strong>' . $amount . '</strong> was the highest, but it did not reach the seller\'s reserve price, so the item was not sold.',
                'The seller may still contact you, or you can ask them a question from the listing.'
            ),
            osc_item_url_from_item($item), 'View listing'
        );
    }
}

/**
 * Send seller no bids notification
 */
function antique_bids_send_no_bids_email($itemId) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    antique_bids_mail(
        $item['s_contact_email'], $item['s_contact_name'],
        'Auction ended with no bids: ' . $item['s_title'],
        'Your auction ended with no bids',
        array(
            'The auction for <strong>' . osc_esc_html($item['s_title']) . '</strong> has ended without any bids.',
            'Try a lower starting bid or a longer auction. You can run it again from Edit listing.'
        ),
        osc_item_edit_url('', $itemId), 'Edit listing'
    );
}

/**
 * AJAX handler for the seller to cancel the current non-paying winner and offer to 2nd highest bidder
 */
function antique_bids_ajax_cancel_winner_offer_second() {
    ob_clean();
    header('Content-Type: application/json');

    if (!osc_is_web_user_logged_in()) {
        echo json_encode(array('success' => false, 'error' => 'You must log in to manage auctions.'));
        exit;
    }

    $token = Params::getParam('octoken');
    if (!osc_csrfguard_validate_token($token)) {
        echo json_encode(array('success' => false, 'error' => 'Your session expired. Please reload the page.'));
        exit;
    }

    $itemId = (int)Params::getParam('itemId');
    $userId = osc_logged_user_id();

    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) {
        echo json_encode(array('success' => false, 'error' => 'Item does not exist.'));
        exit;
    }

    if ($item['fk_i_user_id'] != $userId) {
        echo json_encode(array('success' => false, 'error' => 'You do not have permission to manage this auction.'));
        exit;
    }

    $model = ModelBids::newInstance();
    $db = $model->dao;
    $prefix = DB_TABLE_PREFIX;
    $won_res = $db->query("SELECT * FROM `{$prefix}t_item_bids` WHERE fk_i_item_id = " . (int)$itemId . " AND s_status = 'won' LIMIT 1");
    if (!$won_res || $won_res->numRows() == 0) {
        echo json_encode(array('success' => false, 'error' => 'No active winning bid found to cancel.'));
        exit;
    }
    $currentWinnerBid = $won_res->row();

    // withdraw all of the cancelled winner's bids (with proxy bidding they can hold several)
    $db->query("UPDATE `{$prefix}t_item_bids` SET s_status = 'withdrawn' WHERE fk_i_item_id = " . (int)$itemId . " AND fk_i_user_id = " . (int)$currentWinnerBid['fk_i_user_id'] . " AND s_status IN ('active', 'won')");

    // the next highest bid from someone else
    $next_bids = $model->getBids($itemId, 1);
    $newWinnerBid = !empty($next_bids) ? $next_bids[0] : null;
    $auction = $model->getAuction($itemId);
    if ($newWinnerBid && $auction && (float)$auction['d_reserve_price'] > 0 && (float)$newWinnerBid['d_amount'] < (float)$auction['d_reserve_price']) {
        $newWinnerBid = null;   // below the reserve: not a sale
    }

    if ($newWinnerBid) {
        $model->setBidStatus($newWinnerBid['pk_i_id'], 'won');
        antique_bids_send_second_chance_emails($itemId, $currentWinnerBid, $newWinnerBid);

        echo json_encode(array(
            'success' => true,
            'message' => 'Winner cancelled. The item has been offered to the next highest bidder, ' . osc_esc_html($newWinnerBid['user_name']) . '.',
            'hasNewWinner' => true
        ));
    } else {
        antique_bids_send_cancellation_no_bidders_emails($itemId, $currentWinnerBid);

        echo json_encode(array(
            'success' => true,
            'message' => 'Winner cancelled. There are no other eligible bids on this item.',
            'hasNewWinner' => false
        ));
    }
    exit;
}

/**
 * Send second chance notifications to old winner, new winner, and seller
 */
function antique_bids_send_second_chance_emails($itemId, $oldBid, $newBid) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    $cur = $item['fk_c_currency_code'];
    $title = osc_esc_html($item['s_title']);
    $itemUrl = osc_item_url_from_item($item);

    $oldWinner = User::newInstance()->findByPrimaryKey($oldBid['fk_i_user_id']);
    if ($oldWinner) {
        antique_bids_mail(
            $oldWinner['s_email'], $oldWinner['s_name'],
            'Your winning bid for ' . $item['s_title'] . ' was cancelled',
            'Your winning bid was cancelled',
            array('The seller cancelled your winning bid of <strong>' . osc_esc_html(antique_bids_money($oldBid['d_amount'], $cur)) . '</strong> for <strong>' . $title . '</strong> because the sale was not completed within 24 hours.')
        );
    }

    $newWinner = User::newInstance()->findByPrimaryKey($newBid['fk_i_user_id']);
    if ($newWinner) {
        antique_bids_mail(
            $newWinner['s_email'], $newWinner['s_name'],
            'Second chance offer: ' . $item['s_title'],
            'The item is now offered to you',
            array(
                'The winning bidder for <strong>' . $title . '</strong> did not complete the sale, so the seller is offering it to you as the next highest bidder.',
                'Your bid: <strong>' . osc_esc_html(antique_bids_money($newBid['d_amount'], $cur)) . '</strong>. Please contact the seller within 24 hours to agree payment and delivery.'
            ),
            $itemUrl, 'Contact the seller'
        );
    }

    antique_bids_mail(
        $item['s_contact_email'], $item['s_contact_name'],
        'Offered to the next bidder: ' . $item['s_title'],
        'Offered to the next highest bidder',
        array(
            'You cancelled the winning bid of <strong>' . osc_esc_html(antique_bids_money($oldBid['d_amount'], $cur)) . '</strong> on <strong>' . $title . '</strong>.',
            'The item is now offered to <strong>' . osc_esc_html($newBid['user_name']) . '</strong>' . ($newWinner ? ' (' . osc_esc_html($newWinner['s_email']) . ')' : '') . ' at their bid of <strong>' . osc_esc_html(antique_bids_money($newBid['d_amount'], $cur)) . '</strong>.'
        ),
        $itemUrl, 'View your listing'
    );
}

/**
 * Send cancellation email when no other bidders exist
 */
function antique_bids_send_cancellation_no_bidders_emails($itemId, $oldBid) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    $cur = $item['fk_c_currency_code'];
    $title = osc_esc_html($item['s_title']);

    $oldWinner = User::newInstance()->findByPrimaryKey($oldBid['fk_i_user_id']);
    if ($oldWinner) {
        antique_bids_mail(
            $oldWinner['s_email'], $oldWinner['s_name'],
            'Your winning bid for ' . $item['s_title'] . ' was cancelled',
            'Your winning bid was cancelled',
            array('The seller cancelled your winning bid of <strong>' . osc_esc_html(antique_bids_money($oldBid['d_amount'], $cur)) . '</strong> for <strong>' . $title . '</strong> because the sale was not completed.')
        );
    }

    antique_bids_mail(
        $item['s_contact_email'], $item['s_contact_name'],
        'Winner cancelled: ' . $item['s_title'],
        'Winner cancelled',
        array(
            'You cancelled the winning bid of <strong>' . osc_esc_html(antique_bids_money($oldBid['d_amount'], $cur)) . '</strong> on <strong>' . $title . '</strong>.',
            'There are no other eligible bids. You can run the auction again from Edit listing.'
        ),
        osc_item_edit_url('', $itemId), 'Edit listing'
    );
}
