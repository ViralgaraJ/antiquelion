<?php
  // Marketplace > Payments (included by marketplace.php: $al_db, $al_form, $al_token, $al_count)
  if (!defined('ABS_PATH')) { exit; }
  $al_products = antique_promo_products();
  $al_o = antique_orders_table();
  $al_paid30 = $al_db->query("SELECT s_currency, COUNT(*) AS n, SUM(d_amount) AS total, SUM(d_usd) AS usd FROM `{$al_o}` WHERE s_status = 'paid' AND s_mode = 'live' AND dt_paid >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY s_currency");
  $al_paid30 = $al_paid30 ? $al_paid30->result() : array();
  $al_usd30 = 0; $al_n30 = 0;
  foreach ($al_paid30 as $r) { $al_usd30 += (float)$r['usd']; $al_n30 += (int)$r['n']; }
  $al_live_promos = $al_count("SELECT COUNT(DISTINCT fk_i_item_id) AS n FROM `" . antique_promos_table() . "` WHERE b_cancelled = 0 AND dt_start <= NOW() AND dt_end > NOW()");
  $al_ord_res = $al_db->query("SELECT o.*, u.s_email, (SELECT d.s_title FROM `" . DB_TABLE_PREFIX . "t_item_description` d WHERE d.fk_i_item_id = o.fk_i_item_id LIMIT 1) AS s_title
    FROM `{$al_o}` o LEFT JOIN `" . DB_TABLE_PREFIX . "t_user` u ON u.pk_i_id = o.fk_i_user_id ORDER BY o.dt_created DESC LIMIT 100");
  $al_orders = $al_ord_res ? $al_ord_res->result() : array();
  $al_notify = osc_ajax_hook_url('al_payhere_notify');
?>
<style>.almk-prices { border-collapse: collapse; margin: 6px 0; } .almk-prices th, .almk-prices td { padding: 6px 10px 6px 0; text-align: left; vertical-align: middle; } .almk-prices th { color: #14213D; font-size: 13px; }</style>
<h3 class="almk-h"><?php _e('Last 30 days (live payments)', 'starter'); ?></h3>
<div class="almk-grid">
  <div class="almk-stat is-ok"><strong>USD <?php echo number_format($al_usd30, 2); ?></strong><span><?php _e('Revenue (list price in USD)', 'starter'); ?></span></div>
  <div class="almk-stat"><strong><?php echo (int)$al_n30; ?></strong><span><?php _e('Paid orders', 'starter'); ?></span></div>
  <div class="almk-stat"><strong><?php echo (int)$al_live_promos; ?></strong><span><?php _e('Listings promoted right now', 'starter'); ?></span></div>
  <?php foreach ($al_paid30 as $r) { ?><div class="almk-stat"><strong><?php echo osc_esc_html(antique_pay_money($r['total'], $r['s_currency'])); ?></strong><span><?php echo sprintf(__('Charged in %s', 'starter'), osc_esc_html($r['s_currency'])); ?></span></div><?php } ?>
</div>

<form method="post" action="<?php echo osc_esc_html(antique_marketplace_admin_url('payments')); ?>" class="almk-card almk-seo">
  <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" />
  <input type="hidden" name="al_mk" value="pay_save" />
  <input type="hidden" name="tab" value="payments" />
  <h3><?php _e('PayHere', 'starter'); ?>
    <span class="almk-tag<?php echo antique_pay_setting('enabled', '0') === '1' && antique_pay_configured() ? ' is-ok' : ' is-bad'; ?>"><?php $al_on = antique_pay_setting('enabled', '0') === '1' && antique_pay_configured(); echo $al_on ? (antique_pay_live() ? __('Taking payments', 'starter') : __('On for test members', 'starter')) : __('Off', 'starter'); ?></span>
    <span class="almk-tag<?php echo antique_pay_live() ? ' is-ok' : ''; ?>"><?php echo antique_pay_live() ? __('Live', 'starter') : __('Sandbox (test)', 'starter'); ?></span></h3>
  <p class="almk-meta"><?php _e('Sandbox: create a free account at sandbox.payhere.lk, then Integrations > Add Domain/App to get a Merchant ID and Merchant Secret for this domain. Live: use the Merchant ID and the secret for this domain from your approved payhere.lk account. Sandbox and live keys are different.', 'starter'); ?></p>
  <label><span><input type="checkbox" name="pay_enabled" value="1"<?php echo antique_pay_setting('enabled', '0') === '1' ? ' checked' : ''; ?> /> <strong><?php _e('Sellers can buy promotions', 'starter'); ?></strong></span></label>
  <label><strong><?php _e('Mode', 'starter'); ?></strong>
    <select name="pay_mode" style="max-width:260px;height:36px">
      <option value="sandbox"<?php echo !antique_pay_live() ? ' selected' : ''; ?>><?php _e('Sandbox (test cards, no real money)', 'starter'); ?></option>
      <option value="live"<?php echo antique_pay_live() ? ' selected' : ''; ?>><?php _e('Live (real payments)', 'starter'); ?></option>
    </select></label>
  <label><strong><?php _e('Test members (sandbox mode only)', 'starter'); ?></strong>
    <span class="almk-meta"><?php _e('Member IDs or emails, separated by commas. In sandbox mode only these members see the Promote buttons; in live mode every seller does.', 'starter'); ?></span>
    <input type="text" name="pay_testers" value="<?php echo osc_esc_html(antique_pay_setting('testers')); ?>" placeholder="you@example.com, 12" /></label>
  <label><strong><?php _e('Merchant ID', 'starter'); ?></strong>
    <input type="text" name="pay_merchant_id" maxlength="20" value="<?php echo osc_esc_html(antique_pay_setting('merchant_id')); ?>" autocomplete="off" /></label>
  <label><strong><?php _e('Merchant Secret', 'starter'); ?></strong>
    <span class="almk-meta"><?php echo antique_pay_setting('merchant_secret') !== '' ? __('Saved. Leave empty to keep it; paste a new one to replace it.', 'starter') : __('Not set yet.', 'starter'); ?></span>
    <input type="password" name="pay_merchant_secret" value="" autocomplete="new-password" /></label>
  <p class="almk-meta"><?php _e('Notify URL (sent to PayHere with every payment, nothing to set up):', 'starter'); ?> <code><?php echo osc_esc_html($al_notify); ?></code></p>

  <h3 style="margin-top:18px"><?php _e('Prices (USD)', 'starter'); ?></h3>
  <p class="almk-meta"><?php _e('Visitors pay in their own currency when PayHere supports it (LKR, USD, GBP, EUR, AUD), converted at the daily rate and rounded; everyone else pays in USD.', 'starter'); ?></p>
  <table class="almk-prices">
    <?php foreach ($al_products as $al_k => $al_p) { ?>
      <tr><th><?php echo osc_esc_html($al_p['name']); ?></th>
        <?php foreach ($al_p['prices'] as $al_d => $al_usd) { ?>
          <td><label style="flex-direction:row;align-items:center;gap:6px;margin:0"><?php echo sprintf(__('%d days', 'starter'), $al_d); ?>
            <input type="number" step="0.01" min="0.5" name="price[<?php echo $al_k; ?>][<?php echo (int)$al_d; ?>]" value="<?php echo osc_esc_html($al_usd); ?>" style="width:90px" /></label></td>
        <?php } ?>
      </tr>
    <?php } ?>
  </table>
  <div class="almk-actions"><button type="submit" class="almk-btn is-ok"><?php _e('Save', 'starter'); ?></button></div>
</form>

<form method="post" action="<?php echo osc_esc_html(antique_marketplace_admin_url('payments')); ?>" class="almk-card">
  <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" />
  <input type="hidden" name="al_mk" value="pay_grant" />
  <input type="hidden" name="tab" value="payments" />
  <h3><?php _e('Give a promotion for free', 'starter'); ?></h3>
  <p class="almk-meta"><?php _e('For goodwill or a payment taken outside the site. No order is created.', 'starter'); ?></p>
  <div class="almk-actions">
    <label class="almk-inline"><?php _e('Listing ID', 'starter'); ?> <input type="text" name="item" style="width:100px" required /></label>
    <label class="almk-inline"><select name="product"><?php foreach ($al_products as $al_k => $al_p) { ?><option value="<?php echo $al_k; ?>"><?php echo osc_esc_html($al_p['name']); ?></option><?php } ?></select></label>
    <label class="almk-inline"><?php _e('Days', 'starter'); ?> <input type="text" name="days" value="7" style="width:60px" /></label>
    <button type="submit" class="almk-btn"><?php _e('Start promotion', 'starter'); ?></button>
  </div>
</form>

<?php
  $al_mplans = antique_member_plans();
  $al_mpack = antique_member_pack();
  $al_mres = $al_db->query("SELECT m.*, u.s_email FROM `" . antique_members_table() . "` m LEFT JOIN `" . DB_TABLE_PREFIX . "t_user` u ON u.pk_i_id = m.fk_i_user_id
    WHERE m.b_cancelled = 0 AND m.dt_end > NOW() ORDER BY m.dt_end ASC LIMIT 200");
  $al_members = $al_mres ? $al_mres->result() : array();
?>
<form method="post" action="<?php echo osc_esc_html(antique_marketplace_admin_url('payments')); ?>" class="almk-card almk-seo">
  <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" />
  <input type="hidden" name="al_mk" value="member_save" />
  <input type="hidden" name="tab" value="payments" />
  <h3><?php _e('Seller plans and listing limits', 'starter'); ?>
    <span class="almk-tag<?php echo antique_member_on() ? ' is-ok' : ' is-bad'; ?>"><?php echo antique_member_on() ? __('On', 'starter') : __('Off', 'starter'); ?></span></h3>
  <p class="almk-meta"><?php _e('Limits only apply to sellers who can buy (in sandbox mode: the test members above). Listings already online are never hidden; sellers over the limit just cannot add new ones.', 'starter'); ?>
    <a href="<?php echo osc_esc_html(antique_plans_url()); ?>" target="_blank"><?php _e('View the plans page', 'starter'); ?></a></p>
  <label><span><input type="checkbox" name="member_enabled" value="1"<?php echo antique_member_on() ? ' checked' : ''; ?> /> <strong><?php _e('Sell plans and limit free listings', 'starter'); ?></strong></span></label>
  <label><strong><?php _e('Free plan: live listings per seller', 'starter'); ?></strong>
    <input type="number" name="member_free_limit" min="1" value="<?php echo (int)antique_member_free_limit(); ?>" style="width:120px" /></label>
  <table class="almk-prices">
    <tr><th></th><th><?php _e('Live listings (0 = unlimited)', 'starter'); ?></th><th><?php _e('Promotion discount %', 'starter'); ?></th><th><?php _e('USD: 1 month', 'starter'); ?></th><th><?php _e('3 months', 'starter'); ?></th><th><?php _e('12 months', 'starter'); ?></th></tr>
    <?php foreach ($al_mplans as $al_k => $al_p) { ?>
      <tr><th><?php echo osc_esc_html($al_p['name']); ?></th>
        <td><input type="number" min="0" name="plan[<?php echo $al_k; ?>][limit]" value="<?php echo (int)$al_p['limit']; ?>" style="width:90px" /></td>
        <td><input type="number" min="0" max="90" name="plan[<?php echo $al_k; ?>][discount]" value="<?php echo (int)$al_p['discount']; ?>" style="width:80px" /></td>
        <?php foreach ($al_p['prices'] as $al_d => $al_usd) { ?>
          <td><input type="number" step="0.01" min="0.5" name="plan[<?php echo $al_k; ?>][prices][<?php echo (int)$al_d; ?>]" value="<?php echo osc_esc_html($al_usd); ?>" style="width:90px" /></td>
        <?php } ?>
      </tr>
    <?php } ?>
  </table>
  <label style="margin-top:14px"><strong><?php echo sprintf(__('Listing pack (%d days)', 'starter'), ANTIQUE_MEMBER_PACK_DAYS); ?></strong>
    <span style="display:flex;gap:10px;align-items:center;font-weight:400"><input type="number" name="member_pack_slots" min="1" value="<?php echo (int)$al_mpack['slots']; ?>" style="width:90px" /> <?php _e('extra listings for USD', 'starter'); ?>
    <input type="number" step="0.01" min="0.5" name="member_pack_usd" value="<?php echo osc_esc_html($al_mpack['usd']); ?>" style="width:90px" /></span></label>
  <div class="almk-actions"><button type="submit" class="almk-btn is-ok"><?php _e('Save', 'starter'); ?></button></div>
</form>

<form method="post" action="<?php echo osc_esc_html(antique_marketplace_admin_url('payments')); ?>" class="almk-card">
  <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" />
  <input type="hidden" name="al_mk" value="member_grant" />
  <input type="hidden" name="tab" value="payments" />
  <h3><?php _e('Give a plan or listing pack', 'starter'); ?></h3>
  <p class="almk-meta"><?php _e('For launch offers, partners or a payment taken outside the site. No order is created.', 'starter'); ?></p>
  <div class="almk-actions">
    <label class="almk-inline"><?php _e('Member ID or email', 'starter'); ?> <input type="text" name="member" style="width:220px" required /></label>
    <label class="almk-inline"><select name="plan_key"><?php foreach ($al_mplans as $al_k => $al_p) { ?><option value="<?php echo $al_k; ?>"><?php echo osc_esc_html($al_p['name']); ?></option><?php } ?><option value="pack"><?php _e('Listing pack', 'starter'); ?></option></select></label>
    <label class="almk-inline"><?php _e('Days', 'starter'); ?> <input type="text" name="days" value="30" style="width:60px" /></label>
    <button type="submit" class="almk-btn"><?php _e('Give', 'starter'); ?></button>
  </div>
</form>

<h3 class="almk-sub"><?php echo sprintf(__('Running plans and packs (%d)', 'starter'), count($al_members)); ?></h3>
<?php if (!$al_members) { ?><div class="almk-empty"><?php _e('No paid plans yet.', 'starter'); ?></div><?php } ?>
<?php foreach ($al_members as $m) { $al_mname = $m['s_plan'] === 'pack' ? sprintf(__('Listing pack +%d', 'starter'), $m['i_slots']) : (isset($al_mplans[$m['s_plan']]) ? $al_mplans[$m['s_plan']]['name'] : $m['s_plan']); ?>
  <div class="almk-row">
    <div><strong><?php echo osc_esc_html($al_mname); ?></strong> <span class="almk-meta"><?php echo osc_esc_html(sprintf('%s · %s → %s%s', (string)$m['s_email'], $m['dt_start'], $m['dt_end'], $m['fk_i_order_id'] ? '' : ' · ' . __('given by admin', 'starter'))); ?></span></div>
    <?php echo $al_form('member_stop', $m['pk_i_id'], __('Stop', 'starter'), 'is-bad', '', __('Stop this plan now? The member is not refunded automatically.', 'starter')); ?>
  </div>
<?php } ?>

<h3 class="almk-sub"><?php _e('Orders', 'starter'); ?></h3>
<?php if (!$al_orders) { ?><div class="almk-empty"><?php _e('No orders yet.', 'starter'); ?></div><?php } ?>
<?php foreach ($al_orders as $r) { $al_st = $r['s_status']; ?>
  <div class="almk-row">
    <div>
      <strong><?php echo osc_esc_html($r['s_ref']); ?></strong>
      <span class="almk-tag<?php echo $al_st === 'paid' ? ' is-ok' : (in_array($al_st, array('failed', 'chargedback'), true) ? ' is-bad' : ''); ?>"><?php echo osc_esc_html(antique_order_status_label($al_st)); ?></span>
      <?php if ($r['s_mode'] !== 'live') { ?><span class="almk-tag"><?php _e('Sandbox', 'starter'); ?></span><?php } ?>
      <div class="almk-meta">
        <?php echo osc_esc_html(sprintf('%s · %s · %s · %s', $r['dt_created'], antique_pay_order_label($r, false), antique_pay_money($r['d_amount'], $r['s_currency']), (string)$r['s_email'])); ?>
        <?php if ((int)$r['fk_i_item_id'] > 0) { ?>· <a href="<?php echo osc_esc_html(osc_admin_base_url(true) . '?page=items&action=item_edit&id=' . (int)$r['fk_i_item_id']); ?>">#<?php echo (int)$r['fk_i_item_id']; ?> <?php echo osc_esc_html((string)$r['s_title']); ?></a><?php } ?>
        <?php if ($r['s_gateway_id']) { ?> · PayHere <?php echo osc_esc_html($r['s_gateway_id'] . ($r['s_method'] ? ' (' . $r['s_method'] . ')' : '')); ?><?php } ?>
        <?php if ($r['s_message'] && $al_st !== 'paid') { ?> · <?php echo osc_esc_html($r['s_message']); ?><?php } ?>
      </div>
    </div>
    <?php if ($al_st === 'paid') { echo $al_form('pay_mark', $r['pk_i_id'], __('Mark refunded', 'starter'), 'is-bad', '', __('Only do this after refunding the payment in the PayHere portal. What the order bought (promotion, plan or listing pack) stops now.', 'starter')); } ?>
  </div>
<?php } ?>
