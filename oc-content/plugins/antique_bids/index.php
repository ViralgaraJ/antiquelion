<?php
/*
Plugin Name: Antique Bids
Plugin URI: http://localhost/antique_site
Description: Live bidding and auction system for antiques.
Version: 1.0.0
Author: DeepMind Antigravity Pair Program
Short Name: antique_bids
*/

require_once osc_plugins_path() . 'antique_bids/ModelBids.php';

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
 * Renders the live bidding card widget
 */
function antique_bids_sidebar_widget() {
    $itemId = osc_item_id();
    $userId = osc_logged_user_id();
    $isOwner = (osc_is_web_user_logged_in() && osc_item_user_id() == $userId);
    
    // Fetch auction details
    $auction = ModelBids::newInstance()->getAuction($itemId);
    if (!$auction || $auction['b_active'] == 0) {
        return; // Bidding not active for this item
    }

    $endTime = $auction['dt_end_date'];
    $isExpired = (strtotime($endTime) < time());

    // Instant expired auction check at runtime to avoid cron delays
    if ($isExpired && $auction['b_processed'] == 0) {
        antique_bids_process_expired_auctions();
        $auction = ModelBids::newInstance()->getAuction($itemId); // Re-fetch
    }

    // Fetch highest active bid
    $highestBidRow = ModelBids::newInstance()->getHighestBid($itemId);
    $currentHighest = $highestBidRow ? $highestBidRow['d_amount'] : $auction['d_starting_price'];
    $minNextBid = $currentHighest + $auction['d_min_increment'];

    // Fetch recent bid history
    $bidsHistory = ModelBids::newInstance()->getBids($itemId, 5);

    // Fetch winning bid if expired
    $wonBid = null;
    if ($isExpired) {
        $db = ModelBids::newInstance()->dao;
        $prefix = DB_TABLE_PREFIX;
        $sql_won = "SELECT b.*, u.s_name as user_name, u.s_email as user_email 
                    FROM `{$prefix}t_item_bids` b 
                    JOIN `{$prefix}t_user` u ON b.fk_i_user_id = u.pk_i_id 
                    WHERE b.fk_i_item_id = " . (int)$itemId . " AND b.s_status = 'won' 
                    LIMIT 1";
        $won_res = $db->query($sql_won);
        if ($won_res && $won_res->numRows() > 0) {
            $wonBid = $won_res->row();
        }
    }
    
    $isWinner = ($wonBid && osc_is_web_user_logged_in() && $wonBid['fk_i_user_id'] == $userId);
    ?>
    <div class="antique-bidding-card">
        <div class="bid-header">
            <h3>Live Bidding Box</h3>
            <?php if ($isExpired): ?>
                <span class="live-indicator expired"><i class="fa fa-circle"></i> Closed</span>
            <?php else: ?>
                <span class="live-indicator"><i class="fa fa-circle"></i> Live Auction</span>
            <?php endif; ?>
        </div>
        
        <div class="bid-stats">
            <div class="stat-col">
                <span class="label">Current Highest Bid</span>
                <span class="value gold-accent" id="live-highest-bid">LKR <?php echo number_format($currentHighest, 2); ?></span>
            </div>
            <div class="stat-col">
                <span class="label">Time Remaining</span>
                <span class="value <?php echo $isExpired ? 'text-danger' : ''; ?>" id="countdown-timer" data-endtime="<?php echo $endTime; ?>">
                    <?php echo $isExpired ? 'Ended' : 'Calculating...'; ?>
                </span>
            </div>
        </div>

        <?php if ($isExpired): ?>
            <?php if ($isOwner): ?>
                <?php if ($wonBid): ?>
                    <div class="seller-console-card">
                        <h4><i class="fa fa-cogs"></i> Seller Admin Console</h4>
                        <p class="console-desc">Bidding closed. The winning bid details are:</p>
                        <div class="console-winner-details">
                            <div class="winner-row"><strong>Name:</strong> <span><?php echo osc_esc_html($wonBid['user_name']); ?></span></div>
                            <div class="winner-row"><strong>Email:</strong> <span><?php echo osc_esc_html($wonBid['user_email']); ?></span></div>
                            <div class="winner-row"><strong>Amount:</strong> <span class="gold-accent font-700">LKR <?php echo number_format($wonBid['d_amount'], 2); ?></span></div>
                            <div class="winner-row"><strong>Status:</strong> <span class="status-won font-700">Winner Declared</span></div>
                        </div>
                        <button type="button" id="cancel-winner-btn" class="btn btn-secondary cancel-winner-btn" data-itemid="<?php echo $itemId; ?>">
                            <i class="fa fa-times-circle"></i> Cancel Winner & Offer to 2nd Bidder
                        </button>
                        <div id="seller-console-feedback"></div>
                    </div>
                <?php else: ?>
                    <div class="bid-expired-notice">
                        <i class="fa fa-gavel"></i> Bidding has closed with no bids placed.
                    </div>
                <?php endif; ?>
            <?php elseif ($isWinner): ?>
                <div class="bid-winner-notice">
                    <h4><i class="fa fa-trophy"></i> You won this auction!</h4>
                    <p class="winner-desc">Congratulations! Your bid of <strong>LKR <?php echo number_format($wonBid['d_amount'], 2); ?></strong> was the winning bid.</p>
                    <p class="winner-desc">Please contact the seller <strong><?php echo osc_esc_html(osc_item_contact_name()); ?></strong> to complete the transaction.</p>
                    <a href="#contact" class="btn btn-primary contact-seller-win-btn">Contact Seller Now</a>
                </div>
            <?php else: ?>
                <?php if ($wonBid): ?>
                    <div class="bid-expired-notice">
                        <i class="fa fa-gavel"></i> Bidding has closed. The winning bid was <strong>LKR <?php echo number_format($wonBid['d_amount'], 2); ?></strong>.
                    </div>
                <?php else: ?>
                    <div class="bid-expired-notice">
                        <i class="fa fa-gavel"></i> Bidding has closed for this antique.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif (!osc_is_web_user_logged_in()): ?>
            <div class="bid-action-form guest">
                <a href="<?php echo osc_user_login_url(); ?>" class="btn btn-secondary login-to-bid">
                    <i class="fa fa-sign-in"></i> Log in to place a bid
                </a>
            </div>
        <?php elseif ($isOwner): ?>
            <div class="bid-action-form owner">
                <span class="owner-notice">
                    <i class="fa fa-info-circle"></i> You cannot bid on your own antique.
                </span>
            </div>
        <?php else: ?>
            <div class="bid-action-form">
                <div class="input-wrap">
                    <span class="currency-label">LKR</span>
                    <input type="number" id="user-bid-amount" placeholder="Min. <?php echo number_format($minNextBid, 2); ?>" min="<?php echo $minNextBid; ?>" step="<?php echo $auction['d_min_increment']; ?>">
                </div>
                <button type="button" id="place-bid-btn" class="btn btn-primary">Place Bid</button>
            </div>
            <div id="bid-feedback-msg"></div>
        <?php endif; ?>

        <!-- Live Bid History Log -->
        <div class="bid-history">
            <h4>Recent Bids</h4>
            <ul id="bid-history-list">
                <?php if (empty($bidsHistory)): ?>
                    <li class="no-bids-msg">No bids placed yet. Be the first!</li>
                <?php else: ?>
                    <?php foreach ($bidsHistory as $bid): ?>
                        <li>
                            <span class="bidder-name"><i class="fa fa-user-circle"></i> <?php echo osc_esc_html($bid['user_name']); ?></span>
                            <span class="bid-amt">LKR <?php echo number_format($bid['d_amount'], 2); ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <input type="hidden" id="bid-item-id" value="<?php echo $itemId; ?>">
        <input type="hidden" id="csrf-token" value="<?php echo osc_csrfguard_generate_token(); ?>">
    </div>

    <!-- Countdown and Real-time JS Toggles (Vanilla JS to bypass jQuery footer enqueuing restrictions) -->
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Live Countdown Timer
        var countdownEl = document.getElementById('countdown-timer');
        if (countdownEl) {
            var endTimeStr = countdownEl.getAttribute('data-endtime');
            if (endTimeStr && endTimeStr !== '' && countdownEl.textContent.trim() !== 'Ended') {
                var endTime = new Date(endTimeStr.replace(/-/g, "/")).getTime();

                var timerInterval = setInterval(function() {
                    var now = new Date().getTime();
                    var distance = endTime - now;

                    if (distance < 0) {
                        clearInterval(timerInterval);
                        countdownEl.innerHTML = 'Ended';
                        countdownEl.classList.add('text-danger');
                        var actionForm = document.querySelector('.bid-action-form');
                        if (actionForm) {
                            actionForm.innerHTML = '<div class="bid-expired-notice"><i class="fa fa-gavel"></i> Bidding has closed for this antique.</div>';
                        }
                    } else {
                        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        var timerText = "";
                        if (days > 0) timerText += days + "d ";
                        timerText += hours + "h " + minutes + "m " + seconds + "s";
                        countdownEl.innerHTML = timerText;
                    }
                }, 1000);
            }
        }

        // 2. Submit Bid AJAX Action
        var placeBidBtn = document.getElementById('place-bid-btn');
        if (placeBidBtn) {
            placeBidBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                var bidAmountInput = document.getElementById('user-bid-amount');
                var bidAmount = parseFloat(bidAmountInput.value);
                var itemId = document.getElementById('bid-item-id').value;
                var csrfToken = document.getElementById('csrf-token').value;
                var feedback = document.getElementById('bid-feedback-msg');

                if (isNaN(bidAmount) || bidAmount <= 0) {
                    feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> Please enter a valid bid.</span>';
                    feedback.style.display = 'block';
                    return;
                }

                placeBidBtn.disabled = true;
                placeBidBtn.innerText = 'Placing...';
                feedback.style.display = 'none';

                // Vanilla AJAX call
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo osc_ajax_hook_url("place_bid"); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        placeBidBtn.disabled = false;
                        placeBidBtn.innerText = 'Place Bid';
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    feedback.innerHTML = '<span class="text-success success-alert"><i class="fa fa-check-circle"></i> ' + response.message + '</span>';
                                    feedback.style.display = 'block';
                                    document.getElementById('live-highest-bid').innerHTML = 'LKR ' + parseFloat(response.newHighest).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    
                                    var increment = <?php echo (float)$auction['d_min_increment']; ?>;
                                    var nextMin = parseFloat(response.newHighest) + increment;
                                    bidAmountInput.value = '';
                                    bidAmountInput.setAttribute('placeholder', 'Min. ' + nextMin.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                    bidAmountInput.setAttribute('min', nextMin);
                                    
                                    refreshBidHistory(itemId);
                                } else {
                                    feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> ' + response.error + '</span>';
                                    feedback.style.display = 'block';
                                }
                            } catch (err) {
                                feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> Invalid response from server.</span>';
                                feedback.style.display = 'block';
                            }
                        } else {
                            feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> Communication error. Please retry.</span>';
                            feedback.style.display = 'block';
                        }
                    }
                };
                
                var params = 'itemId=' + encodeURIComponent(itemId) + 
                             '&bidAmount=' + encodeURIComponent(bidAmount) + 
                             '&octoken=' + encodeURIComponent(csrfToken);
                xhr.send(params);
            });
        }

        // 3. Helper to refresh history logs after placeBid
        function refreshBidHistory(itemId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '<?php echo osc_base_url(true); ?>?page=ajax&action=runhook&hook=get_bids_history&itemId=' + itemId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (data && data.length > 0) {
                            var html = '';
                            for (var i = 0; i < data.length; i++) {
                                var bid = data[i];
                                html += '<li><span class="bidder-name"><i class="fa fa-user-circle"></i> ' + escapeHtml(bid.user_name) + '</span>';
                                html += '<span class="bid-amt">LKR ' + parseFloat(bid.d_amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</span></li>';
                            }
                            var historyList = document.getElementById('bid-history-list');
                            if (historyList) {
                                historyList.innerHTML = html;
                            }
                        }
                    } catch (e) {}
                }
            };
            xhr.send();
        }

        function escapeHtml(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // 4. Bids update pooling (every 8 seconds to fetch other bidders' bids in real time)
        setInterval(function() {
            var itemIdEl = document.getElementById('bid-item-id');
            if (!itemIdEl) return;
            var itemId = itemIdEl.value;
            if (itemId) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '<?php echo osc_base_url(true); ?>?page=ajax&action=runhook&hook=get_highest_bid_val&itemId=' + itemId, true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data && data.success) {
                                var liveBidEl = document.getElementById('live-highest-bid');
                                var currentHighest = parseFloat(liveBidEl.textContent.replace(/LKR /g, '').replace(/,/g, ''));
                                var apiHighest = parseFloat(data.amount);
                                
                                if (apiHighest > currentHighest) {
                                    liveBidEl.innerHTML = 'LKR ' + apiHighest.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                                    var increment = <?php echo (float)$auction['d_min_increment']; ?>;
                                    var nextMin = apiHighest + increment;
                                    var bidInput = document.getElementById('user-bid-amount');
                                    if (bidInput) {
                                        bidInput.setAttribute('placeholder', 'Min. ' + nextMin.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                                        bidInput.setAttribute('min', nextMin);
                                    }
                                    refreshBidHistory(itemId);
                                }
                            }
                        } catch (e) {}
                    }
                };
                xhr.send();
            }
        }, 8000);
        // 5. Seller console cancellation handler
        var cancelWinnerBtn = document.getElementById('cancel-winner-btn');
        if (cancelWinnerBtn) {
            cancelWinnerBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (!confirm("Are you sure you want to cancel the winning bidder? This will decline their bid and offer the item to the second-highest bidder. This action cannot be undone.")) {
                    return;
                }
                
                var itemId = cancelWinnerBtn.getAttribute('data-itemid');
                var csrfToken = document.getElementById('csrf-token').value;
                var feedback = document.getElementById('seller-console-feedback');
                
                cancelWinnerBtn.disabled = true;
                cancelWinnerBtn.innerText = 'Processing...';
                if (feedback) feedback.style.display = 'none';

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo osc_ajax_hook_url("cancel_winner_offer_second"); ?>', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        cancelWinnerBtn.disabled = false;
                        cancelWinnerBtn.innerHTML = '<i class="fa fa-times-circle"></i> Cancel Winner & Offer to 2nd Bidder';
                        if (xhr.status === 200) {
                            try {
                                var response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    if (feedback) {
                                        feedback.innerHTML = '<span class="text-success success-alert"><i class="fa fa-check-circle"></i> ' + response.message + '</span>';
                                        feedback.style.display = 'block';
                                    }
                                    setTimeout(function() {
                                        window.location.reload();
                                    }, 2000);
                                } else {
                                    if (feedback) {
                                        feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> ' + response.error + '</span>';
                                        feedback.style.display = 'block';
                                    }
                                }
                            } catch (err) {
                                if (feedback) {
                                    feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> Invalid response from server.</span>';
                                    feedback.style.display = 'block';
                                }
                            }
                        } else {
                            if (feedback) {
                                feedback.innerHTML = '<span class="text-danger error-alert"><i class="fa fa-exclamation-triangle"></i> Communication error. Please retry.</span>';
                                feedback.style.display = 'block';
                            }
                        }
                    }
                };
                
                var params = 'itemId=' + encodeURIComponent(itemId) + 
                             '&octoken=' + encodeURIComponent(csrfToken);
                xhr.send(params);
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

    // 5. Model execution
    $res = ModelBids::newInstance()->placeBid($itemId, $userId, $bidAmount, $ipAddress);

    if ($res === true) {
        echo json_encode(array(
            'success' => true,
            'message' => 'Your bid has been placed!',
            'newHighest' => $bidAmount
        ));
    } else {
        echo json_encode(array('success' => false, 'error' => $res));
    }
    exit;
}

