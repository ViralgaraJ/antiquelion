<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php $msg = osc_get_flash_message(); ?>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
</head>

<body id="body-item-send-friend" class="fw-supporting">
  <div style="display:none!important;"><?php osc_current_web_theme_path('header.php'); ?></div></div></div>
  <?php $type = Params::getParam('content_type'); ?>
  <?php $user_id = Params::getParam('user_id'); ?>

  <!-- SEND TO FRIEND FORM -->

  <?php if($type == 'send_friend') { ?>
    <div id="send-friend-form" class="fw-box" style="display:block;">
      <div class="head">
        <h2><?php _e('Send to friend', 'zara'); ?></h2>
        <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
      </div>

      <div class="left">
        <img src="<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/images/contact-seller-form.jpg" />
      </div>

      <div class="middle">
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
                <div class="ins">
                  <label for="yourName"><span><?php _e('Your name', 'zara'); ?></span><div class="req">*</div></label> 
                  <?php SendFriendForm::your_name(); ?>
                </div>

                <div class="ins">
                  <label for="yourEmail"><span><?php _e('Your e-mail address', 'zara'); ?></span><div class="req">*</div></label>
                  <?php SendFriendForm::your_email(); ?>
                </div>
              </div>
            <?php } ?>

            <div class="row">
              <div class="ins">
                <label for="friendName"><span><?php _e("Your friend's name", 'zara'); ?></span><div class="req">*</div></label>
                <?php SendFriendForm::friend_name(); ?>
              </div>

              <div class="ins">
                <label for="friendEmail"><span><?php _e("Your friend's e-mail address", 'zara'); ?></span><div class="req">*</div></label>
                <?php SendFriendForm::friend_email(); ?>
              </div>
            </div>
                  
            <div class="row last">        
              <?php SendFriendForm::your_message(); ?>
            </div>
            <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>
      <?php if(osc_recaptcha_public_key() <> '') { ?>
        <div class="box">
          <div class="row">
            <?php osc_show_recaptcha(); ?>
          </div>
        </div>
      <?php } else { ?>
        <div class="norecaptcha" style="float:left;clear:both;width:100%;margin:15px 0 5px 0;">
          <?php osc_run_hook("anr_captcha_form_field"); ?>
        </div>
      <?php } ?>

            <button type="submit" id="blue"><?php _e('Send message', 'zara'); ?></button>
          </fieldset>
        </form>

        <?php SendFriendForm::js_validation(); ?>
      </div>
    </div>
  <?php } ?>

 


  <!-- NEW COMMENT FORM -->

  <?php if($type == 'add_comment') { ?>
    <?php if( osc_comments_enabled() && (osc_reg_user_post_comments () && osc_is_web_user_logged_in() || !osc_reg_user_post_comments()) ) { ?>
      <form target="_top" action="<?php echo osc_base_url(true) ; ?>" method="post" name="comment_form" id="comment_form" class="fw-box" style="display:block;">
        <input type="hidden" name="action" value="add_comment" />
        <input type="hidden" name="page" value="item" />
        <input type="hidden" name="id" value="<?php echo osc_item_id() ; ?>" />

        <fieldset>
          <div class="head">
            <h2><?php _e('Add new comment', 'zara'); ?></h2>
            <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
          </div>

          <div class="left">
            <img src="<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/images/post-comment-form.jpg" />
          </div>


          <div class="middle">
            <?php CommentForm::js_validation(); ?>
            <ul id="comment_error_list"></ul>

            <?php if(osc_is_web_user_logged_in()) { ?>
              <input type="hidden" name="authorName" value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
              <input type="hidden" name="authorEmail" value="<?php echo osc_logged_user_email();?>" />
            <?php } else { ?>
              <div class="row">
                <label for="authorName"><?php _e('Name', 'zara') ; ?></label> 
                <?php CommentForm::author_input_text(); ?>
              </div>

              <div class="row">
                <label for="authorEmail"><span><?php _e('E-mail', 'zara') ; ?></span><span class="req">*</span></label> 
                <?php CommentForm::email_input_text(); ?>
              </div>                  
            <?php } ?>

            <div class="row" id="last">
              <label for="title"><?php _e('Title', 'zara') ; ?></label>
              <?php CommentForm::title_input_text(); ?>
            </div>
            
            <?php osc_run_hook('item_comment_form'); ?> 
        
            <div class="row">
              <label for="body"><?php _e('Comment', 'zara') ; ?></label>
              <?php CommentForm::body_input_textarea(); ?>
            </div>

            <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>

            <?php if(osc_recaptcha_public_key() <> '') { ?>
              <?php osc_show_recaptcha(); ?>
            <?php } else { ?>
              <div style="float:left;clear:both;width:100%;margin:15px 0 5px 0;">
                <?php osc_run_hook("anr_captcha_form_field"); ?>
              </div>
            <?php } ?>
            
            <button type="submit" id="blue"><?php _e('Send comment', 'zara') ; ?></button>
          </div>
        </fieldset>
      </form>
    <?php } ?>
  <?php } ?>



  <!-- PUBLIC PROFILE CONTACT SELLER -->

  <?php if($type == 'pub_contact') { ?>
    <?php if(osc_reg_user_can_contact() && osc_is_web_user_logged_in() || !osc_reg_user_can_contact() ) { ?>
      <form target="_top" action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" id="contact_form" class="fw-box" style="display:block;">
        <input type="hidden" name="action" value="contact_post" class="nocsrf" />
        <input type="hidden" name="page" value="user" />
        <input type="hidden" name="id" value="<?php echo $user_id; ?>" />

        <fieldset>
          <div class="head">
            <h2><?php _e('Contact seller', 'zara'); ?></h2>
            <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
          </div>

          <div class="left">
            <img src="<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/images/contact-seller-form.jpg" />
          </div>


          <div class="middle">
            <?php ContactForm::js_validation(); ?>
            <ul id="error_list"></ul>

            <div class="row">
              <label for="yourName"><?php _e('Name', 'zara'); ?></label> 
              <?php ContactForm::your_name(); ?>
            </div>

            <div class="row">
              <label for="yourEmail"><span><?php _e('E-mail', 'zara') ; ?></span><span class="req">*</span></label> 
              <?php ContactForm::your_email(); ?>
            </div>                  

            <div class="row last">
              <label for="phoneNumber"><span><?php _e('Phone number', 'zara') ; ?></span></label>
              <?php ContactForm::your_phone_number(); ?>
            </div>

            <div class="row">
              <label for="message"><?php _e('Message', 'zara'); ?></label>
              <?php ContactForm::your_message(); ?>
            </div>

            <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>

            <button type="submit" id="blue"><?php _e('Send message', 'zara') ; ?></button>
          </div>
        </fieldset>
      </form>
    <?php } ?>
  <?php } ?>



  <!-- ITEM CONTACT SELLER -->

  <?php if($type == 'item_contact') { ?>
            <div id="show-c-seller-form" class="fw-box" style="display:block;">
              <div class="head">
                <h2><?php _e('Contact seller', 'zara'); ?></h2>
                <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
              </div>

              <?php if( osc_item_is_expired () ) { ?>
                <div class="empty">
                  <?php _e('This listing expired, you cannot contact seller.', 'zara') ; ?>
                </div>
              <?php } else if( (osc_logged_user_id() == osc_item_user_id()) && osc_logged_user_id() != 0 ) { ?>
                <div class="empty">
                  <?php _e('It is your own listing, you cannot contact yourself.', 'zara') ; ?>
                </div>
              <?php } else if( osc_reg_user_can_contact() && !osc_is_web_user_logged_in() ) { ?>
                <div class="empty">
                  <?php _e('You must log in or register a new account in order to contact the advertiser.', 'zara') ; ?>
                </div>
              <?php } else { ?> 

                <div class="left">
                  <img src="<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/images/contact-seller-form.jpg" />
                </div>

                <div class="middle">
                  <ul id="error_list"></ul>
                  <?php ContactForm::js_validation(); ?>

                  <form target="_top" action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" id="contact_form"<?php if(osc_item_attachment()) { ?> enctype="multipart/form-data"<?php } ?>>
                    <input type="hidden" name="action" value="contact_post" />
                    <input type="hidden" name="page" value="item" />
                    <input type="hidden" name="id" value="<?php echo osc_item_id() ; ?>" />

                    <?php osc_prepare_user_info() ; ?>

                    <fieldset>
                      <div class="row first">
                        <label><?php _e('Name', 'zara') ; ?></label>
                        <?php ContactForm::your_name(); ?>
                      </div>

                      <div class="row second">
                        <label><span><?php _e('E-mail', 'zara'); ?></span><span class="req">*</span></label>
                        <?php ContactForm::your_email(); ?>
                      </div>

                      <div class="row third">
                        <label><span><?php _e('Phone number', 'zara'); ?></span></label>
                        <?php ContactForm::your_phone_number(); ?>
                      </div>

                      <div class="row full">
                        <label><span><?php _e('Message', 'zara') ; ?></span><span class="req">*</span></label>
                        <?php ContactForm::your_message(); ?>
                      </div>

                      <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>

                      <?php if(osc_item_attachment()) { ?>
                        <div class="row has-file">
                          <label for="attachment"><?php _e('Attachment', 'zara'); ?>:</label>
                          <div class="input-box"><?php ContactForm::your_attachment(); ?></div>
                        </div>
                      <?php } ?>
            
                      <?php osc_run_hook('item_contact_form', osc_item_id()); ?>

                      <?php if(osc_recaptcha_public_key() <> '') { ?>
                        <?php osc_show_recaptcha(); ?>
                      <?php } else { ?>
                        <div style="float:left;clear:both;width:100%;margin:0px 0 15px 0;">
                          <?php osc_run_hook("anr_captcha_form_field"); ?>
                        </div>
                      <?php } ?>

                      <button type="submit" id="blue"><?php _e('Send message', 'zara') ; ?></button>
                    </fieldset>
                  </form>
                </div>
              <?php } ?>
            </div>
  <?php } ?>



  <!-- LOCATION SELECT BUTTON ON HOME PAGE -->

  <?php if($type == 'location_select') { ?>
    <form target="_top" action="<?php echo osc_base_url(true); ?>" method="get" id="home-loc-box" class="fw-box nocsrf" style="display:block;">
      <input type="hidden" name="page" value="search" />
      <input type="hidden" name="cookie-action-side" id="cookie-action-side" value="done" />
      <input type="hidden" name="sOrder" value="<?php echo osc_search_order(); ?>" />
      <input type="hidden" name="iOrderType" value="<?php $allowedTypesForSorting = Search::getAllowedTypesForSorting() ; echo @$allowedTypesForSorting[osc_search_order_type()]; ?>" />

      <fieldset>
        <div class="head">
          <h2><?php _e('Select location', 'zara'); ?></h2>
          <a href="#" class="def-but fw-close-button round3"><i class="fa fa-times"></i> <?php _e('Close', 'zara'); ?></a>
        </div>

        <div class="left">
          <img src="<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/images/home-loc-form.jpg" />
        </div>


        <div class="middle">
          <?php $aCountries = Country::newInstance()->listAll(); ?>
        
          <div class="row"<?php if(count($aCountries) <= 1) {?> style="display:none;"<?php } ?>>
            <h4><?php _e('Country', 'zara') ; ?></h4>

            <?php
              // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
              $s_country = Country::newInstance()->listAll();
              if(count($s_country) <= 1) {
                $s_country = $s_country[0];
              }
            ?>

            <select id="countryId" name="sCountry">
              <option value=""><?php _e('Select a country', 'zara'); ?></option>

              <?php if(is_array($aCountries) && count($aCountries) > 0) { ?>
                <?php foreach ($aCountries as $country) {?>
                  <option value="<?php echo $country['pk_c_code']; ?>" <?php if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == $country['pk_c_code'] or Params::getParam('sCountry') == $country['s_name'] or Params::getParam('sCountry') == @$country['s_name_native']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] = $country['pk_c_code']) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($country, 's_name'); ?></option>

                  <?php 
                    if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == $country['pk_c_code'] or Params::getParam('sCountry') == $country['s_name'] or Params::getParam('sCountry') == @$country['s_name_native']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] = $country['pk_c_code']) {
                      $current_country_code = $country['pk_c_code'];
                    } 
                  ?>
                <?php } ?>
              <?php } ?>
            </select>
          </div>

        
          <?php
            $current_country = Params::getParam('country') <> '' ? Params::getParam('country') : Params::getParam('sCountry');
            if($current_country <> '') {
              $aRegions = Region::newInstance()->findByCountry($current_country_code);
            } else {
              if(osc_count_countries() <= 1 && isset($s_country['pk_c_code']) && $s_country['pk_c_code'] <> '') {
                $aRegions = Region::newInstance()->findByCountry($s_country['pk_c_code']);
              } else {
                $aRegions = '';
              }
            }
          ?>

          <div class="row">
            <h4><?php _e('Region', 'zara') ; ?></h4>

            <?php if(is_array($aRegions) && count($aRegions) >= 1 ) { ?>
              <select id="regionId" name="sRegion" <?php if(Params::getParam('sRegion') == '' && Params::getParam('region')) {?>disabled<?php } ?>>
                <option value=""><?php _e('Select a region', 'zara'); ?></option>
                
                <?php foreach ($aRegions as $region) {?>
                  <option value="<?php echo $region['pk_i_id']; ?>" <?php if(Params::getParam('sRegion') == $region['pk_i_id'] or Params::getParam('sRegion') == $region['s_name'] or Params::getParam('sRegion') == @$region['s_name_native']) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($region, 's_name'); ?></option>
                <?php } ?>
              </select>
            <?php } else { ?>
              <!--<input type="text" name="sRegion" id="sRegion-side" value="<?php echo Params::getParam('sRegion'); ?>" placeholder="<?php echo osc_esc_html(__('Enter a region', 'zara')); ?>" />-->
              <select id="regionId" name="sRegion" disabled><option value=""><?php _e('Select a region', 'zara'); ?></option></select>
            <?php } ?>
          </div>
        
          <?php 
            $current_region = Params::getParam('region') <> '' ? Params::getParam('region') : Params::getParam('sRegion');

            if(!is_numeric($current_region) && $current_region <> '') {
              $reg = Region::newInstance()->findByName($current_region);
              $current_region = @$reg['pk_i_id'];
            }

            if($current_region <> '' && !empty($current_region)) {
              $aCities = City::newInstance()->findByRegion($current_region);
            } else {
              $aCities = '';
            }
          ?> 

          <div class="row">
            <h4><?php _e('City', 'zara') ; ?></h4>

            <?php if(is_array($aCities) && count($aCities) >= 1 && !empty($aCities) ) { ?>
              <select name="sCity" id="cityId" <?php if(Params::getParam('sCity') == '' && Params::getParam('city') == '') {?>disabled<?php } ?>> 
                <option value=""><?php _e('Select a city', 'zara'); ?></option>
          
                <?php foreach ($aCities as $city) {?>
                  <option value="<?php echo $city['pk_i_id']; ?>" <?php if(Params::getParam('sCity') == $city['pk_i_id'] or Params::getParam('sCity') == $city['s_name'] or Params::getParam('sCity') == @$city['s_name_native']) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($city, 's_name'); ?></option>
                <?php } ?>
              </select>
            <?php } else { ?>
              <!--<input type="text" name="sCity" id="sCity-side" value="<?php echo Params::getParam('sCity'); ?>" placeholder="<?php echo osc_esc_html(__('Enter a city', 'zara')); ?>" />-->
              <select id="cityId" name="sCity" disabled><option value=""><?php _e('Select a city', 'zara'); ?></option></select>
            <?php } ?>
          </div>
          
          <div class="row r-but">
            <button type="submit" id="blue"><?php _e('Search', 'zara') ; ?></button>
          </div>

          <div class="row r-clean">
            <a target="_top" href="<?php echo osc_search_url(array('page' => 'search', 'cookie-action-side' => 'done'));?>" class="clear-search tr1" title="<?php  echo osc_esc_html(__('Clear location', 'zara')); ?>"><i class="fa fa-eraser"></i><?php _e('Clean search', 'zara'); ?></a>
          </div>
        </div>
      </fieldset>
    </form>
  <?php } ?>



  <script>
    $(document).ready(function(){
      // PLACEHOLDERS FOR SEND TO FRIEND FORM
      $('#sendfriend #yourName').attr('placeholder', '<?php echo osc_esc_js(__('Your real or user name', 'zara')); ?>');
      $('#sendfriend #yourEmail').attr('placeholder', '<?php echo osc_esc_js(__('Contact email', 'zara')); ?>');
      $('#sendfriend #friendName').attr('placeholder', '<?php echo osc_esc_js(__('Name of your friend', 'zara')); ?>');
      $('#sendfriend #friendEmail').attr('placeholder', '<?php echo osc_esc_js(__('Friend\'s email', 'zara')); ?>');
      $('#sendfriend #message').attr('placeholder', '<?php echo osc_esc_js(__('I would like to ask you...', 'zara')); ?>');


      // PLACEHODERS FOR PUBLIC PROFILE CONTACT SELLER FORM
      $('#yourName').attr('placeholder', '<?php echo osc_esc_js(__('Your real or user name', 'zara')); ?>');
      $('#yourEmail').attr('placeholder', '<?php echo osc_esc_js(__('Contact email', 'zara')); ?>');
      $('#phoneNumber').attr('placeholder', '<?php echo osc_esc_js(__('Phone seller can contact you', 'zara')); ?>');

      <?php if(osc_is_web_user_logged_in()) { ?>
        $('#yourName').attr('readonly', true).addClass('disabled');
        $('#yourEmail').attr('readonly', true).addClass('disabled');
      <?php } ?>


      // PLACEHODERS FOR ADD NEW COMMENT FORM
      $('#comment_form #authorName').attr('placeholder', '<?php echo osc_esc_js(__('Your real or user name', 'zara')); ?>');
      $('#comment_form #authorEmail').attr('placeholder', '<?php echo osc_esc_js(__('Contact email', 'zara')); ?>');
      $('#comment_form #title').attr('placeholder', '<?php echo osc_esc_js(__('Comment title', 'zara')); ?>');
      $('#comment_form #body').attr('placeholder', '<?php echo osc_esc_js(__('Review or comment...', 'zara')); ?>');
    });
  </script>


  <?php if($type == 'location_select') { ?>
    <!-- JAVASCRIPT AJAX LOADER FOR COUNTRY/REGION/CITY SELECT BOX -->
    <script>
      $(document).ready(function(){

        // COUNTRY SELECT
        $("body").on("change", "#countryId", function(){
          var pk_c_code = $(this).val();
          var url = '<?php echo osc_base_url(true)."?page=ajax&action=regions&countryId="; ?>' + pk_c_code;
          var result = '';


          if(pk_c_code != '') {

            // Country has been selected
            $.ajax({
              type: "POST",
              url: url,
              dataType: 'json',
              success: function(data) {
                var length = data.length;
                var locationsNative = "<?php echo osc_get_current_user_locations_native(); ?>";

                if(length > 0) {

                  result += '<option value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option>';
                  for(key in data) {
                    var vname = data[key].s_name;
                    if(data[key].hasOwnProperty('s_name_native')) {
                      if(data[key].s_name_native != '' && data[key].s_name_native != 'null' && data[key].s_name_native != null && locationsNative == "1") {
                        vname = data[key].s_name_native;
                      }
                    }

                    result += '<option value="' + data[key].pk_i_id + '">' + vname + '</option>';
                  }

                  $("#sRegion-side").before('<select name="sRegion" id="regionId" ><option selected="selected" value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option></select>').remove();
                  $("#sCity-side").before('<select name="sCity" id="cityId"><option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
                  
                  $("#regionId").val("").html(result).attr('disabled',false);
                  $("#cityId").val("").attr('disabled',true);

                } else {

                  $("#regionId").before('<input placeholder="<?php echo osc_esc_js(__('Enter a region', 'zara')); ?>" type="text" name="sRegion" id="sRegion-side" />').remove();
                  $("#cityId").before('<input placeholder="<?php echo osc_esc_js(__('Enter a city', 'zara')); ?>" type="text" name="sCity" id="sCity-side" />').remove();;

                  $("#sRegion-side").val('').attr('disabled',false);
                  $("#sCity-side").val('').attr('disabled',true);
                }

                $("#cityId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>');
              }
            });

          } else {

            // Country is empty
            $("#sRegion-side").before('<select name="sRegion" id="regionId"><option selected="selected" value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option></select>').remove();
            $("#regionId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option>').attr('disabled',true);

            $("#sCity-side").before('<select name="sCity" id="cityId" ><option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
            $("#cityId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>').attr('disabled',true);
           }
        });



        // REGION SELECTION
        $("body").on("change", "#regionId", function(){
          var pk_r_id = $(this).val();
          var url = '<?php echo osc_base_url(true)."?page=ajax&action=cities&regionId="; ?>' + pk_r_id;
          var result = '';

          if(pk_r_id != '') {

            // Region has been selected
            $.ajax({
              type: "POST",
              url: url,
              dataType: 'json',
              success: function(data){
                var length = data.length;
                var locationsNative = "<?php echo osc_get_current_user_locations_native(); ?>";

                if(length > 0) {

                  result += '<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>';
                  for(key in data) {
                    var vname = data[key].s_name;
                    if(data[key].hasOwnProperty('s_name_native')) {
                      if(data[key].s_name_native != '' && data[key].s_name_native != 'null' && data[key].s_name_native != null && locationsNative == "1") {
                        vname = data[key].s_name_native;
                      }
                    }

                    result += '<option value="' + data[key].pk_i_id + '">' + vname + '</option>';
                  }

                  $("#sCity-side").before('<select name="sCity" id="cityId"><option value="" selected="selected"><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
                  $("#cityId").val("").html(result).attr('disabled',false);

                } else {
                  $("#cityId").before('<input type="text" placeholder="<?php echo osc_esc_js(__('Enter a city', 'zara')); ?>" name="sCity" id="sCity-side" />').remove().val('').attr('disabled',false);
                }

              }
            });

          } else {

            // Region is empty
            $("#sCity-side").before('<select name="sCity" id="cityId"><option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
            $("#cityId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>').attr('disabled',true);
          }
        });


        // MANAGE REGION & CITY INPUTS
        $("body").on("change", "input#sRegion-side", function(){
          if( $(this).val() != '' ) {
            $("input#sCity-side").attr('disabled',false).val("");
          } else {
            $("input#sCity-side").attr('disabled',true).val("");
          }
        });


        // ONLOAD FIXES
        if($("#countryId").length != 0) {
          if( $("#countryId").attr('value') != "")  {
            $("#regionId, #sRegion-side, #cityId, #sCity-side").attr('disabled',false);
          } else {
            $("#regionId, #sRegion-side, #cityId, #sCity-side").attr('disabled',true);
          }
        }

        if($("#country").length != 0) {
          $("#regionId, #region").attr('disabled',false);
        }

        if( $("#regionId").attr('value') != "")  {
          $("#cityId, #sCity-side").attr('disabled',false);
        } else {
          $("#cityId, #sCity-side").attr('disabled',true);
        }

      });
    </script>
  <?php } ?>

  <?php 
    if(@$type == 'location_select') {
      exit;
    }

    if(!isset($type) || $type == '') {
      if(is_array($msg) && count($msg) > 0) {
        foreach($msg as $m) {
          if($m[type] == 'error') {
            osc_add_flash_error_message($m[msg]);
          } else if ($m[type] == 'success' || $m[type] == 'ok') {
            osc_add_flash_ok_message($m[msg]);
          } else {
            osc_add_flash_message($m[msg]);
          }
        }
      }

      header('Location:'.osc_item_url());
      exit;
    } 
  ?>
</body>
</html>