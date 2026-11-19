<?php
  $al_uid = (int)osc_logged_user_id();
  $al_db = antique_pay_db();
  $al_ref = (string)Params::getParam('order');
  $al_order = $al_ref !== '' ? antique_order_by_ref($al_ref) : null;
  if ($al_order && (int)$al_order['fk_i_user_id'] !== $al_uid) { $al_order = null; }
  // Back from PayHere's cancel button: close the checkout unless PayHere already reported a result
  if ($al_order && Params::getParam('cancelled') == '1' && $al_order['s_status'] === 'pending') {
    $al_db->query(sprintf("UPDATE `%s` SET s_status = 'cancelled', s_message = 'Cancelled by the member', dt_updated = %s WHERE pk_i_id = %d AND s_status = 'pending'",
      antique_orders_table(), $al_db->escape(antique_pay_now()), (int)$al_order['pk_i_id']));
    $al_order = antique_order_by_ref($al_ref);
  }
  $al_products = antique_promo_products();
  // open checkouts older than a day are noise (the cron closes them)
  $al_res = $al_db->query(sprintf("SELECT o.*, (SELECT d.s_title FROM `%st_item_description` d WHERE d.fk_i_item_id = o.fk_i_item_id LIMIT 1) AS s_title
    FROM `%s` o WHERE o.fk_i_user_id = %d AND (o.s_status <> 'pending' OR o.dt_created > DATE_SUB(NOW(), INTERVAL 1 DAY))
    ORDER BY o.dt_created DESC LIMIT 100", DB_TABLE_PREFIX, antique_orders_table(), $al_uid));
  $al_rows = $al_res ? $al_res->result() : array();
?>
<div class="al-account-head">
  <h2><?php _e('Payments', 'starter'); ?></h2>
</div>

<?php if ($al_order) { $al_s = $al_order['s_status']; ?>
  <div class="al-pay-result is-<?php echo osc_esc_html($al_s); ?>" role="status">
    <?php if ($al_s === 'paid') { ?>
      <h3><?php echo antique_pay_is_promo($al_order) ? __('Payment received. Your promotion is live.', 'starter') : __('Payment received. Your purchase is active.', 'starter'); ?></h3>
      <p><?php echo osc_esc_html(sprintf(__('Order %s. We have emailed you a receipt.', 'starter'), $al_order['s_ref'])); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_pay_order_url($al_order)); ?>"><?php echo antique_pay_is_promo($al_order) ? __('View your listing', 'starter') : __('See your plan', 'starter'); ?></a>
    <?php } elseif ($al_s === 'pending') { ?>
      <h3><?php _e('Confirming your payment...', 'starter'); ?></h3>
      <p><?php _e('PayHere is confirming the payment. This page refreshes by itself and usually takes a few seconds.', 'starter'); ?></p>
      <script>setTimeout(function () { window.location.reload(); }, 4000);</script>
    <?php } elseif ($al_s === 'cancelled') { ?>
      <h3><?php _e('Payment cancelled', 'starter'); ?></h3>
      <p><?php _e('You have not been charged. You can try again whenever you like.', 'starter'); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_pay_is_promo($al_order) ? antique_promote_url($al_order['fk_i_item_id']) : antique_plans_url()); ?>"><?php _e('Try again', 'starter'); ?></a>
    <?php } else { ?>
      <h3><?php echo osc_esc_html(antique_order_status_label($al_s)); ?></h3>
      <p><?php echo osc_esc_html($al_order['s_message'] ? $al_order['s_message'] : __('The payment did not go through. You have not been charged.', 'starter')); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_pay_is_promo($al_order) ? antique_promote_url($al_order['fk_i_item_id']) : antique_plans_url()); ?>"><?php _e('Try again', 'starter'); ?></a>
    <?php } ?>
  </div>
<?php } ?>

<?php if ($al_rows) { ?>
  <div class="al-table-wrap">
    <table class="al-pay-table">
      <thead><tr>
        <th><?php _e('Date', 'starter'); ?></th>
        <th><?php _e('Order', 'starter'); ?></th>
        <th><?php _e('Purchase', 'starter'); ?></th>
        <th><?php _e('Amount', 'starter'); ?></th>
        <th><?php _e('Status', 'starter'); ?></th>
      </tr></thead>
      <tbody>
        <?php foreach ($al_rows as $al_r) { ?>
          <tr>
            <td><?php echo osc_esc_html(date('j M Y', strtotime($al_r['dt_created']))); ?></td>
            <td><?php echo osc_esc_html($al_r['s_ref']); ?></td>
            <td><?php echo osc_esc_html(antique_pay_order_label($al_r, false)); ?>
              <?php if ($al_r['s_title']) { ?><small><?php echo osc_esc_html((string)$al_r['s_title']); ?></small><?php } ?></td>
            <td><?php echo osc_esc_html(antique_pay_money($al_r['d_amount'], $al_r['s_currency'])); ?></td>
            <td><span class="al-status is-<?php echo osc_esc_html($al_r['s_status']); ?>"><?php echo osc_esc_html(antique_order_status_label($al_r['s_status'])); ?></span></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
<?php } elseif (!$al_order) { ?>
  <div class="al-empty-state">
    <h2><?php _e('No payments yet', 'starter'); ?></h2>
    <p><?php _e('Promote a listing to get it in front of more buyers, or choose a seller plan for more listings and lower promotion prices.', 'starter'); ?></p>
    <a class="al-btn al-btn-ink" href="<?php echo osc_user_list_items_url(); ?>"><?php _e('Go to my listings', 'starter'); ?></a>
  </div>
<?php } ?>
