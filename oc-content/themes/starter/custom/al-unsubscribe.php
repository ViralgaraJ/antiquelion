<?php
  // Opened from an email's unsubscribe link. Asks to confirm with a button (POST),
  // because mail scanners open links in emails and must not unsubscribe anyone.
  $al_uid = (int)Params::getParam('u');
  $al_topic = (string)Params::getParam('t');
  $al_key = (string)Params::getParam('k');
  $al_valid = antique_notify_check($al_uid, $al_topic, $al_key) && User::newInstance()->findByPrimaryKey($al_uid);
  $al_done = $al_valid && Params::getParam('done') == '1';
  $al_topics = antique_notify_topics();
  $al_what = $al_topic === 'all' ? __('all optional emails', 'starter') : ($al_valid ? sprintf(__('%s emails', 'starter'), mb_strtolower($al_topics[$al_topic][0])) : '');
?>
<div class="al-wrap al-unsub">
  <div class="al-panel">
    <?php if (!$al_valid) { ?>
      <h1><?php _e('This link does not work', 'starter'); ?></h1>
      <p><?php echo sprintf(__('The unsubscribe link is incomplete or out of date. Sign in and open <a href="%s">Email settings</a> to choose which emails you get.', 'starter'), antique_notify_url()); ?></p>
    <?php } elseif ($al_done) { ?>
      <h1><?php _e('You are unsubscribed', 'starter'); ?></h1>
      <p><?php echo sprintf(__('We will not send you %s any more.', 'starter'), osc_esc_html($al_what)); ?></p>
      <p><?php _e('We will still email you about a deal or your account, such as an auction you won or an accepted offer.', 'starter'); ?></p>
      <p class="al-unsub-actions"><a class="al-btn al-btn-outline" href="<?php echo antique_notify_url(); ?>"><?php _e('Change email settings', 'starter'); ?></a></p>
    <?php } else { ?>
      <h1><?php _e('Unsubscribe', 'starter'); ?></h1>
      <p><?php echo sprintf(__('Stop sending me %s?', 'starter'), '<strong>' . osc_esc_html($al_what) . '</strong>'); ?></p>
      <form method="post" action="<?php echo osc_esc_html(antique_unsubscribe_post_url($al_uid, $al_topic)); ?>" class="al-unsub-actions">
        <input type="hidden" name="confirm" value="1" />
        <button type="submit" class="al-btn al-btn-brass"><?php _e('Unsubscribe', 'starter'); ?></button>
        <?php if ($al_topic !== 'all') { ?>
          <a class="al-btn al-btn-outline" href="<?php echo osc_esc_html(antique_unsubscribe_url($al_uid, 'all')); ?>"><?php _e('Unsubscribe from all optional emails', 'starter'); ?></a>
        <?php } ?>
      </form>
    <?php } ?>
  </div>
</div>
