<?php
  // INTERNAL USE FOR AJAX. IF NO AJAX, SHOW CONTACT PAGE
  if (isset($_GET['ajaxRequest']) && $_GET['ajaxRequest'] == '1') {
    error_reporting(0);
    ob_clean();
    osc_current_web_theme_path('ajax.php');
    exit;
  }
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
</head>
<body id="body-contact" class="al-contact-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-contact">
    <section class="al-panel">
      <h2 class="al-panel-title"><?php _e('Send us a message', 'starter'); ?></h2>
      <form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact" class="al-form">
        <input type="hidden" name="page" value="contact" />
        <input type="hidden" name="action" value="contact_post" />
        <div class="al-form-row al-form-2">
          <label><span><?php _e('Your name', 'starter'); ?></span><input type="text" name="yourName" id="yourName" value="<?php echo osc_esc_html(osc_is_web_user_logged_in() ? osc_logged_user_name() : ''); ?>" autocomplete="name" /></label>
          <label><span><?php _e('Email', 'starter'); ?> <em>*</em></span><input type="email" name="yourEmail" id="yourEmail" required value="<?php echo osc_esc_html(osc_is_web_user_logged_in() ? osc_logged_user_email() : ''); ?>" autocomplete="email" /></label>
        </div>
        <label class="al-form-full"><span><?php _e('Subject', 'starter'); ?> <em>*</em></span><input type="text" name="subject" id="subject" required value="" /></label>
        <label class="al-form-full"><span><?php _e('Message', 'starter'); ?> <em>*</em></span><textarea id="message" name="message" rows="6" required></textarea></label>
        <?php osc_run_hook('contact_form'); ?>
        <?php osc_show_recaptcha(); ?>
        <button type="submit" class="al-btn al-btn-brass"><?php _e('Send message', 'starter'); ?></button>
      </form>
    </section>

    <aside class="al-contact-side">
      <div class="al-panel">
        <h3 class="al-panel-title"><?php _e('Before you write', 'starter'); ?></h3>
        <ul class="al-help-list">
          <li><strong><?php _e('Questions about an item?', 'starter'); ?></strong> <?php _e('Use "Message seller" on the listing. Sellers answer directly.', 'starter'); ?></li>
          <li><strong><?php _e('Bidding', 'starter'); ?></strong> <?php _e('Sign in, open a live auction and enter at least the minimum bid shown.', 'starter'); ?></li>
          <li><strong><?php _e('Selling', 'starter'); ?></strong> <?php _e('Listing is free. Choose a fixed price or run an auction.', 'starter'); ?></li>
          <li><strong><?php _e('Report a listing', 'starter'); ?></strong> <?php _e('Send us the listing link and what is wrong. We review every report.', 'starter'); ?></li>
        </ul>
      </div>
    </aside>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
