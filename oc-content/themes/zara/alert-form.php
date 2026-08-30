<script type="text/javascript">
$(document).ready(function(){
  if (($(window).width()) <= 767) {
    var alert_close_btn = true;
  } else {
    var alert_close_btn = false;
  }

  $(".sub_button").click(function(){
    $.post('<?php echo osc_base_url(true); ?>', {email:$("#alert_email").val(), userid:$("#alert_userId").val(), alert:$("#alert").val(), page:"ajax", action:"alerts"}, 
      function(data){
        if(data==1) { 
          if (!!$.prototype.fancybox) {
            $.fancybox({
              'padding':  0,
              'width':    640,
              'minHeight': 100,
              'height':   180,
              'autoSize': false,
              'autoDimensions': false,
              'closeBtn' : alert_close_btn,
              'wrapCSS':  'alert-func',
              'content':  '<div id="alert-ok" class="fw-box alert-messages">' + $('.fw-box#alert-ok').html() + '</div>'
            });
          }
        } else if(data==-1) { 
          if (!!$.prototype.fancybox) {
            $.fancybox({
              'padding':  0,
              'width':    640,
              'minHeight': 100,
              'height':   180,
              'autoSize': false,
              'autoDimensions': false,
              'closeBtn' : alert_close_btn,
              'wrapCSS':  'alert-func',
              'content':  '<div id="alert-email" class="fw-box alert-messages">' + $('.fw-box#alert-email').html() + '</div>'
            });
          }
        } else { 
          if (!!$.prototype.fancybox) {
            $.fancybox({
              'padding':  0,
              'width':    640,
              'minHeight': 100,
              'height':   180,
              'autoSize': false,
              'autoDimensions': false,
              'closeBtn' : alert_close_btn,
              'wrapCSS':  'alert-func',
              'content':  '<div id="alert-error" class="fw-box alert-messages">' + $('.fw-box#alert-error').html() + '</div>'
            });
          }
        };
    });
    return false;
  });
});
</script>

<div id="n-block" class="block<?php if(osc_is_web_user_logged_in()) { ?> logged_user<?php } ?>">
  <div class="head sc-click"> 
    <h4><?php _e('Subscribe to this search', 'zara'); ?></h4> 
  </div>
  
  <div class="n-wrap sc-block">
    <form action="<?php echo osc_base_url(true); ?>" method="post" name="sub_alert" id="sub_alert">
      <?php AlertForm::page_hidden(); ?>
      <?php AlertForm::alert_hidden(); ?>

      <?php if(osc_is_web_user_logged_in()) { ?>
        <?php AlertForm::user_id_hidden(); ?>
        <?php AlertForm::email_hidden(); ?>

      <?php } else { ?>
        <?php AlertForm::user_id_hidden(); ?>
        <?php AlertForm::email_text(); ?>
      <?php }; ?>

      <?php if(osc_is_web_user_logged_in()) { ?><div class="alert-logged"><?php } ?>
      <button class="button orange-button round2 sub_button"><?php _e('Subscribe', 'zara'); ?></button>
      <?php if(osc_is_web_user_logged_in()) { ?></div><?php } ?>
    </form>
  </div>
    
  <div class="under not767">
    <div class="row"><?php _e('You will get emails about', 'zara'); ?>:</div>
    <div class="row"><i class="fa fa-tag"></i> <?php _e('New products matching your criteria', 'zara'); ?></div>
    <div class="row"><i class="fa fa-ban"></i> <?php _e('No spam guarantee', 'zara'); ?></div>
  </div>

  <div id="footer-share">
    <div class="text">
      <span class="facebook"><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo osc_base_url(); ?>" title="<?php echo osc_esc_html(__('Share us on Facebook', 'zara')); ?>" target="_blank"><i class="fa fa-facebook"></i></a></span>
      <span class="pinterest"><a href="https://pinterest.com/pin/create/button/?url=<?php echo osc_base_url(); ?>/oc-content/themes/zara/images/logo.jpg&media=<?php echo osc_base_url(); ?>&description=" title="<?php echo osc_esc_html(__('Share us on Pinterest', 'zara')); ?>" target="_blank"><i class="fa fa-pinterest"></i></a></span>
      <span class="twitter"><a href="https://twitter.com/home?status=<?php echo osc_base_url(); ?>%20-%20<?php _e('your', 'zara'); ?>%20<?php _e('classifieds', 'zara'); ?>" title="<?php echo osc_esc_html(__('Tweet us', 'zara')); ?>" target="_blank"><i class="fa fa-twitter"></i></a></span>
      <span class="google-plus"><a href="https://plus.google.com/share?url=<?php echo osc_base_url(); ?>" title="<?php echo osc_esc_html(__('Share us on Google+', 'zara')); ?>" target="_blank"><i class="fa fa-google-plus"></i></a></span>
      <span class="linkedin"><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo osc_base_url(); ?>&title=<?php echo osc_esc_html(__('My', 'zara')); ?>%20<?php echo osc_esc_html(__('classifieds', 'zara')); ?>&summary=&source=" title="<?php echo osc_esc_html(__('Share us on LinkedIn', 'zara')); ?>" target="_blank"><i class="fa fa-linkedin"></i></a></span>
    </div>
  </div>
