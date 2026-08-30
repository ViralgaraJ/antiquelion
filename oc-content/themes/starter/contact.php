<?php 
  // INTERNAL USE FOR AJAX. IF NO AJAX, SHOW CONTACT PAGE
  if(isset($_GET['ajaxRequest']) && $_GET['ajaxRequest'] == '1') {
    error_reporting(0);
    ob_clean();
    osc_current_web_theme_path('ajax.php');
    exit;
  }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>
<body id="body-contact">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="modern-contact-container">
    <div class="modern-contact-card">
      <div class="contact-card-header">
        <div class="contact-header-icon"><i class="fa fa-envelope-o"></i></div>
        <h2>Contact Us</h2>
        <p class="contact-header-sub">Have questions or feedback? Fill out the form below and we will respond promptly.</p>
      </div>

      <form action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" id="contact">
        <input type="hidden" name="page" value="contact" />
        <input type="hidden" name="action" value="contact_post" />
        <fieldset>
        <div class="contact-form-grid">
          <div class="form-group half-width">
            <label for="yourName">Your Name</label> 
            <div class="input-box">
              <i class="fa fa-user"></i>
              <input type="text" name="yourName" id="yourName" value="" placeholder="Enter your full name" />
            </div>
          </div>

          <div class="form-group half-width">
            <label for="yourEmail">Your E-mail Address <span class="req">*</span></label>
            <div class="input-box">
              <i class="fa fa-envelope"></i>
              <input type="text" name="yourEmail" id="yourEmail" value="" placeholder="Enter your email address" />
            </div>
          </div>

          <div class="form-group full-width">
            <label for="subject">Subject <span class="req">*</span></label>
            <div class="input-box">
              <i class="fa fa-pencil"></i>
              <input type="text" name="subject" id="subject" value="" placeholder="What is your inquiry about?" />
            </div>
          </div>

          <div class="form-group full-width">
            <label for="message">Message <span class="req">*</span></label>
            <div class="input-box textarea-box">
              <textarea id="message" name="message" rows="4" placeholder="How can we help you? Write your message here..."></textarea>
            </div>
          </div>

          <div class="form-group full-width submit-group">
            <button type="submit" class="contact-submit-btn">
              <i class="fa fa-paper-plane"></i>
              <span>Send Message</span>
            </button>
          </div>
        </div>
        </fieldset>
      </form>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>