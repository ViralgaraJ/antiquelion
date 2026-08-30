<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />

  <?php if(osc_get_preference('image_upload', 'zara_theme') <> 1) { ?>
    <?php if(osc_images_enabled_at_items()) { ItemForm::photos_javascript(); } ?>
  <?php } ?>

  <?php if(osc_images_enabled_at_items() && !modern_is_fineuploader() && osc_get_preference('image_upload', 'zara_theme') == 1) { ItemForm::photos_javascript(); } ?>

  <script type="text/javascript">
  <?php if(osc_get_preference('image_upload', 'zara_theme') <> 1) { ?>
    function uniform_input_file(){
      photos_div = $('div.photos');
      $('div',photos_div).each(
        function(){
          if( $(this).find('div.uploader').length == 0  ){
            divid = $(this).attr('id');
            if(divid != 'photos'){
              divclass = $(this).hasClass('box');
              if( !$(this).hasClass('box') & !$(this).hasClass('uploader') & !$(this).hasClass('row')){
                //$("div#"+$(this).attr('id')+" input:file").uniform({fileDefaultText: fileDefaultText,fileBtnText: fileBtnText});
              }
            }
          }
        }
      );
    }
    <?php } ?>
    
    //setInterval("uniform_plugins()", 250);
    function uniform_plugins() {
      // Add class checkbox to custom fields with check
      $('#post-hooks #plugin-hook .meta_list .meta:not(.check-row) input[type="checkbox"]').parent().addClass('check-row');
      $('.add_item .row:not(.radio-row) input[type="radio"]').parent('.row').addClass('radio-row');
      $('.add_item label:not(.radio-label) input[type="radio"]').parent('label').addClass('radio-label');

      var content_plugin_hook = $('#plugin-hook').text();
      content_plugin_hook = content_plugin_hook.replace(/(\r\n|\n|\r)/gm,"");
      if( content_plugin_hook != '' ){
        
        var div_plugin_hook = $('#plugin-hook');
        var num_uniform = $("div[id*='uniform-']", div_plugin_hook ).length;
        if( num_uniform == 0 ){
          if( $('#plugin-hook input:text').length > 0 ){
            $('#plugin-hook input:text').uniform();
          }
          if( $('#plugin-hook select').length > 0 ){
            $('#plugin-hook select').uniform();
          }
        }
      }
    }

    <?php if(osc_locale_thousands_sep()!='' || osc_locale_dec_point() != '') { ?>
    $().ready(function(){
      $("#price").blur(function(event) {
        var price = $("#price").attr("value");
        <?php if(osc_locale_thousands_sep()!='') { ?>
        while(price.indexOf('<?php echo osc_esc_js(osc_locale_thousands_sep());  ?>')!=-1) {
          price = price.replace('<?php echo osc_esc_js(osc_locale_thousands_sep());  ?>', '');
        }
        <?php }; ?>
        <?php if(osc_locale_dec_point()!='') { ?>
        var tmp = price.split('<?php echo osc_esc_js(osc_locale_dec_point())?>');
        if(tmp.length>2) {
          price = tmp[0]+'<?php echo osc_esc_js(osc_locale_dec_point())?>'+tmp[1];
        }
        <?php }; ?>
        $("#price").attr("value", price);
      });
    });
    <?php }; ?>


    tabberAutomatic();
  </script>
  <!-- end only item-post.php -->
</head>