</div>


<?php if(osc_is_web_user_logged_in()) { ?>
  <div id="n-block-extra" class="some-block">
    <h4><?php _e('Account', 'zara'); ?></h4>

    <div class="text">
      <span><a href="<?php echo osc_user_dashboard_url(); ?>" class="elem"><?php _e('My account', 'zara'); ?></a></span>
      <span><a href="<?php echo osc_user_alerts_url(); ?>" class="elem"><?php _e('My alerts', 'zara'); ?></a></span>
      <span><a href="<?php echo osc_user_profile_url(); ?>" class="elem"><?php _e('My personal info', 'zara'); ?></a></span>
      <span><a href="<?php echo osc_user_list_items_url(); ?>" class="elem"><?php _e('My listings', 'zara'); ?></a></span>
      <span><a href="<?php echo osc_user_public_profile_url(osc_logged_user_id()); ?>" class="elem"><?php _e('My public profile', 'zara'); ?></a></span>
      <span><a href="<?php echo osc_user_logout_url(); ?>" class="elem"><?php _e('Log me out', 'zara'); ?></a></span>
    </div>
  </div>
<?php } ?>



<!-- ALERT MESSAGES -->
<div class="alert-fancy-boxes">
  <div id="alert-ok" class="fw-box">
    <div class="head">
      <h2><?php _e('Subscribe to alert', 'zara'); ?></h2>
      <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
    </div>

    <div class="left">
      <img src="<?php echo osc_base_url(); ?>oc-content/themes/zara/images/alert-ok.jpg" />
    </div>

    <div class="middle">
      <div class="a-message">
        <span class="first"><?php _e('You have successfully subscribed to alert!', 'zara'); ?></span>
        <span><?php _e('You will recieve notification to your email once there is new listing that match your search criteria.', 'zara'); ?></span>
      </div>
    </div>
  </div>

  <div id="alert-email" class="fw-box">
    <div class="head">
      <h2><?php _e('Subscribe to alert', 'zara'); ?></h2>
      <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
    </div>

    <div class="left">
      <img src="<?php echo osc_base_url(); ?>oc-content/themes/zara/images/alert-error.jpg" />
    </div>

    <div class="middle">
      <div class="a-message">
        <span class="first"><?php _e('There was error during subscription process!', 'zara'); ?></span>
        <span><?php _e('You have entered email address in incorrect format or you did not entered email address.', 'zara'); ?></span>
      </div>
    </div>
  </div>

  <div id="alert-error" class="fw-box">
    <div class="head">
      <h2><?php _e('Subscribe to alert', 'zara'); ?></h2>
      <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
    </div>

    <div class="left">
      <img src="<?php echo osc_base_url(); ?>oc-content/themes/zara/images/alert-error.jpg" />
    </div>

    <div class="middle">
      <div class="a-message">
        <span class="first"><?php _e('There was error during subscription process!', 'zara'); ?></span>
        <span><?php _e('You have already subscribed to this search.', 'zara'); ?></span>
      </div>
    </div>
  </div>
</div>