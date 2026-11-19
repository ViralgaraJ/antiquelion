<?php
  // Seller plans: public comparison; buying needs a seller account (antique_plan_buy_post)
  $al_uid = osc_is_web_user_logged_in() ? (int)osc_logged_user_id() : 0;
  $al_plans = antique_member_plans();
  $al_pack = antique_member_pack();
  $al_cur = antique_pay_currency();
  $al_can_buy = $al_uid > 0 && antique_member_on() && antique_pay_enabled();
  $al_current = $al_uid ? antique_member_plan($al_uid) : '';
  $al_token = osc_csrfguard_generate_token();
  $al_free = antique_member_free_limit();
  $al_price = function ($usd) use ($al_cur) { list($a, $c) = antique_pay_quote($usd, $al_cur); return antique_pay_money($a, $c); };
  $al_promo = antique_promo_products();
  $al_from = min($al_promo['bump']['prices']);
?>
<div class="al-wrap al-plans">
  <header class="al-plans-head">
    <h1><?php _e('Sell more with a seller plan', 'starter'); ?></h1>
    <p><?php echo sprintf(__('Every seller can keep up to %d listings online for free. Plans give you room for more, a badge buyers notice, and lower prices on promotions.', 'starter'), $al_free); ?></p>
  </header>

  <?php if (!antique_member_on()) { ?>
    <div class="al-empty-state">
      <h2><?php _e('Seller plans are coming soon', 'starter'); ?></h2>
      <p><?php _e('Listing on AntiqueLion is free while we prepare them.', 'starter'); ?></p>
      <a class="al-btn al-btn-ink" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
    </div>
  <?php } else { ?>

    <?php if ($al_uid && antique_is_seller($al_uid)) {
      $al_limit = antique_member_limit($al_uid);
      $al_used = antique_member_live_count($al_uid);
      $al_end = $al_current !== '' ? antique_member_plan_end($al_uid, $al_current) : null;
      $al_packs = antique_member_pack_slots($al_uid);
    ?>
      <section class="al-panel al-plan-status" aria-label="<?php echo osc_esc_html(__('Your plan', 'starter')); ?>">
        <div>
          <span class="al-plan-status-label"><?php _e('Your plan', 'starter'); ?></span>
          <strong><?php echo osc_esc_html($al_current !== '' ? $al_plans[$al_current]['name'] : __('Free', 'starter')); ?></strong>
          <?php if ($al_end) { ?><span><?php echo osc_esc_html(sprintf(__('Paid until %s', 'starter'), date('j M Y', strtotime($al_end)))); ?></span><?php } ?>
        </div>
        <div>
          <span class="al-plan-status-label"><?php _e('Live listings', 'starter'); ?></span>
          <strong><?php echo $al_limit === 0 ? sprintf(__('%d (unlimited)', 'starter'), $al_used) : sprintf(__('%1$d of %2$d', 'starter'), $al_used, $al_limit); ?></strong>
          <?php if ($al_packs) { ?><span><?php echo osc_esc_html(sprintf(__('Includes %d from listing packs', 'starter'), $al_packs)); ?></span><?php } ?>
        </div>
        <a class="al-btn al-btn-outline" href="<?php echo osc_esc_html(antique_payments_url()); ?>"><?php _e('Payment history', 'starter'); ?></a>
      </section>
    <?php } ?>

    <div class="al-plan-grid">
      <article class="al-plan-card<?php echo $al_current === '' && $al_uid ? ' is-current' : ''; ?>">
        <h2><?php _e('Free', 'starter'); ?></h2>
        <p class="al-plan-desc"><?php _e('For trying AntiqueLion and occasional sales.', 'starter'); ?></p>
        <div class="al-plan-price"><strong><?php echo osc_esc_html(antique_pay_money(0, $al_cur)); ?></strong></div>
        <ul class="al-plan-perks">
          <li><?php echo sprintf(_n('%d live listing', '%d live listings', $al_free, 'starter'), $al_free); ?></li>
          <li><?php _e('Fixed-price and auction listings', 'starter'); ?></li>
          <li><?php _e('Messages, offers and your shop page', 'starter'); ?></li>
          <li><?php echo osc_esc_html(sprintf(__('Promotions from %s', 'starter'), $al_price($al_from))); ?></li>
        </ul>
        <?php if (!$al_uid) { ?><a class="al-btn al-btn-outline" href="<?php echo osc_item_post_url(); ?>"><?php _e('Start selling free', 'starter'); ?></a><?php } ?>
      </article>

      <?php foreach ($al_plans as $al_key => $al_p) { $al_is = $al_current === $al_key; ?>
        <article class="al-plan-card is-<?php echo $al_key; ?><?php echo $al_is ? ' is-current' : ''; ?>">
          <?php if ($al_key === 'pro') { ?><span class="al-plan-ribbon"><?php _e('Best for dealers', 'starter'); ?></span><?php } ?>
          <h2><?php echo osc_esc_html($al_p['name']); ?> <?php if ($al_is) { ?><em><?php _e('Your plan', 'starter'); ?></em><?php } ?></h2>
          <p class="al-plan-desc"><?php echo osc_esc_html($al_p['desc']); ?></p>
          <div class="al-plan-price"><strong><?php echo osc_esc_html($al_price($al_p['prices'][30])); ?></strong> <span><?php _e('per month', 'starter'); ?></span></div>
          <ul class="al-plan-perks">
            <li><?php echo $al_p['limit'] === 0 ? __('Unlimited live listings', 'starter') : sprintf(__('%d live listings', 'starter'), $al_p['limit']); ?></li>
            <li><?php echo sprintf(__('%d%% off every promotion', 'starter'), $al_p['discount']); ?></li>
            <li><?php echo sprintf(__('%s badge on your listings and shop', 'starter'), '<span class="al-member-badge is-' . $al_key . '">' . osc_esc_html($al_p['badge']) . '</span>'); ?></li>
            <li><?php _e('Everything in Free', 'starter'); ?></li>
          </ul>
          <?php if ($al_can_buy) { ?>
            <form method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_plan_buy')); ?>" class="al-plan-form">
              <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" />
              <input type="hidden" name="plan" value="<?php echo $al_key; ?>" />
              <fieldset>
                <legend class="al-sr-only"><?php _e('How long', 'starter'); ?></legend>
                <?php $al_first = true; foreach ($al_p['prices'] as $al_days => $al_usd) {
                  $al_save = round((1 - $al_usd / ($al_p['prices'][30] * $al_days / 30)) * 100); ?>
                  <label class="al-promo-opt">
                    <input type="radio" name="days" value="<?php echo (int)$al_days; ?>"<?php echo $al_first ? ' checked' : ''; ?> />
                    <span><?php echo osc_esc_html(antique_member_period($al_days)); ?><?php if ($al_save >= 5) { ?> <small><?php echo sprintf(__('save %d%%', 'starter'), $al_save); ?></small><?php } ?></span>
                    <strong><?php echo osc_esc_html($al_price($al_usd)); ?></strong>
                  </label>
                <?php $al_first = false; } ?>
              </fieldset>
              <button type="submit" class="al-btn <?php echo $al_key === 'pro' ? 'al-btn-brass' : 'al-btn-ink'; ?>"><?php echo $al_is ? __('Add more time', 'starter') : sprintf(__('Get %s', 'starter'), osc_esc_html($al_p['name'])); ?></button>
              <?php if ($al_current !== '' && !$al_is) { ?><p class="al-plan-note"><?php _e('Switching plans starts the new one today; time left on your current plan is not carried over.', 'starter'); ?></p><?php } ?>
            </form>
          <?php } elseif (!$al_uid) { ?>
            <a class="al-btn al-btn-ink" href="<?php echo osc_esc_html(antique_login_back_url(antique_plans_url())); ?>"><?php _e('Sign in to choose', 'starter'); ?></a>
          <?php } ?>
        </article>
      <?php } ?>
    </div>

    <section class="al-panel al-pack">
      <div>
        <h2><?php _e('Need just a few more?', 'starter'); ?></h2>
        <p><?php echo osc_esc_html(sprintf(__('A listing pack adds %1$d live listings for %2$d days to any plan, for %3$s.', 'starter'), $al_pack['slots'], ANTIQUE_MEMBER_PACK_DAYS, $al_price($al_pack['usd']))); ?></p>
      </div>
      <?php if ($al_can_buy) { ?>
        <form method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_plan_buy')); ?>">
          <input type="hidden" name="octoken" value="<?php echo $al_token; ?>" />
          <input type="hidden" name="plan" value="pack" />
          <button type="submit" class="al-btn al-btn-outline"><?php echo osc_esc_html(sprintf(__('Buy a pack for %s', 'starter'), $al_price($al_pack['usd']))); ?></button>
        </form>
      <?php } ?>
    </section>

    <section class="al-plans-faq">
      <h2><?php _e('Questions', 'starter'); ?></h2>
      <h3><?php _e('What counts as a live listing?', 'starter'); ?></h3>
      <p><?php _e('A listing that buyers can see: not sold, reserved, expired or waiting for approval. Marking an item sold frees its place straight away.', 'starter'); ?></p>
      <h3><?php _e('What happens when my plan ends?', 'starter'); ?></h3>
      <p><?php echo sprintf(__('We email you %d days before. Afterwards your listings stay online, but you cannot add new ones while you have more than the free plan allows.', 'starter'), ANTIQUE_MEMBER_REMIND_DAYS); ?></p>
      <h3><?php _e('Does a plan renew by itself?', 'starter'); ?></h3>
      <p><?php _e('No. You pay once for the time you choose, so there is nothing to cancel.', 'starter'); ?></p>
      <h3><?php _e('How do I pay?', 'starter'); ?></h3>
      <p><?php echo sprintf(__('By card on PayHere\'s secure page, in %s. See our <a href="%s">terms</a> for refunds.', 'starter'), osc_esc_html($al_cur), antique_info_page_url('al-terms')); ?></p>
      <?php if (!antique_pay_live()) { ?><p><strong><?php _e('Test mode: no real money is charged.', 'starter'); ?></strong></p><?php } ?>
    </section>
  <?php } ?>
</div>