// Hook to pull bid history list
osc_add_hook('ajax_get_bids_history', 'antique_bids_ajax_history');
function antique_bids_ajax_history() {
    ob_clean();
    header('Content-Type: application/json');
    $itemId = (int)Params::getParam('itemId');
    $history = ModelBids::newInstance()->getBids($itemId, 5);
    echo json_encode($history);
    exit;
}

// Hook to pull highest bid dynamically
osc_add_hook('ajax_get_highest_bid_val', 'antique_bids_ajax_highest');
function antique_bids_ajax_highest() {
    ob_clean();
    header('Content-Type: application/json');
    $itemId = (int)Params::getParam('itemId');
    $highest = ModelBids::newInstance()->getHighestBid($itemId);
    if ($highest) {
        echo json_encode(array('success' => true, 'amount' => $highest['d_amount']));
    } else {
        // Fallback to start price
        $auction = ModelBids::newInstance()->getAuction($itemId);
        if ($auction) {
            echo json_encode(array('success' => true, 'amount' => $auction['d_starting_price']));
        } else {
            echo json_encode(array('success' => false));
        }
    }
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
    $startingPrice = $auction ? $auction['d_starting_price'] : '';
    $minIncrement = $auction ? $auction['d_min_increment'] : 100;
    
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
            <h3><i class="fa fa-gavel"></i> Live Bidding Configuration</h3>
        </div>
        <div class="form-row checkbox-row">
            <label class="toggle-switch">
                <input type="checkbox" name="bids_enabled" id="bids_enabled" value="1" <?php echo $enabled ? 'checked' : ''; ?>>
                <span class="slider-toggle"></span>
            </label>
            <span class="toggle-label">Enable Live Bidding / Auction for this item</span>
        </div>
        
        <div id="bids-details-panel" class="bids-slide-panel" style="<?php echo $enabled ? 'display: flex;' : 'display: none;'; ?>">
            <div class="form-row">
                <label for="bids_starting_price">Starting Price (LKR)</label>
                <div class="input-wrap-post">
                    <span class="currency-label-post">LKR</span>
                    <input type="number" name="bids_starting_price" id="bids_starting_price" value="<?php echo osc_esc_html($startingPrice); ?>" placeholder="Leave blank to use main listing price" style="padding-left: 45px;">
                </div>
                <span class="help-text">This will be the base price where bidding starts.</span>
            </div>
            
            <div class="form-row">
                <label for="bids_min_increment">Minimum Bid Increment (LKR)</label>
                <div class="input-wrap-post">
                    <span class="currency-label-post">LKR</span>
                    <input type="number" name="bids_min_increment" id="bids_min_increment" value="<?php echo osc_esc_html($minIncrement); ?>" min="1" style="padding-left: 45px;">
                </div>
                <span class="help-text">The minimum extra amount a bidder must add to the current highest bid.</span>
            </div>
            
            <div class="form-row">
                <label for="bids_duration">Auction Duration (Days)</label>
                <div class="input-wrap-post select-wrap">
                    <select name="bids_duration" id="bids_duration">
                        <option value="3" <?php echo $duration == 3 ? 'selected' : ''; ?>>3 Days</option>
                        <option value="5" <?php echo $duration == 5 ? 'selected' : ''; ?>>5 Days</option>
                        <option value="7" <?php echo $duration == 7 ? 'selected' : ''; ?>>7 Days (Recommended)</option>
                        <option value="10" <?php echo $duration == 10 ? 'selected' : ''; ?>>10 Days</option>
                        <option value="14" <?php echo $duration == 14 ? 'selected' : ''; ?>>14 Days</option>
                        <option value="30" <?php echo $duration == 30 ? 'selected' : ''; ?>>30 Days</option>
                    </select>
                </div>
                <span class="help-text">How long the auction will remain active from publication.</span>
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
        
        ModelBids::newInstance()->saveAuction($itemId, $startingPrice, $minIncrement, $duration, true);
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
        
        ModelBids::newInstance()->saveAuction($itemId, $startingPrice, $minIncrement, $duration, true);
    } else {
        // Disable listing auction active state
        $auction = ModelBids::newInstance()->getAuction($itemId);
        if ($auction) {
            ModelBids::newInstance()->saveAuction($itemId, $auction['d_starting_price'], $auction['d_min_increment'], 7, false);
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
    $last_cron = osc_get_preference('antique_bids_last_cron', 'antique_bids');
    $force_run = (Params::getParam('run_cron') == '1');
    if ($last_cron == '') {
        osc_set_preference('antique_bids_last_cron', time(), 'antique_bids', 'INTEGER');
        $last_cron = time();
    }
    
    if ($force_run || (time() - $last_cron > 300)) {
        osc_set_preference('antique_bids_last_cron', time(), 'antique_bids', 'INTEGER');
        antique_bids_process_expired_auctions();
    }
}

/**
 * Process expired, unprocessed auctions and declare winners
 */
function antique_bids_process_expired_auctions() {
    $expired = ModelBids::newInstance()->getExpiredUnprocessedAuctions();
    if (empty($expired)) {
        return;
    }
    
    foreach ($expired as $auction) {
        $itemId = $auction['fk_i_item_id'];
        
        // Find highest active bid
        $highest = ModelBids::newInstance()->getHighestBid($itemId);
        if ($highest) {
            // Update bid status to won
            ModelBids::newInstance()->setBidStatus($highest['pk_i_id'], 'won');
            
            // Send email to winner & seller
            antique_bids_send_winner_emails($itemId, $highest);
        } else {
            // No bids were placed. Notify seller that auction ended with no bids.
            antique_bids_send_no_bids_email($itemId);
        }
        
        // Mark auction as processed
        ModelBids::newInstance()->markAuctionProcessed($itemId);
    }
}

/**
 * Send winning bidder email notifications
 */
function antique_bids_send_winner_emails($itemId, $highest) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;
    
    $winnerUser = User::newInstance()->findByPrimaryKey($highest['fk_i_user_id']);
    if (!$winnerUser) return;
    
    $winnerEmail = $winnerUser['s_email'];
    $winnerName = $winnerUser['s_name'];
    $bidAmountStr = number_format($highest['d_amount'], 2) . ' LKR';
    
    $itemUrl = osc_item_url_from_item($item);
    
    // 1. Email to Winner
    $winnerSubject = "Congratulations! You won the auction for " . $item['s_title'];
    $winnerBody = "Hello {$winnerName},<br/><br/>";
    $winnerBody .= "Congratulations! You won the live bidding auction for the antique <strong>" . osc_esc_html($item['s_title']) . "</strong>.<br/>";
    $winnerBody .= "Your winning bid amount: <strong>{$bidAmountStr}</strong>.<br/><br/>";
    $winnerBody .= "Please complete your payment and shipping coordination with the seller within 24 hours.<br/>";
    $winnerBody .= "You can contact the seller directly by visiting the listing page here: <a href='{$itemUrl}'>{$itemUrl}</a>.<br/><br/>";
    $winnerBody .= "Thank you for bidding!<br/>AntiqueLanka Team";
    
    $winnerParams = array(
        'from' => _osc_from_email_aux(),
        'to' => $winnerEmail,
        'to_name' => $winnerName,
        'subject' => $winnerSubject,
        'body' => $winnerBody,
        'alt_body' => strip_tags($winnerBody)
    );
    @osc_sendMail($winnerParams);
    
    // 2. Email to Seller
    $sellerEmail = $item['s_contact_email'];
    $sellerName = $item['s_contact_name'];
    
    $sellerSubject = "Auction Ended: Your item " . $item['s_title'] . " has been sold!";
    $sellerBody = "Hello {$sellerName},<br/><br/>";
    $sellerBody .= "The live bidding auction for your antique listing <strong>" . osc_esc_html($item['s_title']) . "</strong> has ended.<br/>";
    $sellerBody .= "Winning Bid Amount: <strong>{$bidAmountStr}</strong>.<br/>";
    $sellerBody .= "Winner Name: <strong>" . osc_esc_html($winnerName) . "</strong> (Email: {$winnerEmail}).<br/><br/>";
    $sellerBody .= "Please contact the winner to finalize the transaction. If the winner fails to pay within 24 hours, you can cancel their bid and offer the item to the 2nd highest bidder directly from your listing details page.<br/><br/>";
    $sellerBody .= "Thank you,<br/>AntiqueLanka Team";
    
    $sellerParams = array(
        'from' => _osc_from_email_aux(),
        'to' => $sellerEmail,
        'to_name' => $sellerName,
        'subject' => $sellerSubject,
        'body' => $sellerBody,
        'alt_body' => strip_tags($sellerBody)
    );
    @osc_sendMail($sellerParams);
}

