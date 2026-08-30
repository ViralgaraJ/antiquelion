<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="body-user-register">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <div id="i-forms" class="content">
    <div id="login" class="box"<?php if(Params::getParam('move') == 'register') { ?> style="display:none"<?php } ?>>
      <div class="user_forms login round5">
        <div class="inner">
          <?php if(function_exists('fl_call_after_install')) { ?>
            <a class="external-log fb btn round3 tr1" href="<?php echo facebook_login_link(); ?>"><i class="fa fa-facebook"></i><?php _e('Connect with Facebook', 'starter'); ?></a>
          <?php } ?>

          <?php if(function_exists('gc_login_button')) { ?>
            <a class="external-log gc btn round3 tr1" href="<?php gc_login_button('link-only'); ?>"><i class="fa fa-google"></i><?php _e('Connect with Google', 'starter'); ?></a>
          <?php } ?>

          <?php if(function_exists('fjl_login_button')) { ?>
            <a target="_top" href="javascript:void(0);" class="external-log fb btn round3 tr1 fl-button fjl-button" onclick="fjlCheckLoginState();" title="<?php echo osc_esc_html(__('Connect with Facebook', 'starter')); ?>">
              <i class="fa fa-facebook"></i>
              <?php _e('Connect with Facebook', 'starter'); ?>
            </a>
          <?php } ?>
          
          <?php if(function_exists('fl_call_after_install') || function_exists('gc_login_button') || function_exists('fjl_login_button')) { ?>
            <div class="or"><div class="left"></div><span><?php _e('or', 'starter'); ?></span><div class="right"></div></div>
          <?php } ?>

          <form action="<?php echo osc_base_url(true); ?>" method="post" >
          <input type="hidden" name="page" value="login" />
          <input type="hidden" name="action" value="login_post" />
          <fieldset>
            <h2><?php _e('Log in', 'starter'); ?></h2>

            <span class="input-box"><i class="fa fa-envelope"></i><?php UserForm::email_login_text(); ?></span>
            <span class="input-box"><i class="fa fa-lock"></i><?php UserForm::password_login_text(); ?></span>

            <button type="submit"><?php _e("Log in", 'starter');?></button>

            <div class="login-line">
              <div class="input-box-check">
                <?php UserForm::rememberme_login_checkbox();?>
                <label for="remember"><?php _e('Remember me', 'starter'); ?></label>
              </div>
            </div>

            <div class="iform-footer">
              <div class="swap">
                <?php _e('Don\'t you have account?', 'starter'); ?>&nbsp; 
                <a href="#" class="swap-log-reg to-reg"><?php _e('Register now', 'starter'); ?> <i class="fa fa-angle-double-right"></i></a>
              </div>
 
              <div class="lost">
                <span><?php _e('Lost password?', 'starter'); ?></span>&nbsp;
                <a class="more-login tr1" href="<?php echo osc_recover_user_password_url(); ?>"><?php _e('Recover password now', 'starter'); ?> <i class="fa fa-angle-double-right"></i></a>
              </div>
            </div>
          </fieldset>
          </form>
        </div>
      </div>
    </div>

    <div id="register" class="box" <?php if(Params::getParam('move') <> 'register') { ?> style="display:none"<?php } ?>>
      <div class="user_forms register round5">
        <div class="inner">          
          <form name="register" id="register" action="<?php echo osc_base_url(true); ?>" method="post" >
          <input type="hidden" name="page" value="register" />
          <input type="hidden" name="action" value="register_post" />
          <fieldset>
            <h2><?php _e('Register new account', 'starter'); ?></h2>

            <h1></h1>
            <ul id="error_list"></ul>

            <label for="name"><span><?php _e('Name', 'starter'); ?></span><span class="req">*</span></label> <span class="input-box"><i class="fa fa-user"></i><?php UserForm::name_text(); ?></span>
            <label for="password"><span><?php _e('Password', 'starter'); ?></span><span class="req">*</span></label> <span class="input-box"><i class="fa fa-lock"></i><?php UserForm::password_text(); ?></span>
            <label for="password"><span><?php _e('Re-type password', 'starter'); ?></span><span class="req">*</span></label> <span class="input-box"><i class="fa fa-unlock-alt"></i><?php UserForm::check_password_text(); ?></span>
            <label for="email"><span><?php _e('E-mail', 'starter'); ?></span><span class="req">*</span></label> <span class="input-box"><i class="fa fa-envelope"></i><?php UserForm::email_text(); ?></span>
            <label for="phone"><?php _e('Mobile Phone', 'starter'); ?></label> <span class="input-box last"><i class="fa fa-phone"></i><?php UserForm::mobile_text(osc_user()); ?></span>
            <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'starter'); ?></div></div>

            <?php osc_run_hook('user_register_form'); ?>

            <?php starter_show_recaptcha('register'); ?>

            <button type="submit" id="green"><?php _e('Create account now', 'starter'); ?></button>

            <div class="iform-footer">
              <div class="swap">
                <?php _e('Have an account', 'starter'); ?>&nbsp; 
                <a href="#" class="swap-log-reg to-log"><?php _e('Log in', 'starter'); ?> <i class="fa fa-angle-double-right"></i></a>
              </div>
            </div>
          </fieldset>
          </form>
        </div>
      </div>
    </div>
  </div>


  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>