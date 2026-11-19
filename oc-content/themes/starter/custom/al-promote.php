<?php
  $al_uid = (int)osc_logged_user_id();
  $al_item_id = (int)Params::getParam('item');
  $al_item = $al_item_id ? Item::newInstance()->findByPrimaryKey($al_item_id) : null;
  $al_block = antique_promo_block_reason($al_item, $al_uid);
  $al_products = antique_promo_products();
  $al_cur = antique_pay_currency();
  $al_map = antique_promo_active_map();
  $al_running = isset($al_map[$al_item_id]) ? $al_map[$al_item_id] : array();
?>
<div class="al-account-head">
  <h2><?php _e('Promote your listing', 'starter'); ?></h2>
  <a class="al-btn al-btn-outline" href="<?php echo osc_esc_html(antique_payments_url()); ?>"><?php _e('Payment history', 'starter'); ?></a>
</div>

<?php if ($al_block !== '') { ?>
  <div class="al-empty-state">
    <h2><?php echo osc_esc_html($al_block); ?></h2>
    <a class="al-btn al-btn-ink" href="<?php echo osc_user_list_items_url(); ?>"><?php _e('Go to my listings', 'starter'); ?></a>
  </div>
<?php } elseif (!antique_pay_enabled()) { ?>
  <div class="al-empty-state">
    <h2><?php _e('Promotions are not available yet', 'starter'); ?></h2>
    <p><?php _e('Online payments are being set up. Please check back soon.', 'starter'); ?></p>
  </div>
<?php } else { ?>
  <?php $al_photo = ItemResource::newInstance()->getResource($al_item_id); ?>
  <div class="al-panel al-promo-item">
    <?php if ($al_photo) { ?><img src="<?php echo osc_esc_html(osc_base_url() . $al_photo['s_path'] . $al_photo['pk_i_id'] . '_thumbnail.' . $al_photo['s_extension']); ?>" alt="" width="72" height="72" /><?php } ?>
    <div>
      <strong><?php echo osc_esc_html($al_item['s_title']); ?></strong>
      <?php if ($al_running) { ?>
        <span><?php
          $al_names = array();
          foreach ($al_running as $al_t) { $al_end = antique_promo_end($al_item_id, $al_t); $al_names[] = $al_products[$al_t]['name'] . ($al_end ? ' ' . sprintf(__('until %s', 'starter'), date('j M Y', strtotime($al_end))) : ''); }
          echo osc_esc_html(sprintf(__('Running now: %s. Buying the same promotion again adds the days on top.', 'starter'), implode(', ', $al_names)));
        ?></span>
      <?php } else { ?>
        <span><?php _e('Pick a promotion. It starts as soon as your payment goes through.', 'starter'); ?></span>
      <?php } ?>
    </div>
  </div>

  <form class="al-form al-promo-form" method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_promote')); ?>">
    <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />
    <input type="hidden" name="item" value="<?php echo $al_item_id; ?>" />
    <div class="al-promo-grid">
      <?php $al_first = true; foreach ($al_products as $al_key => $al_p) { ?>
        <fieldset class="al-promo-card al-promo-<?php echo $al_key; ?>">
          <legend><?php echo osc_esc_html($al_p['name']); ?><?php if (in_array($al_key, $al_running, true)) { ?> <em><?php _e('Running', 'starter'); ?></em><?php } ?></legend>
          <p><?php echo osc_esc_html($al_p['desc']); ?></p>
          <?php foreach ($al_p['prices'] as $al_days => $al_usd) { list($al_amt, $al_c) = antique_pay_quote($al_usd, $al_cur); ?>
            <label class="al-promo-opt">
              <input type="radio" name="al_pick" value="<?php echo $al_key . ':' . (int)$al_days; ?>" required<?php echo $al_first ? ' checked' : ''; ?> />
              <span><?php echo sprintf(_n('%d day', '%d days', $al_days, 'starter'), $al_days); ?></span>
              <strong><?php echo osc_esc_html(antique_pay_money($al_amt, $al_c)); ?></strong>
            </label>
          <?php $al_first = false; } ?>
        </fieldset>
      <?php } ?>
    </div>
    <input type="hidden" name="product" value="" />
    <input type="hidden" name="days" value="" />
    <div class="al-promo-pay">
      <button type="submit" class="al-btn al-btn-brass"><?php _e('Pay securely with PayHere', 'starter'); ?></button>
      <p><?php echo sprintf(__('Prices in %s. You pay by card on PayHere\'s secure page; AntiqueLion never sees your card details.', 'starter'), osc_esc_html($al_cur)); ?>
        <?php if (!antique_pay_live()) { ?><br><strong><?php _e('Test mode: no real money is charged.', 'starter'); ?></strong><?php } ?></p>
    </div>
  </form>
  <script>
    (function () {
      var form = document.querySelector('.al-promo-form');
      form.addEventListener('submit', function () {
        var pick = form.querySelector('input[name="al_pick"]:checked');
        if (!pick) { return; }
        var parts = pick.value.split(':');
        form.elements.product.value = parts[0];
        form.elements.days.value = parts[1];
        form.querySelector('button[type="submit"]').disabled = true;
      });
    })();
  </script>
<?php } ?>
