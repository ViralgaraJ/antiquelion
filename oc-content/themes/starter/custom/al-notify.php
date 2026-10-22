<?php
  $al_uid = (int)osc_logged_user_id();
  $al_off = antique_notify_off_topics($al_uid);
?>
<div class="al-account-head">
  <h2><?php _e('Email settings', 'starter'); ?></h2>
</div>
<p class="al-account-intro"><?php echo sprintf(__('Choose which emails we send to %s.', 'starter'), '<strong>' . osc_esc_html(osc_logged_user_email()) . '</strong>'); ?></p>

<form class="al-form al-notify-form" method="post" action="<?php echo osc_esc_html(osc_ajax_hook_url('al_notify')); ?>">
  <input type="hidden" name="octoken" value="<?php echo osc_csrfguard_generate_token(); ?>" />

  <section class="al-panel">
    <h3 class="al-panel-title"><?php _e('Optional emails', 'starter'); ?></h3>
    <ul class="al-notify-list">
      <?php foreach (antique_notify_topics() as $al_t => $al_info) { ?>
        <li>
          <label class="al-check">
            <input type="checkbox" name="topics[]" value="<?php echo osc_esc_html($al_t); ?>"<?php echo in_array($al_t, $al_off, true) ? '' : ' checked'; ?> />
            <span><strong><?php echo osc_esc_html($al_info[0]); ?></strong><small><?php echo osc_esc_html($al_info[1]); ?></small></span>
          </label>
        </li>
      <?php } ?>
    </ul>
  </section>

  <section class="al-panel">
    <h3 class="al-panel-title"><?php _e('Always sent', 'starter'); ?></h3>
    <p class="al-notify-note"><?php _e('We always email you about a deal or your account: an auction you won or sold, an accepted offer, seller verification, and password or security messages.', 'starter'); ?></p>
    <p class="al-notify-note"><?php echo sprintf(__('Emails for saved searches are managed under <a href="%s">Saved searches</a>.', 'starter'), osc_user_alerts_url()); ?></p>
  </section>

  <div class="al-shop-save">
    <button type="submit" class="al-btn al-btn-brass"><?php _e('Save email settings', 'starter'); ?></button>
  </div>
</form>
