<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
</head>
<body id="body-contact">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div id="contact-wrap" class="content cont_us">
    <h2 class="contact">
      <span><?php _e("Contact us", 'zara'); ?></span>
    </h2>

    <div id="contact-ins" class="inner">
      <div class="div-desc"><?php _e('If you have any problem or need some additional info about our site, do not hesitate to contact us. We will reply soon.', 'zara') ; ?></div>
      <div class="clear"></div>

      <ul id="error_list"></ul>
      <form action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact" id="contact">
        <input type="hidden" name="page" value="contact" />
        <input type="hidden" name="action" value="contact_post" />

        <?php if(osc_is_web_user_logged_in()) { ?>
          <input type="hidden" name="yourName" value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
          <input type="hidden" name="yourEmail" value="<?php echo osc_logged_user_email();?>" />
        <?php } else { ?>
          <div class="thirdd">
            <label for="yourName"><span><?php _e('Your name', 'zara'); ?></span></label> 
            <?php ContactForm::your_name() ; ?>
          </div>

          <div class="thirdd">
            <label for="yourEmail"><span><?php _e('Your e-mail address', 'zara'); ?></span><div class="req">*</div></label>
            <?php ContactForm::your_email(); ?>
          </div>
        <?php } ?>

        <div class="thirdd" id="titt">
          <label for="subject"><span><?php _e("Subject", 'zara'); ?></span><div class="req">*</div></label>
          <?php ContactForm::the_subject() ; ?>
        </div>
                      
        <?php ContactForm::your_message() ; ?>
        <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>

        <?php if(osc_recaptcha_public_key() <> '') { ?>
          <?php osc_show_recaptcha(); ?>
        <?php } else { ?>
          <div style="float:left;clear:both;width:100%;margin:15px 0 5px 0;">
            <?php osc_run_hook("anr_captcha_form_field"); ?>
          </div>
        <?php } ?>

        <button type="submit" id="blue"><?php _e('Send message', 'zara'); ?></button>
      </fieldset>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function(){
      // PLACEHOLDERS FOR CONTACT FORM
      $('#contact #yourName').attr('placeholder', '<?php echo osc_esc_js(__('Your real or user name', 'zara')); ?>');
      $('#contact #yourEmail').attr('placeholder', '<?php echo osc_esc_js(__('We can contact you back', 'zara')); ?>');
      $('#contact #subject').attr('placeholder', '<?php echo osc_esc_js(__('Summary of reason to contact us', 'zara')); ?>');
      $('#contact #message').attr('placeholder', '<?php echo osc_esc_js(__('I would like to ask you...', 'zara')); ?>');
    });
  </script>

  <?php ContactForm::js_validation() ; ?>
  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>