/**
 * Send seller no bids notification
 */
function antique_bids_send_no_bids_email($itemId) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;
    
    $sellerEmail = $item['s_contact_email'];
    $sellerName = $item['s_contact_name'];
    
    $sellerSubject = "Auction Ended: No bids placed for " . $item['s_title'];
    $sellerBody = "Hello {$sellerName},<br/><br/>";
    $sellerBody .= "The live bidding auction for your antique listing <strong>" . osc_esc_html($item['s_title']) . "</strong> has ended.<br/>";
    $sellerBody .= "Unfortunately, no bids were placed on this item during the auction duration.<br/>";
    $sellerBody .= "You can re-list or adjust the starting price by editing your item details here: <a href='" . osc_item_edit_url('', $itemId) . "'>Edit Listing</a>.<br/><br/>";
    $sellerBody .= "Thank you,<br/>AntiqueLanka Team";
    
    $sellerParams = array(
        'from' => _osc_from_email_aux(),
        'to' => $sellerEmail,
        'to_name' => $sellerName,
        'subject' => $sellerSubject,
        'body' => $sellerBody,
        'alt_body' => strip_tags($sellerBody)
    );
    @osc_sendMail($sellerParams);
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
        echo json_encode(array('success' => false, 'error' => 'CSRF security token expired. Please reload page.'));
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

    $db = ModelBids::newInstance()->dao;
    $prefix = DB_TABLE_PREFIX;
    $sql_won = "SELECT * FROM `{$prefix}t_item_bids` 
                WHERE fk_i_item_id = " . (int)$itemId . " AND s_status = 'won' 
                LIMIT 1";
    
    $won_res = $db->query($sql_won);
    if (!$won_res || $won_res->numRows() == 0) {
        echo json_encode(array('success' => false, 'error' => 'No active winning bid found to cancel.'));
        exit;
    }
    $currentWinnerBid = $won_res->row();

    // Mark winner's bid status as 'withdrawn'
    ModelBids::newInstance()->setBidStatus($currentWinnerBid['pk_i_id'], 'withdrawn');

    // Find next highest active bid
    $next_bids = ModelBids::newInstance()->getBids($itemId, 1);
    $newWinnerBid = !empty($next_bids) ? $next_bids[0] : null;

    if ($newWinnerBid) {
        ModelBids::newInstance()->setBidStatus($newWinnerBid['pk_i_id'], 'won');
        antique_bids_send_second_chance_emails($itemId, $currentWinnerBid, $newWinnerBid);

        echo json_encode(array(
            'success' => true,
            'message' => 'Cancelled winner. Bid offered to next highest bidder: ' . osc_esc_html($newWinnerBid['user_name']) . '.',
            'hasNewWinner' => true,
            'newWinnerName' => $newWinnerBid['user_name'],
            'newWinnerEmail' => User::newInstance()->findByPrimaryKey($newWinnerBid['fk_i_user_id'])['s_email'],
            'newWinnerAmount' => number_format($newWinnerBid['d_amount'], 2)
        ));
    } else {
        antique_bids_send_cancellation_no_bidders_emails($itemId, $currentWinnerBid);
        
        echo json_encode(array(
            'success' => true,
            'message' => 'Cancelled winner. No other bids exist on this item.',
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

    $oldWinner = User::newInstance()->findByPrimaryKey($oldBid['fk_i_user_id']);
    if ($oldWinner) {
        $oldSubject = "Auction Update: Your winning bid for " . $item['s_title'] . " has been cancelled";
        $oldBody = "Hello " . $oldWinner['s_name'] . ",<br/><br/>";
        $oldBody .= "Your winning bid of <strong>" . number_format($oldBid['d_amount'], 2) . " LKR</strong> for the antique <strong>" . osc_esc_html($item['s_title']) . "</strong> has been cancelled by the seller.<br/>";
        $oldBody .= "This action was taken due to a payment timeout (non-payment within the 24-hour limit).<br/><br/>";
        $oldBody .= "Thank you,<br/>AntiqueLanka Team";
        
        $oldParams = array(
            'from' => _osc_from_email_aux(),
            'to' => $oldWinner['s_email'],
            'to_name' => $oldWinner['s_name'],
            'subject' => $oldSubject,
            'body' => $oldBody,
            'alt_body' => strip_tags($oldBody)
        );
        @osc_sendMail($oldParams);
    }

    $newWinner = User::newInstance()->findByPrimaryKey($newBid['fk_i_user_id']);
    if ($newWinner) {
        $newSubject = "Second Chance Offer: You won the auction for " . $item['s_title'] . "!";
        $newBody = "Hello " . $newWinner['s_name'] . ",<br/><br/>";
        $newBody .= "Good news! The previous highest bidder did not finalize payment, and the seller of <strong>" . osc_esc_html($item['s_title']) . "</strong> has offered the item to you as the next highest bidder.<br/>";
        $newBody .= "Your bid amount: <strong>" . number_format($newBid['d_amount'], 2) . " LKR</strong>.<br/><br/>";
        $newBody .= "Please complete payment and coordinate shipping with the seller within 24 hours.<br/>";
        $newBody .= "You can contact the seller directly by visiting the listing page here: <a href='" . osc_item_url_from_item($item) . "'>" . osc_item_url_from_item($item) . "</a>.<br/><br/>";
        $newBody .= "Thank you,<br/>AntiqueLanka Team";

        $newParams = array(
            'from' => _osc_from_email_aux(),
            'to' => $newWinner['s_email'],
            'to_name' => $newWinner['s_name'],
            'subject' => $newSubject,
            'body' => $newBody,
            'alt_body' => strip_tags($newBody)
        );
        @osc_sendMail($newParams);
    }

    $sellerSubject = "Seller Update: Winner cancelled, offered to next bidder";
    $sellerBody = "Hello " . $item['s_contact_name'] . ",<br/><br/>";
    $sellerBody .= "You have successfully cancelled the winning bid of <strong>" . number_format($oldBid['d_amount'], 2) . " LKR</strong> due to non-payment.<br/>";
    $sellerBody .= "The item <strong>" . osc_esc_html($item['s_title']) . "</strong> has been offered to the 2nd highest bidder: <strong>" . osc_esc_html($newBid['user_name']) . "</strong> (Email: " . $newWinner['s_email'] . ") at their bid price of <strong>" . number_format($newBid['d_amount'], 2) . " LKR</strong>.<br/><br/>";
    $sellerBody .= "Please wait for the new winner to coordinate payment.<br/><br/>";
    $sellerBody .= "Thank you,<br/>AntiqueLanka Team";

    $sellerParams = array(
        'from' => _osc_from_email_aux(),
        'to' => $item['s_contact_email'],
        'to_name' => $item['s_contact_name'],
        'subject' => $sellerSubject,
        'body' => $sellerBody,
        'alt_body' => strip_tags($sellerBody)
    );
    @osc_sendMail($sellerParams);
}

/**
 * Send cancellation email when no other bidders exist
 */
function antique_bids_send_cancellation_no_bidders_emails($itemId, $oldBid) {
    $item = Item::newInstance()->findByPrimaryKey($itemId);
    if (!$item) return;

    $oldWinner = User::newInstance()->findByPrimaryKey($oldBid['fk_i_user_id']);
    if ($oldWinner) {
        $oldSubject = "Auction Update: Your winning bid for " . $item['s_title'] . " has been cancelled";
        $oldBody = "Hello " . $oldWinner['s_name'] . ",<br/><br/>";
        $oldBody .= "Your winning bid of <strong>" . number_format($oldBid['d_amount'], 2) . " LKR</strong> for the antique <strong>" . osc_esc_html($item['s_title']) . "</strong> has been cancelled by the seller due to non-payment.<br/><br/>";
        $oldBody .= "Thank you,<br/>AntiqueLanka Team";
        
        $oldParams = array(
            'from' => _osc_from_email_aux(),
            'to' => $oldWinner['s_email'],
            'to_name' => $oldWinner['s_name'],
            'subject' => $oldSubject,
            'body' => $oldBody,
            'alt_body' => strip_tags($oldBody)
        );
        @osc_sendMail($oldParams);
    }

    $sellerSubject = "Seller Update: Winner cancelled, no other bidders";
    $sellerBody = "Hello " . $item['s_contact_name'] . ",<br/><br/>";
    $sellerBody .= "You have successfully cancelled the winning bid of <strong>" . number_format($oldBid['d_amount'], 2) . " LKR</strong> due to non-payment.<br/>";
    $sellerBody .= "There are no other active bids on the listing <strong>" . osc_esc_html($item['s_title']) . "</strong>.<br/>";
    $sellerBody .= "You may re-list the auction or adjust its starting price here: <a href='" . osc_item_edit_url('', $itemId) . "'>Edit Listing</a>.<br/><br/>";
    $sellerBody .= "Thank you,<br/>AntiqueLanka Team";

    $sellerParams = array(
        'from' => _osc_from_email_aux(),
        'to' => $item['s_contact_email'],
        'to_name' => $item['s_contact_name'],
        'subject' => $sellerSubject,
        'body' => $sellerBody,
        'alt_body' => strip_tags($sellerBody)
    );
    @osc_sendMail($sellerParams);
}
?>
