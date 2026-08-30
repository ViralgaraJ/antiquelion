<?php
  $is_modal = (Params::getParam('modal') == 1);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
  
  <style type="text/css">
    html {
      overflow-y: auto !important;
      overflow-x: hidden !important;
      height: 100% !important;
    }
    /* Global/Unified styles for forms in both standalone and modal views */
    body.standalone-view, body.modal-view {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
      color: #303133;
      margin: 0;
      padding: 0;
    }
    
    /* Standalone View styling */
    body.standalone-view {
      background: #f4f6f8;
    }
    body.standalone-view .fw-box {
      max-width: 680px;
      margin: 40px auto !important;
      float: none !important;
      border: 1px solid #e1e8ed;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.06);
      overflow: hidden;
      background: #fff;
    }
    body.standalone-view .fw-box .head {
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
      background: #fff;
      border-bottom: 1px solid #f0f3f6;
      padding: 24px 30px;
    }
    body.standalone-view .fw-box .middle {
      background: #fff !important;
      padding: 30px;
    }
    body.standalone-view .fw-close-button {
      display: none !important;
    }

    /* Modal View styling */
    body.modal-view {
      background: #fff !important;
      overflow-y: auto !important;
      overflow-x: hidden !important;
      height: 100% !important;
    }
    body.modal-view .fw-box {
      border: none !important;
      box-shadow: none !important;
      border-radius: 0 !important;
      background: #fff !important;
      margin: 0 !important;
      width: 100% !important;
      max-width: 100% !important;
      float: none !important;
      overflow: visible !important;
    }
    body.modal-view .fw-box .head {
      border-top-left-radius: 0 !important;
      border-top-right-radius: 0 !important;
      background: #fff !important;
      border-bottom: 1px solid #f0f3f6 !important;
      padding: 20px 24px !important;
      height: auto !important;
    }
    body.modal-view .fw-box .middle {
      background: #fff !important;
      padding: 20px 24px 30px 24px !important;
      overflow: visible !important;
    }

    /* Common layout and preview styling */
    .fw-box .head h2 {
      font-size: 20px !important;
      font-weight: 700 !important;
      color: #1f2f3d !important;
      margin: 0 !important;
    }
    .fw-box .item-sample {
      border-bottom: 1px solid #f0f3f6 !important;
      background: #fafbfc !important;
      padding: 16px 24px !important;
    }
    .fw-box .item-sample .one .text {
      padding: 2px 0 2px 16px !important;
    }
    .fw-box .item-sample .one .title {
      color: #1f2f3d !important;
      font-size: 15px !important;
      font-weight: 600 !important;
      margin-bottom: 4px !important;
    }
    .fw-box .item-sample .one .desc {
      color: #606266 !important;
      font-size: 13px !important;
      margin-bottom: 6px !important;
    }
    .fw-box .item-sample .one .price span {
      color: #d97706 !important;
      font-weight: 700 !important;
      font-size: 14px !important;
    }

    /* Elegant forms elements styling */
    .fw-box .middle .row {
      margin-bottom: 20px !important;
      float: none !important;
      width: 100% !important;
      clear: both !important;
    }
    .fw-box .middle .row label {
      display: block !important;
      font-size: 14px !important;
      font-weight: 600 !important;
      margin-bottom: 8px !important;
      color: #303133 !important;
      float: none !important;
      width: auto !important;
    }
    .fw-box .middle .row label .req,
    .fw-box .middle .row label span.req {
      color: #f56c6c !important;
      margin-left: 4px !important;
      display: inline-block !important;
      font-weight: bold !important;
    }
    .fw-box .middle .input-box {
      position: relative !important;
      margin-bottom: 0 !important;
      float: none !important;
      width: 100% !important;
      clear: both !important;
    }
    .fw-box .middle .input-box i {
      position: absolute !important;
      left: 14px !important;
      top: 50% !important;
      transform: translateY(-50%) !important;
      color: #909399 !important;
      font-size: 15px !important;
      pointer-events: none !important;
      z-index: 10 !important;
    }
    .fw-box .middle .input-box input:not([type="radio"]):not([type="checkbox"]):not([type="file"]):not([type="submit"]) {
      width: 100% !important;
      box-sizing: border-box !important;
      border: 1px solid #dcdfe6 !important;
      border-radius: 6px !important;
      padding: 12px 14px 12px 48px !important; /* spacing for icon */
      background: #f9fafc !important;
      font-size: 14px !important;
      height: 44px !important;
      line-height: 20px !important;
      color: #303133 !important;
      transition: all 0.2s ease-in-out !important;
      float: none !important;
    }
    .fw-box .middle textarea {
      width: 100% !important;
      box-sizing: border-box !important;
      border: 1px solid #dcdfe6 !important;
      border-radius: 6px !important;
      padding: 12px 14px !important;
      background: #f9fafc !important;
      font-size: 14px !important;
      height: auto !important;
      min-height: 120px !important;
      color: #303133 !important;
      transition: all 0.2s ease-in-out !important;
      resize: vertical !important;
      float: none !important;
    }
    .fw-box .middle .input-box input:focus,
    .fw-box .middle textarea:focus {
      border-color: #409eff !important;
      background: #fff !important;
      outline: none !important;
      box-shadow: 0 0 0 3px rgba(64, 158, 255, 0.1) !important;
    }

    /* Premium Button styling */
    .fw-box .middle button {
      background: #d97706 !important;
      color: #fff !important;
      border: none !important;
      padding: 12px 28px !important;
      font-size: 14px !important;
      font-weight: 600 !important;
      border-radius: 6px !important;
      cursor: pointer !important;
      transition: all 0.15s !important;
      box-shadow: 0 2px 6px rgba(29, 158, 239, 0.2) !important;
      float: none !important;
      display: inline-block !important;
      margin-top: 10px !important;
    }
    .fw-box .middle button:hover {
      background: #b45309 !important;
      box-shadow: 0 4px 10px rgba(29, 158, 239, 0.3) !important;
    }
    .fw-box .middle button:active {
      transform: translateY(1px) !important;
    }
    .fw-box .middle button:disabled {
      background: #a0cfff !important;
      cursor: not-allowed !important;
      box-shadow: none !important;
    }
  </style>
