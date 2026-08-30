<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
  <head>
    <?php osc_current_web_theme_path('head.php') ; ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
    <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
  </head>
  <body id="body-item-contact">
    <?php osc_current_web_theme_path('header.php') ; ?>
    <div class="content user_forms">
      <div id="contact" class="inner">
        <h1><?php _e('Contact seller', 'zara'); ?></h1>
        <ul id="error_list"></ul>
        <?php ContactForm::js_validation(); ?>
        <form action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" id="contact_form">
          <fieldset>
            <?php ContactForm::primary_input_hidden() ; ?>
            <?php ContactForm::action_hidden() ; ?>
            <?php ContactForm::page_hidden() ; ?>
            <label><?php _e('To (seller)', 'zara'); ?>: <?php echo osc_item_contact_name() ;?></label><br />
            <label><?php _e('Listing', 'zara'); ?>: <a href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title() ; ?></a></label><br />
            <?php if(osc_is_web_user_logged_in()) { ?>
              <input type="hidden" name="yourName" value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
              <input type="hidden" name="yourEmail" value="<?php echo osc_logged_user_email();?>" />
            <?php } else { ?>
              <label for="yourName"><?php _e('Your name', 'zara'); ?></label> <?php ContactForm::your_name(); ?><br />
              <label for="yourEmail"><?php _e('Your e-mail address', 'zara'); ?></label> <?php ContactForm::your_email(); ?><br />
            <?php }; ?>
            <label for="phoneNumber"><?php _e('Phone number', 'zara'); ?> (<?php _e('optional', 'zara'); ?>)</label> <?php ContactForm::your_phone_number(); ?><br />
            <label for="message"><?php _e('Message', 'zara'); ?></label> <?php ContactForm::your_message(); ?><br />
            <?php osc_show_recaptcha(); ?>
            <button type="submit"><?php _e('Send message', 'zara'); ?></button>
          </fieldset>
        </form>
      </div>
    </div>
    <?php osc_current_web_theme_path('footer.php') ; ?>
  </body>
</html>