<body id="body-item-edit">
  <h1 class="item_adding"></h1>

  <?php 
    $def_cat['fk_i_category_id'] = Params::getParam('sCategory');
    $def_cat['fk_i_category_id'] = ($def_cat['fk_i_category_id'] <> '' ? $def_cat['fk_i_category_id'] : 0);

    $country = Country::newInstance()->listAll();

    if(osc_is_web_user_logged_in()) {

      // GET LOCATION OF LOGGED USER
      $cookie_loc = osc_item();

      // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
      if(count($country) == 1) {
        $country = $country[0];
        $cookie_loc['fk_c_country_code'] = $country['pk_c_code'];
      }
    } else {

      // GET LOCATION FROM SEARCH
      if(Params::getParam('sCountry') <> '') {    
        if(strlen(Params::getParam('sCountry')) == 2) {
          $cookie_loc['fk_c_country_code'] = Params::getParam('sCountry');
        } else {
          $country = Country::newInstance()->findByName(Params::getParam('sCountry'));
          $cookie_loc['fk_c_country_code'] = $country['pk_c_code'];
        }
      } else {
        // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
        if(count($country) == 1) {
          $country = $country[0];
          $cookie_loc['fk_c_country_code'] = $country['pk_c_code'];
        }
      }



      if(Params::getParam('sRegion') <> '') {
        if(is_numeric(Params::getParam('sRegion'))) {
          $cookie_loc['fk_i_region_id'] = Params::getParam('sRegion');
        } else {
          $region = Region::newInstance()->findByName(Params::getParam('sRegion'));
          $cookie_loc['fk_i_region_id'] = $region['pk_i_id'];
        }
      }
    }


    if(Params::getParam('sCity') <> '') {
      if(is_numeric(Params::getParam('sCity'))) {
        $cookie_loc['fk_i_city_id'] = Params::getParam('sCity');
      } else {
        $city = City::newInstance()->findByName(Params::getParam('sCity'), $cookie_loc['fk_i_region_id']);
        $cookie_loc['fk_i_city_id'] = $city['pk_i_id'];
      }
    }
    
    if($cookie_loc['fk_c_country_code'] <> '') {
      $region_list = Region::newInstance()->findByCountry($cookie_loc['fk_c_country_code']);
    } else {
      $region_list = RegionStats::newInstance()->listRegions("%%%%", ">=");
    }



    if($cookie_loc['fk_i_region_id'] <> '') {
      $city_list = City::newInstance()->findByRegion($cookie_loc['fk_i_region_id']);
    }
  ?>


  <?php osc_current_web_theme_path('header.php') ; ?>

  <ul id="error_list" class="new-item"></ul>

  <div class="content add_item">
    <form name="item" action="<?php echo osc_base_url(true);?>" method="post" enctype="multipart/form-data">
      <fieldset>
      <input type="hidden" name="action" value="item_edit_post" />
      <input type="hidden" name="page" value="item" />
      <input type="hidden" name="id" value="<?php echo osc_item_id(); ?>" /> 
      <input type="hidden" name="secret" value="<?php echo osc_item_secret(); ?>" /> 

      <h2 class="post-out"><?php _e('Modify listing', 'zara'); ?></h2>

      <?php osc_run_hook('item_publish_top'); ?>
      
      <div id="left">
        <div class="box general_info">

          <!-- CATEGORY -->
          <?php if(osc_get_preference('drop_cat', 'zara_theme') == 1) { ?>
            <div class="row catshow multiple">
              <div class="multi-left">
                <label for="catId"><span><?php _e('Category', 'zara'); ?></span><span class="req">*</span></label>
              </div>
              <div class="multi-right">
                <?php ItemForm::category_multiple_selects(null, null, __('Select a category', 'zara')); ?>
              </div>
            </div>
          <?php } else { ?>
            <div class="row catshow">
              <label for="catId"><span><?php _e('Category', 'zara'); ?></span><span class="req">*</span></label>
              <?php ItemForm::category_select(null, null, __('Select a category', 'zara')); ?>
            </div>
          <?php } ?>
          
          <?php osc_run_hook('item_publish_category'); ?>


          <!-- PRICE -->
          <?php if( osc_price_enabled_at_items() ) { ?>
            <div class="box price">
              <label for="price"><span><?php _e('Price', 'zara'); ?></span></label>
              
              <?php ItemForm::price_input_text(); ?>
              <?php ItemForm::currency_select(); ?>
              
              <div class="radio-price-row first"><input type="radio" id="ps1" name="PriceSelect" value="0" checked="checked"><?php _e('Enter price', 'zara'); ?></div>
              <div class="radio-price-row"><input type="radio" id="ps2" name="PriceSelect" value="1"><?php _e('Free', 'zara'); ?></div>
              <div class="radio-price-row"><input type="radio" id="ps3" name="PriceSelect" value="2"><?php _e('Check with seller', 'zara'); ?></div>
 
            </div>
          <?php } ?>
          
          <?php osc_run_hook('item_publish_price'); ?>


          <!-- TITLE & DESCRIPTION -->
          <div class="row descshow">
            <?php ItemForm::multilanguage_title_description(); ?>
            <div class="req-what"><div class="req">*</div><div class="small-info"><?php _e('This field is required', 'zara'); ?></div></div>
          </div>
          
          <?php osc_run_hook('item_publish_description'); ?>
        </div>


        <div class="box photos item_edit photoshow <?php if(osc_get_preference('image_upload', 'zara_theme') == 1) { echo 'drag_drop'; } else { echo 'not_drag_drop'; } ?>">
          <h2><?php _e('Pictures', 'zara'); ?></h2>

          <div id="photos">
            <div class="photo-left">
              <label><?php _e('Click to upload', 'zara'); ?></label>
              <span class="text"><?php _e('You can upload up to', 'zara'); ?> <?php echo osc_max_images_per_item(); ?> <?php _e('pictures per listing', 'zara'); ?></span>
            </div>

            <?php if(osc_images_enabled_at_items()) {
              if(modern_is_fineuploader() && osc_get_preference('image_upload', 'zara_theme') == 1) {
                ItemForm::ajax_photos();
                echo '</div>';
            } else { ?>
              <div class="row">
                <input type="file" name="photos[]" multiple />
              </div>
            </div>
            <a id="new-pho" href="#" onclick="addNewPhotoZara(); uniform_input_file(); return false;"><?php _e('Add new photo', 'zara'); ?></a>
          <?php } ?>
          
          <?php osc_run_hook('item_publish_images'); ?>
        </div>
        <?php } ?>

 
        <!-- LOCATION OF ITEM -->
        <div class="box location">
          <h2><?php _e('Location', 'zara'); ?></h2>

          <?php $country = Country::newInstance()->listAll(); ?>
          <div class="row" <?php if(count($country) == 1) { ?>style="display:none;"<?php } ?>>
            <label for="countryId"><?php _e('Country', 'zara'); ?></label>
            <?php ItemForm::country_select(Country::newInstance()->listAll(), $cookie_loc); ?>
          </div>         

          <div class="row">
            <label for="regionId"><?php _e('Region', 'zara'); ?></label>
            <?php ItemForm::region_select($region_list, $cookie_loc); ?>
          </div>

          <div class="row">
            <label for="city"><span><?php _e('City', 'zara'); ?></span></label>
            <?php ItemForm::city_select($city_list, $cookie_loc); ?>
          </div>

          <div class="row">
            <label for="address"><?php _e('Address', 'zara'); ?></label>
            <?php ItemForm::address_text(osc_user()); ?>
          </div>
          
          <?php osc_run_hook('item_publish_location'); ?>
        </div>


        <!-- SELLER INFORMATION -->
        <div class="box seller<?php if(osc_is_web_user_logged_in() ) { ?> logged<?php } ?>">
          <h2><?php _e('Seller\'s information', 'zara'); ?></h2>

          <div class="row">
            <label for="contactName"><?php _e('Name', 'zara'); ?></label>
            <?php ItemForm::contact_name_text(osc_item()) ; ?>
          </div>
        
          <div class="row">
            <label for="phone"><?php _e('Mobile Phone', 'zara'); ?></label>
            <?php ItemForm::city_area_text(osc_item()) ; ?>
          </div>

          <?php if(method_exists('ItemForm', 'show_phone_checkbox')) { ?>
            <div class="row mail_show">
              <div id="email_show">
                <?php ItemForm::show_phone_checkbox() ; ?>
              </div>
              <label for="showPhone" id="label_phone_show"><?php _e('Show phone on listing page', 'zara'); ?></label>
            </div>
          <?php } ?>

          <div class="row">
            <label for="contactEmail"><span><?php _e('E-mail', 'zara'); ?></span><span class="req">*</span></label>
            <?php ItemForm::contact_email_text(osc_item()) ; ?>
          </div>
          
          <div class="row mail_show">
            <div id="email_show">
              <?php ItemForm::show_email_checkbox() ; ?>
            </div>
            <label for="showEmail" id="label_email_show"><?php _e('Show email on listing page', 'zara'); ?></label>
          </div>
          
          <?php osc_run_hook('item_publish_seller'); ?>
        </div>


        <div class="clear"></div>


        <!-- PLUGIN HOOKS -->
        <div id="post-hooks">
          <h2><?php _e('Additional details', 'zara'); ?></h2>

          <?php ItemForm::plugin_edit_item(); ?>
          <?php osc_run_hook('item_publish_hook'); ?>
        </div>
      </div>



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

      <?php osc_run_hook('item_publish_bottom'); ?>
      
      <div class="clear"></div>

      <button type="submit" class="btn btn-primary"><?php _e('Save changes', 'zara'); ?></button>
      <?php osc_run_hook('item_publish_buttons'); ?>
      </fieldset>
      
      <?php osc_run_hook('item_publish_after'); ?>
    </form>
  </div>


  <!-- JAVASCRIPT FOR PRICE ALTERNATIVES -->
  <script type="text/javascript">

    $(document).ready(function(){
      draw_select( $('select[id^="select_"]').length + 1, <?php echo $def_cat['fk_i_category_id']; ?> );
    });


    $(document).ready(function(){
      $('input[name="PriceSelect"]:radio').change(function(){
        if( $(this).val() == 0) {
          $('input#price').val('');
          $('input#price, #uniform-currency').attr('readonly', false);
          $('input#price, #uniform-currency').removeClass('is_disabled');
        } else if ( $(this).val() == 1) {
          $('input#price').val(0);
          $('input#price, #uniform-currency').attr('readonly', true);
          $('input#price, #uniform-currency').addClass('is_disabled');
        } else if ( $(this).val() == 2) {
          $('input#price').val('');
          $('input#price, #uniform-currency').attr('readonly', true);
          $('input#price, #uniform-currency').addClass('is_disabled');
        }
      });

      $('#uniform-currency.is_disabled, #uniform-currency.is_disabled select#currency').on('click', function(){
        return false;
      });


      // CONTROL IF PRICE IS FREE OR CHECK WITH SELLER
      if ($('input#price').val() == '') {
        $('input[name="PriceSelect"]#ps3').prop('checked', true);
        $('input#price, #uniform-currency').attr('readonly', true);
        $('input#price, #uniform-currency').addClass('is_disabled');
      } else if($('input#price').val() == 0) {
        $('input[name="PriceSelect"]#ps2').prop('checked', true);
        $('input#price, #uniform-currency').attr('readonly', true);
        $('input#price, #uniform-currency').addClass('is_disabled');
      }
    });

    
    $("#catId").click(function(){
      var cat_id = $(this).val();
      var url = '<?php echo osc_base_url(); ?>index.php';
      var result = '';

      if(cat_id != '') {
        if(catPriceEnabled[cat_id] == 1) {
          $("#price").closest("div").show();
        } else {
          $("#price").closest("div").hide();
          $('#price').val('') ;
        }

        if(1==2) {
          $.ajax({
            type: "POST",
            url: url,
            data: 'page=ajax&action=runhook&hook=item_form&catId=' + cat_id,
            dataType: 'html',
            success: function(data){
              $("#plugin-hook").html(data);
            }
          });
        }
      }
    });
  </script>

  <?php if(osc_get_preference('image_upload', 'zara_theme') <> 1) { ?>
  <script>
    var photoIndex = 0;
    function gebi(id) { return document.getElementById(id); }
    function ce(name) { return document.createElement(name); }
    function re(id) {
      var e = gebi(id);
      e.parentNode.removeChild(e);
    }

    function addNewPhotoZara() {
      var max = <?php echo osc_max_images_per_item(); ?>;
      var num_img = $('input[name="photos[]"]').length + $("a.delete").length;
      if((max!=0 && num_img<max) || max==0) {
        var id = 'p-' + photoIndex++;

        var i = ce('input');
        i.setAttribute('type', 'file');
        i.setAttribute('name', 'photos[]');

        var a = ce('a');
        a.style.fontSize = 'x-small';
        a.style.paddingLeft = '10px';
        a.setAttribute('href', '#');
        a.setAttribute('divid', id);
        a.onclick = function() { re(this.getAttribute('divid')); return false; }
        a.appendChild(document.createTextNode('<?php echo osc_esc_js(__('Remove', 'zara')); ?>'));

        var d = ce('div');
        d.setAttribute('id', id);
        d.setAttribute('style','padding: 4px 0;')

        d.appendChild(i);
        d.appendChild(a);

        gebi('photos').appendChild(d);

      } else {
        alert('<?php echo osc_esc_js(__('Sorry, you have reached the maximum number of images per listing')); ?>');
      }
    }
    // Listener: automatically add new file field when the visible ones are full.
    setInterval("add_file_field()", 250);
    /**
     * Timed: if there are no empty file fields, add new file field.
     */
    function add_file_field() {
      var count = 0;
      $('input[name="photos[]"]').each(function(index) {
        if ( $(this).val() == '' ) {
          count++;
        }
      });
      var max = <?php echo osc_max_images_per_item(); ?>;
      var num_img = $('input[name="photos[]"]').length + $("a.delete").length;
      if (count == 0 && (max==0 || (max!=0 && num_img<max))) {
        addNewPhotoZara();uniform_input_file(); return false;
      }
    }
  </script>
  <?php } ?>


  <!-- ADD CAMERA ICON TO PICTURE BOX -->
  <script>
    $(document).ready(function(){
      setInterval(function(){ 
        $('input[name="qqfile"]').prop('accept', 'image/*');
      }, 250);


      $('#photos .qq-upload-button > div').remove();
      $('#photos .qq-upload-button').append('<div class="sample-box-wrap"></div>');

      var draw_boxes = <?php echo osc_max_images_per_item(); ?>;
      draw_boxes = draw_boxes - $('.photos.item_edit #photos > .qq-upload-list > li.qq-upload-success').length;
      draw_boxes = Math.max(draw_boxes, 1);

      for(i = 0; i < draw_boxes; i++) {  
        $('#photos .qq-upload-button .sample-box-wrap').append('<div class="sample-box tr1"><div class="ins tr1"><i class="fa fa-camera tr1"></i></i></div></div>');
      }

      $('#photos .qq-upload-button .sample-box-wrap').on('click', function(){
        $('#photos .qq-upload-button input').click();
      });
    });
  </script>


  <!-- JAVASCRIPT AJAX LOADER FOR COUNTRY/REGION/CITY SELECT BOX -->
  <script>
    $(document).ready(function(){

      // COUNTRY SELECT
      $("#countryId").on("change", function(){
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

                $("#region").before('<select name="sRegion" id="regionId" ><option selected="selected" value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option></select>').remove();
                $("#city").before('<select name="cityId" id="cityId"><option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
                
                $("#regionId").val("").html(result).attr('disabled',false);
                $("#cityId").val("").attr('disabled',true);

              } else {

                $("#regionId").before('<input placeholder="<?php echo osc_esc_js(__('Enter a region', 'zara')); ?>" type="text" name="sRegion" id="region" />').remove();
                $("#cityId").before('<input placeholder="<?php echo osc_esc_js(__('Enter a city', 'zara')); ?>" type="text" name="cityId" id="city" />').remove();;

                $("#region").val('').attr('disabled',false);
                $("#city").val('').attr('disabled',true);
              }

              $("#cityId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>');
            }
          });

        } else {

          // Country is empty
          $("#region").before('<select name="sRegion" id="regionId"><option selected="selected" value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option></select>').remove();
          $("#regionId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a region', 'zara')); ?></option>').attr('disabled',true);

          $("#city").before('<select name="cityId" id="cityId" ><option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
          $("#cityId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>').attr('disabled',true);
         }
      });



      // REGION SELECTION
      $("#regionId").on("change", function(){
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

                $("#city").before('<select name="cityId" id="cityId"><option value="" selected="selected"><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
                $("#cityId").val("").html(result).attr('disabled',false);

              } else {
                $("#cityId").before('<input type="text" placeholder="<?php echo osc_esc_js(__('Enter a city', 'zara')); ?>" name="cityId" id="city" />').remove().val('').attr('disabled',false);
              }

            }
          });

        } else {

          // Region is empty
          $("#city").before('<select name="cityId" id="cityId"><option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option></select>').remove();
          $("#cityId").html('<option selected="selected" value=""><?php echo osc_esc_js(__('Select a city', 'zara')); ?></option>').attr('disabled',true);
        }
      });


      // MANAGE REGION & CITY INPUTS
      $("input#region").on("change", function(){
        if( $(this).val() != '' ) {
          $("input#city").attr('disabled',false).val("");
        } else {
          $("input#city").attr('disabled',true).val("");
        }
      });


      // ONLOAD FIXES
      if($("#countryId").length != 0) {
        if( $("#countryId").attr('value') != "")  {
          $("#regionId, #region, #cityId, #city").attr('disabled',false);
        } else {
          $("#regionId, #region, #cityId, #city").attr('disabled',true);
        }
      }

      if( $("#regionId").attr('value') != "")  {
        $("#cityId, #city").attr('disabled',false);
      } else {
        $("#cityId, #city").attr('disabled',true);
      }

    });
  </script>


  <script>
    $(document).ready(function() {

      // TITLE REMAINING CHARACTERS
      var title_max = <?php echo osc_max_characters_per_title(); ?>;
      $('.add_item .title input').attr('maxlength', title_max);
      $('.add_item .title input').after('<div class="title-max-char max-char"></div>');
      $('.title-max-char').html(title_max + ' ' + '<?php echo osc_esc_js(__('characters remaining', 'zara')); ?>');

      $('ul.tabbernav li a').on('click', function(){
        var title_length = $('.add_item .title input:visible').val().length;
        var title_remaining = title_max - title_length;

        $('.title-max-char').html(title_remaining + ' ' + '<?php echo osc_esc_js(__('characters remaining', 'zara')); ?>');

        $('.title-max-char').removeClass('orange').removeClass('red');
        if(title_remaining/title_length <= 0.2 && title_remaining/title_length > 0.1) {
          $('.title-max-char').addClass('orange');
        } else if (title_remaining/title_length <= 0.1) {
          $('.title-max-char').addClass('red');
        }
      });

      $('.add_item .title input:visible').keyup(function() {
        var title_length = $(this).val().length;
        var title_remaining = title_max - title_length;

        $('.title-max-char').html(title_remaining + ' ' + '<?php echo osc_esc_js(__('characters remaining', 'zara')); ?>');

        $('.title-max-char').removeClass('orange').removeClass('red');
        if(title_remaining/title_length <= 0.2 && title_remaining/title_length > 0.1) {
          $('.title-max-char').addClass('orange');
        } else if (title_remaining/title_length <= 0.1) {
          $('.title-max-char').addClass('red');
        }
      });


      // DESCRIPTION REMAINING CHARACTERS
      var desc_max = <?php echo osc_max_characters_per_description(); ?>;
      $('.add_item .description textarea').attr('maxlength', desc_max);
      $('.add_item .description textarea').after('<div class="desc-max-char max-char"></div>');
      $('.desc-max-char').html(desc_max + ' ' + '<?php echo osc_esc_js(__('characters remaining', 'zara')); ?>');

      $('body').on('click', 'ul.tabbernav li a', function(){
        var desc_length = $('.add_item .description > textarea').val().length;
        var desc_remaining = desc_max - desc_length;

        $('.desc-max-char').html(desc_remaining + ' ' + '<?php echo osc_esc_js(__('characters remaining', 'zara')); ?>');

        $('.desc-max-char').removeClass('orange').removeClass('red');

        if(desc_remaining/desc_length <= 0.2 && desc_remaining/desc_length > 0.1) {
          $('.desc-max-char').addClass('orange');
        } else if (desc_remaining/desc_length <= 0.1) {
          $('.desc-max-char').addClass('red');
        }
      });

      $('body').on('keyup change', '.add_item .description > textarea', function() {
        var desc_length = $(this).val().length;
        var desc_remaining = desc_max - desc_length;

        $('.desc-max-char').html(desc_remaining + ' ' + '<?php echo osc_esc_js(__('characters remaining', 'zara')); ?>');

        $('.desc-max-char').removeClass('orange').removeClass('red');
        if(desc_remaining/desc_length <= 0.2 && desc_remaining/desc_length > 0.1) {
          $('.desc-max-char').addClass('orange');
        } else if (desc_remaining/desc_length <= 0.1) {
          $('.desc-max-char').addClass('red');
        }
      });
    });


    // CATEGORY CHECK IF PARENT
    <?php if(!osc_selectable_parent_categories()) { ?>
      $(document).ready(function(){
        if(typeof window['categories_' + $('#catId').val()] !== 'undefined'){
          if(eval('categories_' + $('#catId').val()) != '') {
            $('#catId').val('');
          }
        }
      });

      $('#catId').on('change', function(){
        if(typeof window['categories_' + $(this).val()] !== 'undefined'){
          if(eval('categories_' + $(this).val()) != '') {
            $(this).val('');
          }
        }
      });
    <?php } ?>


    // PLACEHOLDERS FOR TITLE AND DESCRIPTION
    $(document).ready(function(){
      $('.title input').attr('placeholder', '<?php echo osc_esc_js(__('Apple iPhone S6...', 'zara')); ?>')
      $('.description textarea').attr('placeholder', '<?php echo osc_esc_js(__('Looks like new, original package, all equipment...', 'zara')); ?>')
      $('#contactName').attr('placeholder', '<?php echo osc_esc_js(__('Nick or name', 'zara')); ?>')
      $('#cityArea').attr('placeholder', '<?php echo osc_esc_js(__('Number to reach you', 'zara')); ?>')
      $('#contactEmail').attr('placeholder', '<?php echo osc_esc_js(__('Mail address', 'zara')); ?>')
    });
  </script>

  <script>
    $(document).ready(function(){
      $('.button').click(function(){
        $('select.error').parent().addClass('error');
        $('select.valid').parent().addClass('valid');
        $('#catId').parent().find('select').removeClass('error').removeClass('valid');
        $('#catId.error').parent().find('select').last().addClass('error');
        $('#catId.valid').parent().find('select').last().addClass('valid');
      });

      $('select').change(function(){
        if($(this).val() != '' && $(this).val() != 0) {
          $(this).parent().removeClass('error');
          $(this).parent().addClass('valid');
        } else {
          $(this).parent().removeClass('valid');
          $(this).parent().addClass('error');
        }
      });
    });
  </script>


  <script type="text/javascript">
    // Set forms to active language
    $(document).ready(function(){

      var post_timer = setInterval(zara_check_lang, 250);

      function zara_check_lang() {
        if($('.tabbertab').length > 1 && $('.tabbertab.tabbertabhide').length) {
          var l_active = zaraCurrentLocale;
          l_active = l_active.trim();

          $('.tabbernav > li > a:contains("' + l_active + '")').click();

          clearInterval(post_timer);
          return;
        }
      }

    });
  </script>


  <script type="text/javascript">
    // Validation Code
    $(document).ready(function(){
      // Validate description without HTML.
      $.validator.addMethod(
        "minstriptags",
        function(value, element) {
          altered_input = strip_tags(value);
          if (altered_input.length < 3) {
              return false;
          } else {
              return true;
          }
        },
        "Description needs to be longer."
      );

      // Code for form validation
      $("form[name=item]").validate({
        rules: {
          catId: {
              required: true,
              digits: true
          },
                          price: {
              maxlength: 50
          },
          currency: "required",
                                          "photos[]": {
              accept: "png,gif,jpg,jpeg"
          },
                                          contactName: {
              minlength: 3,
              maxlength: 35
          },
          contactEmail: {
              required: true,
              email: true
          },
                          address: {
              minlength: 3,
              maxlength: 100
          }
        },
        messages: {
          catId: "<?php echo osc_esc_js(__('Choose one category.', 'zara')); ?>",
                          price: {
              maxlength: "<?php echo osc_esc_js(__('Price: no more than 50 characters.', 'zara')); ?>"
          },
          currency: "<?php echo osc_esc_js(__('Currency: make your selection.', 'zara')); ?>",
                                          "photos[]": {
              accept: "<?php echo osc_esc_js(__('Photo: must be png,gif,jpg,jpeg.', 'zara')); ?>"
          },
                                          contactName: {
              minlength: "<?php echo osc_esc_js(__('Name: enter at least 3 characters.', 'zara')); ?>",
              maxlength: "<?php echo osc_esc_js(__('Name: no more than 35 characters.', 'zara')); ?>"
          },
          contactEmail: {
              required: "<?php echo osc_esc_js(__('Email: this field is required.', 'zara')); ?>",
              email: "<?php echo osc_esc_js(__('Invalid email address.', 'zara')); ?>"
          },
                          address: {
              minlength: "<?php echo osc_esc_js(__('Address: enter at least 3 characters.', 'zara')); ?>",
              maxlength: "<?php echo osc_esc_js(__('Address: no more than 100 characters.', 'zara')); ?>"
          }
        },
        errorLabelContainer: "#error_list",
        wrapper: "li",
        invalidHandler: function(form, validator) {
            $('html,body').animate({ scrollTop: $('h1').offset().top }, { duration: 250, easing: 'swing'});
        },
        submitHandler: function(form){
          $('button[type=submit], input[type=submit]').attr('disabled', 'disabled');
          form.submit();
        }
      });
    });


    function strip_tags(html) {
      if (arguments.length < 3) {
        html=html.replace(/<\/?(?!\!)[^>]*>/gi, '');
      } else {
        var allowed = arguments[1];
        var specified = eval("["+arguments[2]+"]");
        if (allowed){
          var regex='</?(?!(' + specified.join('|') + '))\b[^>]*>';
          html=html.replace(new RegExp(regex, 'gi'), '');
        } else{
          var regex='</?(' + specified.join('|') + ')\b[^>]*>';
          html=html.replace(new RegExp(regex, 'gi'), '');
        }
      }
      return html;
    }
  </script>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>	