</head>


<body id="body-item-send-friend" class="fw-supporting <?php echo $is_modal ? 'modal-view' : 'standalone-view'; ?>">
  <?php if ($is_modal) { ?>
    <div style="display:none!important;"><?php osc_current_web_theme_path('header.php'); ?></div></div></div>
  <?php } else { ?>
    <?php osc_current_web_theme_path('header.php'); ?>
  <?php } ?>
  <?php 
    $content_only = Params::getParam('contentOnly');
    $type = Params::getParam('type');
    $user_id = Params::getParam('userId'); 
  ?>

  <!-- ITEM PREVIEW (CONTENT ONLY) -->
  <?php if($content_only == 1) { ?>
    <?php 
      ob_get_clean();
      require_once osc_base_path().'oc-content/themes/starter/item.php'; 
      exit;
    ?>  

  <?php } ?>

  <?php if($type == 'send_friend' || $type == '') { ?>
    <!-- SEND TO FRIEND FORM -->

    <div id="send-friend-form" class="fw-box" style="display:block;">
      <div class="head">
        <h2><?php _e('Send to friend', 'starter'); ?></h2>
        <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'starter'); ?></a>
      </div>

      <div class="item-sample">
        <a class="one" href="<?php echo osc_item_url(); ?>">
          <div class="image">
            <div class="img">
              <?php if(osc_count_item_resources()) { ?>
                <?php for( $i = 0; $i <= 0; $i++ ) { ?>
                  <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                <?php } ?>
              <?php } else { ?>
                <img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
              <?php } ?>
            </div>
          </div>

          <div class="text">
            <div class="title"><?php echo osc_item_title(); ?></div>
            <div class="desc"><?php echo osc_highlight(osc_item_description(), 80); ?></div>

            <div class="price">
              <span><?php echo antique_format_price(osc_item_price()); ?></span>
            </div>
          </div>
        </a>
      </div>

      <div class="middle">
        <h1 class="h1-error-fix"></h1>
        <ul id="error_list"></ul>

        <form target="_top" id="sendfriend" name="sendfriend" action="<?php echo osc_base_url(true); ?>" method="post">
          <fieldset>
            <input type="hidden" name="action" value="send_friend_post" />
            <input type="hidden" name="page" value="item" />
            <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" />

            <?php if(osc_is_web_user_logged_in()) { ?>
              <input type="hidden" name="yourName" value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
              <input type="hidden" name="yourEmail" value="<?php echo osc_logged_user_email();?>" />
            <?php } else { ?>
              <div class="row">
                <label for="yourName"><span><?php _e('Your name', 'starter'); ?></span><div class="req">*</div></label> 
                <div class="input-box"><i class="fa fa-user-o"></i><?php SendFriendForm::your_name(); ?></div>

                <label for="yourEmail"><span><?php _e('Your e-mail address', 'starter'); ?></span><div class="req">*</div></label>
                <div class="input-box"><i class="fa fa-envelope-o"></i><?php SendFriendForm::your_email(); ?></div>
              </div>
            <?php } ?>

            <div class="row">
              <label for="friendName"><span><?php _e("Your friend's name", 'starter'); ?></span><div class="req">*</div></label>
              <div class="input-box"><i class="fa fa-user"></i><?php SendFriendForm::friend_name(); ?></div>

              <label for="friendEmail"><span><?php _e("Your friend's e-mail address", 'starter'); ?></span><div class="req">*</div></label>
              <div class="input-box last"><i class="fa fa-envelope"></i><?php SendFriendForm::friend_email(); ?></div>
            </div>
                  
            <div class="row last">   
              <label for="message"><span><?php _e("Message", 'starter'); ?></span><div class="req">*</div></label>
              <?php SendFriendForm::your_message(); ?>
            </div>

            <?php starter_show_recaptcha(); ?>

            <button type="<?php echo (osc_get_preference('forms_ajax', 'starter_theme') == 1 ? 'button' : 'submit'); ?>" id="send-message"><?php _e('Send message', 'starter'); ?></button>
          </fieldset>
        </form>

        <?php SendFriendForm::js_validation(); ?>
      </div>
    </div>
  <?php } ?>

 

  <?php if($type == 'add_comment') { ?>
    <!-- NEW COMMENT FORM -->
    <?php if( !osc_comments_enabled() ) { ?>
      <div class="fw-box login-required-box" style="display:block;">
        <div class="head">
          <h2><?php _e('Add new comment', 'starter'); ?></h2>
          <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'starter'); ?></a>
        </div>
        <div class="middle" style="text-align: center; padding: 50px 24px;">
          <div style="font-size: 64px; color: #f56c6c; margin-bottom: 24px;"><i class="fa fa-comments-o"></i></div>
          <h3 style="font-size: 20px; font-weight: 700; color: #1f2f3d; margin-bottom: 12px;"><?php _e('Comments Disabled', 'starter'); ?></h3>
          <p style="font-size: 14px; color: #606266; margin-bottom: 30px; line-height: 22px; max-width: 320px; margin-left: auto; margin-right: auto;">
            <?php _e('Comments are currently disabled.', 'starter'); ?>
          </p>
        </div>
      </div>
    <?php } else if( osc_reg_user_post_comments() && !osc_is_web_user_logged_in() ) { ?>
      <div class="fw-box login-required-box" style="display:block;">
        <div class="head">
          <h2><?php _e('Add new comment', 'starter'); ?></h2>
          <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'starter'); ?></a>
        </div>
        <div class="middle" style="text-align: center; padding: 50px 24px;">
          <div style="font-size: 64px; color: #d97706; margin-bottom: 24px;"><i class="fa fa-lock"></i></div>
          <h3 style="font-size: 20px; font-weight: 700; color: #1f2f3d; margin-bottom: 12px;"><?php _e('Login Required', 'starter'); ?></h3>
          <p style="font-size: 14px; color: #606266; margin-bottom: 30px; line-height: 22px; max-width: 320px; margin-left: auto; margin-right: auto;">
            <?php _e('You must be logged in to post a comment on this listing.', 'starter'); ?>
          </p>
          <a target="_top" href="<?php echo osc_user_login_url(); ?>" class="def-but" style="background: #d97706; color: #fff; border: none; padding: 14px 32px; font-size: 14px; font-weight: 600; border-radius: 6px; text-decoration: none; display: inline-block; box-shadow: 0 2px 6px rgba(29, 158, 239, 0.2); transition: all 0.15s;"><?php _e('Login Now', 'starter'); ?></a>
        </div>
      </div>
    <?php } else { ?>
      <form target="_top" action="<?php echo osc_base_url(true) ; ?>" method="post" name="comment_form" id="comment_form" class="fw-box" style="display:block;">
        <input type="hidden" name="action" value="add_comment" />
        <input type="hidden" name="page" value="item" />
        <input type="hidden" name="id" value="<?php echo osc_item_id() ; ?>" />

        <fieldset>
          <div class="head">
            <h2><?php _e('Add new comment', 'starter'); ?></h2>
            <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'starter'); ?></a>
          </div>

          <div class="item-sample">
            <a class="one" href="<?php echo osc_item_url(); ?>">
              <div class="image">
                <div class="img">
                  <?php if(osc_count_item_resources()) { ?>
                    <?php for( $i = 0; $i <= 0; $i++ ) { ?>
                      <img src="<?php echo osc_resource_thumbnail_url(); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                    <?php } ?>
                  <?php } else { ?>
                    <img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" />
                  <?php } ?>
                </div>
              </div>

              <div class="text">
                <div class="title"><?php echo osc_item_title(); ?></div>
                <div class="desc"><?php echo osc_highlight(osc_item_description(), 80); ?></div>

                <div class="price">
                  <span><?php echo antique_format_price(osc_item_price()); ?></span>
                </div>
              </div>
            </a>
          </div>

          <div class="middle">
            <?php CommentForm::js_validation(); ?>
            <h1 class="h1-error-fix"></h1>
            <ul id="comment_error_list"></ul>

            <?php if(osc_is_web_user_logged_in()) { ?>
              <input type="hidden" name="authorName" value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
              <input type="hidden" name="authorEmail" value="<?php echo osc_logged_user_email();?>" />
            <?php } else { ?>
              <div class="row">
                <label for="authorName"><?php _e('Name', 'starter') ; ?></label> 
                <div class="input-box"><i class="fa fa-user"></i><?php CommentForm::author_input_text(); ?></div>
              </div>

              <div class="row">
                <label for="authorEmail"><span><?php _e('E-mail', 'starter') ; ?></span><span class="req">*</span></label> 
                <div class="input-box"><i class="fa fa-at"></i><?php CommentForm::email_input_text(); ?></div>
              </div>                  
            <?php } ?>

            <div class="row" id="last">
              <label for="title"><?php _e('Title', 'starter') ; ?></label>
              <div class="input-box"><i class="fa fa-pencil"></i><?php CommentForm::title_input_text(); ?></div>
            </div>
            
            <?php osc_run_hook('item_comment_form'); ?>
        
            <div class="row">
              <label for="body"><?php _e('Comment', 'starter') ; ?></label> 
              <?php CommentForm::body_input_textarea(); ?>
            </div>

            <div class="row">
              <button type="<?php echo (osc_get_preference('forms_ajax', 'starter_theme') == 1 ? 'button' : 'submit'); ?>" id="send-comment"><?php _e('Send comment', 'starter') ; ?></button>
            </div>
          </div>
        </fieldset>
      </form>
    <?php } ?>
  <?php } ?>




  <?php if($type == 'publicContact') { ?>
    <!-- PUBLIC PROFILE CONTACT SELLER -->
    <?php if( osc_reg_user_can_contact() && !osc_is_web_user_logged_in() ) { ?>
      <div class="fw-box login-required-box" style="display:block;">
        <div class="head">
          <h2><?php _e('Contact seller', 'starter'); ?></h2>
          <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'starter'); ?></a>
        </div>
        <div class="middle" style="text-align: center; padding: 50px 24px;">
          <div style="font-size: 64px; color: #d97706; margin-bottom: 24px;"><i class="fa fa-lock"></i></div>
          <h3 style="font-size: 20px; font-weight: 700; color: #1f2f3d; margin-bottom: 12px;"><?php _e('Login Required', 'starter'); ?></h3>
          <p style="font-size: 14px; color: #606266; margin-bottom: 30px; line-height: 22px; max-width: 320px; margin-left: auto; margin-right: auto;">
            <?php _e('You must be logged in to contact this seller.', 'starter'); ?>
          </p>
          <a target="_top" href="<?php echo osc_user_login_url(); ?>" class="def-but" style="background: #d97706; color: #fff; border: none; padding: 14px 32px; font-size: 14px; font-weight: 600; border-radius: 6px; text-decoration: none; display: inline-block; box-shadow: 0 2px 6px rgba(29, 158, 239, 0.2); transition: all 0.15s;"><?php _e('Login Now', 'starter'); ?></a>
        </div>
      </div>
    <?php } else { ?>
      <form target="_top" action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" id="contact_form_public" class="fw-box" style="display:block;">
        <input type="hidden" name="action" value="contact_post" class="nocsrf" />
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="id" value="<?php echo $user_id; ?>" />

        <div class="head">
          <h2><?php _e('Contact seller', 'starter'); ?></h2>
          <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'starter'); ?></a>
        </div>

        <div class="middle">
          <fieldset>
            <?php ContactForm::js_validation(); ?>
            <h1 class="h1-error-fix"></h1>
            <ul id="error_list"></ul>

            <?php if(!osc_is_web_user_logged_in()) { ?>
              <div class="row">
                <label for="yourName"><?php _e('Name', 'starter'); ?></label> 
                <div class="input-box"><i class="fa fa-user"></i><?php ContactForm::your_name(); ?></div>
              </div>

              <div class="row">
                <label for="yourEmail"><span><?php _e('E-mail', 'starter') ; ?></span><span class="req">*</span></label> 
                <div class="input-box"><i class="fa fa-at"></i><?php ContactForm::your_email(); ?></div>
              </div>
            <?php } ?>              

            <div class="row last">
              <label for="phoneNumber"><span><?php _e('Phone number', 'starter') ; ?></span></label>
              <div class="input-box"><i class="fa fa-phone"></i><?php ContactForm::your_phone_number(); ?></div>
            </div>

            <div class="row">
              <?php ContactForm::your_message(); ?>
            </div>

            <?php starter_show_recaptcha(); ?>

            <button type="<?php echo (osc_get_preference('forms_ajax', 'starter_theme') == 1 ? 'button' : 'submit'); ?>" id="send-public-message"><?php _e('Send message', 'starter') ; ?></button>
          </fieldset>
        </div>
      </form>
    <?php } ?>
  <?php } ?>

  <?php if ($is_modal) { ?>
    <div style="display:none!important;"><div><div>
      <?php osc_current_web_theme_path('footer.php') ; ?>
    </div></div></div>
  <?php } else { ?>
    <?php osc_current_web_theme_path('footer.php'); ?>
  <?php } ?>
</body>
</html>