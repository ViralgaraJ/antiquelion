<?php
  require_once 'functions.php';
  zara_backoffice_menu(__('Settings', 'zara'));
?>


<?php
// MANAGE IMAGES
if(Params::getParam('zara_images') == 'done') { 
  $upload_dir_small = osc_themes_path() . osc_current_web_theme() . '/images/small_cat/';
  $upload_dir_large = osc_themes_path() . osc_current_web_theme() . '/images/large_cat/';

  if (!file_exists($upload_dir_small)) { mkdir($upload_dir_small, 0777, true); }
  if (!file_exists($upload_dir_large)) { mkdir($upload_dir_large, 0777, true); }

  $count_real = 0;
  for ($i=1; $i<=2000; $i++) {
    if(isset($_POST['fa-icon' .$i])) {
      $fields['fields'] = array('s_icon' => Params::getParam('fa-icon' .$i));
      $fields['aFieldsDescription'] = array();
      Category::newInstance()->updateByPrimaryKey($fields, $i);
      message_ok(__('Font Awesome icon successfully saved for category' . ' <strong>#' . $i . '</strong>' ,'zara'));
    }

    if(isset($_POST['color' .$i])) {
      $fields['fields'] = array('s_color' => Params::getParam('color' .$i));
      $fields['aFieldsDescription'] = array();
      Category::newInstance()->updateByPrimaryKey($fields, $i);
      message_ok(__('Color successfully saved for category' . ' <strong>#' . $i . '</strong>' ,'zara'));
    }

    if(isset($_FILES['small' .$i]) and $_FILES['small' .$i]['name'] <> ''){

      $file_ext   = strtolower(end(explode('.', $_FILES['small' .$i]['name'])));
      $file_name  = $i . '.' . $file_ext;
      $file_tmp   = $_FILES['small' .$i]['tmp_name'];
      $file_type  = $_FILES['small' .$i]['type'];   
      $extensions = array("png");

      if(in_array($file_ext,$extensions )=== false) {
        $errors = __('extension not allowed, only allowed extension is .png!','zara');
      } 
				
      if(empty($errors)==true){
        move_uploaded_file($file_tmp, $upload_dir_small.$file_name);
        message_ok(__('Small image #','zara') . $i . __(' uploaded successfully.','zara'));
        $count_real++;
      } else {
        message_error(__('There was error when uploading small image #','zara') . $i . ': ' .$errors);
      }
    }
  }

  $count_real = 0;
  for ($i=1; $i<=2000; $i++) {
    if(isset($_FILES['large' .$i]) and $_FILES['large' .$i]['name'] <> ''){
      $file_ext   = strtolower(end(explode('.', $_FILES['large' .$i]['name'])));
      $file_name  = $i . '.' . $file_ext;
      $file_tmp   = $_FILES['large' .$i]['tmp_name'];
      $file_type  = $_FILES['large' .$i]['type'];   
      $extensions = array("jpg");

      if(in_array($file_ext,$extensions )=== false) {
        $errors = __('extension not allowed, only allowed extension for large images is .jpg!','zara');
      }
				
      if(empty($errors)==true){
        move_uploaded_file($file_tmp, $upload_dir_large.$file_name);
        message_ok(__('Large image #','zara') . $i . __(' uploaded successfully.','zara'));
        $count_real++;
      } else {
        message_error(__('There was error when uploading large image #','zara') . $i . ': ' .$errors);
      }
    }
  }
}
?>




<div class="mb-body">
  <div class="mb-info-box" style="margin:5px 0 30px 0;">
    <div class="mb-line"><strong><?php _e('Plugins for this theme', 'zara'); ?></strong></div>
    <div class="mb-line"><?php _e('We have modified for you many plugins to fit theme design that will work without need of any modifications', 'zara'); ?>.</div>
    <div class="mb-line"><?php _e('Plugins are not delivered in theme package, must be downloaded separately', 'zara'); ?>.</div>
    <div class="mb-line" style="margin:10px 0;"><a href="https://osclasspoint.com/theme-plugins/zara_plugins_20180307_oy81dK.zip" target="_blank" class="mb-button-white"><i class="fa fa-download"></i> <?php _e('Download plugins', 'zara'); ?></a></div>
    <div class="mb-line" style="margin-top:15px;">- <?php _e('upload and extract downloaded file <strong>zara-plugins.zip</strong> into folder <strong>oc-content/plugins/</strong> on your hosting', 'zara'); ?>.</div>
    <div class="mb-line">- <?php _e('go to <strong>oc-admin > Plugins</strong> and install plugins you like', 'zara'); ?>.</div>
  </div>


  <!-- GENERAL -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-cog"></i> <?php _e('General settings', 'zara'); ?></div>

    <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php'); ?>" method="post">
      <input type="hidden" name="zara_general" value="done" />

      <div class="mb-inside">

        <div class="mb-info-box" style="margin:0 0 20px 0;">
          <div class="mb-line"><?php _e('Plugin generates backlink link added to footer.', 'zara'); ?></div>
          <div class="mb-line"><?php echo sprintf(__('You can use plugin for free with link in footer, or purchase "%s" for this product and disable link.', 'zara'), '<a target="_blank" href="https://osclasspoint.com/services/customer-services/remove-free-product-footer-link-i221">Link remover service</a>'); ?></div>
          <div class="mb-line" style="font-weight:bold;"><?php _e('Removal of link without link remover service purchased violates product license!', 'zara'); ?></div>
        </div>
        
        <?php
          $link_remover_order_id = osc_esc_html(osc_get_preference('link_remover_order_id', 'zara_theme'));
          $remove_footer_link = osc_get_preference('remove_footer_link', 'zara_theme');
        ?>

        <div class="mb-row">
          <label for="link_remover_order_id" class=""><span><?php _e('OsclassPoint Order Id', 'zara'); ?></span></label> 
          <input name="link_remover_order_id" size="20" type="text" value="<?php echo $link_remover_order_id; ?>" />

          <div class="mb-explain"><?php _e('Enter valid OsclassPoint order ID that contains "Link removal service" product.', 'zara'); ?></div>
        </div>
        
        
        <div class="mb-row <?php if($link_remover_order_id < 8000 || $link_remover_order_id > 500000) { ?>mb-disabled<?php } ?>">
          <label for="remove_footer_link" class=""><span><?php _e('Remove Backlink from Footer', 'zara'); ?></span></label> 
          <input name="remove_footer_link" type="checkbox" class="element-slide" <?php if($link_remover_order_id < 8000 || $link_remover_order_id > 100000) { ?>disabled<?php } ?> <?php echo ($remove_footer_link == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-link"><?php _e('When checked, footer backlink will be removed from footer. It require valid OsclassPoint order ID.', 'zara'); ?></div>
            <div class="mb-link"><?php _e('This plugin license allows to remove link just in case "Link remover service" was purchased for it.', 'zara'); ?></div>
            <div class="mb-link"><?php _e('By selecting this option to remove the link, you confirm that you have purchased the license removal service and that your saved order includes this service.', 'zara'); ?></div>
          </div>
        </div>
        
        
        <hr/>
        
        

        <div class="mb-row">
          <label for="phone" class="h1"><span><?php _e('Contact Number', 'zara'); ?></span></label> 
          <input size="40" name="phone" id="promote_service_id" type="text" value="<?php echo osc_esc_html( osc_get_preference('phone', 'zara_theme') ); ?>" placeholder="<?php _e('Contact number', 'zara'); ?>" />

          <div class="mb-explain"><?php _e('Leave blank to disable.', 'zara'); ?></div>
        </div>

        <div class="mb-row">
          <label for="website_name" class="h2"><span><?php _e('Website Name', 'zara'); ?></span></label> 
          <input size="40" name="website_name" id="website_name" type="text" value="<?php echo osc_esc_html( osc_get_preference('website_name', 'zara_theme') ); ?>" placeholder="<?php _e('Website Name', 'zara'); ?>" />
        </div>
        
        <div class="mb-row">
          <label for="date_format" class="h4"><span><?php _e('Date Format on Listings', 'zara'); ?></span></label> 
          <select name="date_format" id="date_format">
            <option value="m/d" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'm/d' ? 'selected="selected"' : ''); ?>>m/d (12/01)</option>
            <option value="d/m" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'd/m' ? 'selected="selected"' : ''); ?>>d/m (01/12)</option>
            <option value="m-d" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'm-d' ? 'selected="selected"' : ''); ?>>m-d (12-01)</option>
            <option value="d-m" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'd-m' ? 'selected="selected"' : ''); ?>>d-m (01-12)</option>
            <option value="j. M" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'j. M' ? 'selected="selected"' : ''); ?>>j. M (1. Dec)</option>
            <option value="M" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'M' ? 'selected="selected"' : ''); ?>>M (Dec)</option>
            <option value="F" <?php echo (osc_get_preference('date_format', 'zara_theme') == 'F' ? 'selected="selected"' : ''); ?>>F (December)</option>
          </select>

          <div class="mb-explain"><?php _e('Selected date format will be applied in all section on listings.', 'zara'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="def_view" class="h5"><span><?php _e('Default View on Search Page', 'zara'); ?></span></label> 
          <select name="def_view" id="def_view">
            <option value="0" <?php echo (osc_get_preference('def_view', 'zara_theme') == 0 ? 'selected="selected"' : ''); ?>><?php _e('Gallery view', 'zara'); ?></option>
            <option value="1" <?php echo (osc_get_preference('def_view', 'zara_theme') == 1 ? 'selected="selected"' : ''); ?>><?php _e('List view', 'zara'); ?></option>
          </select>
        </div>

        
        <div class="mb-row">
          <label for="default_logo" class="h7"><span><?php _e('Use Default Logo', 'zara'); ?></span></label> 
          <input name="default_logo" id="default_logo" class="element-slide" type="checkbox" <?php echo (osc_get_preference('default_logo', 'zara_theme') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('If you did not upload any logo yet, osclass default logo will be used.', 'zara'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="image_upload" class="h8"><span><?php _e('Use Drag & Drop Photo Uploader', 'zara'); ?></span></label> 
          <input name="image_upload" id="image_upload" class="element-slide" type="checkbox" <?php echo (osc_get_preference('image_upload', 'zara_theme') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Drag & Drop uploader is recommended specially for mobile devices.', 'zara'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="cat_icons" class="h9"><span><?php _e('Category Icons Type', 'zara'); ?></span></label> 
          <input name="cat_icons" id="cat_icons" class="element-slide" type="checkbox" <?php echo (osc_get_preference('cat_icons', 'zara_theme') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Check to ON if you want to use Font-Awesome icons instead of Small images for categories.', 'zara'); ?></div>
        </div>
        
        <div class="mb-row">
          <label for="footer_email" class="h10"><span><?php _e('Email Contact in Footer', 'zara'); ?></span></label> 
          <input size="40" name="footer_email" id="footer_email" type="text" value="<?php echo osc_esc_html( osc_get_preference('footer_email', 'zara_theme') ); ?>" placeholder="<?php _e('Contact email', 'zara'); ?>" />
        </div>
       
        <div class="mb-row">
          <label for="drop_cat" class="h11"><span><?php _e('Dropdown Subcategories on Publish', 'zara'); ?></span></label> 
          <input name="drop_cat" id="drop_cat" class="element-slide" type="checkbox" <?php echo (osc_get_preference('drop_cat', 'zara_theme') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Enable to allow cascading to select category on publish page.', 'zara'); ?></div>
        </div>

        <div class="mb-row">
          <label for="item_pager" class="h12"><span><?php _e('Photo Pager on Listing Page', 'zara'); ?></span></label> 
          <input name="item_pager" id="item_pager" class="element-slide" type="checkbox" <?php echo (osc_get_preference('item_pager', 'zara_theme') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('Thumbnails of photos will be shown on item page.', 'zara'); ?></div>
        </div>

        <div class="mb-row">
          <label for="def_cur" class="h3"><span><?php _e('Currency in Search Box', 'zara'); ?></span></label> 
          <select name="def_cur" id="def_cur">
            <?php foreach(osc_get_currencies() as $c) { ?>
              <option value="<?php echo $c['s_description']; ?>" <?php echo (osc_get_preference('def_cur', 'zara_theme') == $c['s_description'] ? 'selected="selected"' : ''); ?>><?php echo $c['s_description']; ?></option>
            <?php } ?>
          </select>
        </div>

        <div class="mb-row">
          <label for="format_cur" class="h13"><span><?php _e('Price Slider - Currency Position', 'zara'); ?></span></label> 
          <select name="format_cur" id="format_cur">
            <option value="0" <?php echo (osc_get_preference('format_cur', 'zara_theme') == 0 ? 'selected="selected"' : ''); ?>><?php _e('Before price', 'zara'); ?></option>
            <option value="1" <?php echo (osc_get_preference('format_cur', 'zara_theme') == 1 ? 'selected="selected"' : ''); ?>><?php _e('After price', 'zara'); ?></option>
            <option value="2" <?php echo (osc_get_preference('format_cur', 'zara_theme') == 2 ? 'selected="selected"' : ''); ?>><?php _e('Do not show', 'zara'); ?></option>
          </select>
        </div>

        <div class="mb-row">
          <label for="format_sep" class="h14"><span><?php _e('Price Slider - Thousands Separator', 'zara'); ?></span></label> 
          <select name="format_sep" id="format_sep">
            <option value="" <?php echo (osc_get_preference('format_sep', 'zara_theme') == '' ? 'selected="selected"' : ''); ?>><?php _e('None', 'zara'); ?></option>
            <option value="." <?php echo (osc_get_preference('format_sep', 'zara_theme') == '.' ? 'selected="selected"' : ''); ?>>.</option>
            <option value="," <?php echo (osc_get_preference('format_sep', 'zara_theme') == ',' ? 'selected="selected"' : ''); ?>>,</option>
            <option value=" " <?php echo (osc_get_preference('format_sep', 'zara_theme') == ' ' ? 'selected="selected"' : ''); ?>><?php _e('(blank)', 'zara'); ?></option>
          </select>
        </div>


        <div class="mb-row">
          <label for="latest_random" class="h16"><span><?php _e('Show Latest Items in Random Order', 'zara'); ?></span></label> 
          <input name="latest_random" id="latest_random" class="element-slide" type="checkbox" <?php echo (osc_get_preference('latest_random', 'zara_theme') == 1 ? 'checked' : ''); ?> />
        </div>

        <div class="mb-row">
          <label for="latest_picture" class="h17"><span><?php _e('Latest Items Picture Only', 'zara'); ?></span></label> 
          <input name="latest_picture" id="latest_picture" class="element-slide" type="checkbox" <?php echo (osc_get_preference('latest_picture', 'zara_theme') == 1 ? 'checked' : ''); ?> />
        </div>

        <div class="mb-row">
          <label for="latest_premium" class="h18"><span><?php _e('Latest Premium Items', 'zara'); ?></span></label> 
          <input name="latest_premium" id="latest_premium" class="element-slide" type="checkbox" <?php echo (osc_get_preference('latest_premium', 'zara_theme') == 1 ? 'checked' : ''); ?> />
        </div>

        <div class="mb-row">
          <label for="latest_category" class="h19"><span><?php _e('Category for Latest Items', 'zara'); ?></span></label> 
          <select name="latest_category" id="latest_category">
            <option value="" <?php echo (osc_get_preference('latest_category', 'zara_theme') == '' ? 'selected="selected"' : ''); ?>><?php _e('All categories', 'zara'); ?></option>

            <?php while(osc_has_categories()) { ?>
              <option value="<?php echo osc_category_id(); ?>" <?php echo (osc_get_preference('latest_category', 'zara_theme') == osc_category_id() ? 'selected="selected"' : ''); ?>><?php echo osc_category_name(); ?></option>
            <?php } ?>
          </select>
        </div>
      </div>

      <div class="mb-foot">
        <button type="submit" class="mb-button"><?php _e('Save', 'zara');?></button>
      </div>
    </form>
  </div>


  <!-- BANNERS -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-clone"></i> <?php _e('Banner settings', 'zara'); ?></div>

    <form action="<?php echo osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php'); ?>" method="post">
      <input type="hidden" name="zara_banner" value="done" />

      <div class="mb-inside">
        <div class="mb-row">
          <label for="theme_adsense" class="h28"><span><?php _e('Enable Google Adsense Banners', 'zara'); ?></span></label> 
          <input name="theme_adsense" id="theme_adsense" class="element-slide" type="checkbox" <?php echo (osc_get_preference('theme_adsense', 'zara_theme') == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain"><?php _e('When enabled, bellow banners will be shown in front page.', 'zara'); ?></div>
        </div>
        
        <?php foreach(zara_banner_list() as $b) { ?>
          <div class="mb-row">
            <label for="<?php echo $b['id']; ?>" class="h29"><span><?php echo ucwords(str_replace('_', ' ', $b['id'])); ?></span></label> 
            <textarea class="mb-textarea mb-textarea-large" name="<?php echo $b['id']; ?>" placeholder="<?php echo osc_esc_html(__('Will be shown', 'zara')); ?>: <?php echo $b['position']; ?>"><?php echo stripslashes( osc_get_preference($b['id'], 'zara_theme') ); ?></textarea>
          </div>
        <?php } ?>
      </div>

      <div class="mb-foot">
        <button type="submit" class="mb-button"><?php _e('Save', 'zara');?></button>
      </div>
    </form>
  </div>


  <!-- CATEGORY ICONS -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-photo"></i> <?php _e('Category icons settings', 'zara'); ?></div>

    <form name="promo_form" id="load_image" action="<?php echo osc_admin_render_theme_url('oc-content/themes/' . osc_current_web_theme() . '/admin/settings.php'); ?>" method="POST" enctype="multipart/form-data" >
      <input type="hidden" name="zara_images" value="done" />

      <div class="mb-inside">
        <div class="mb-table">
          <div class="mb-table-head">
            <div class="mb-col-1_2 id"><?php _e('ID', 'zara'); ?></div>
            <div class="mb-col-2_1_2 mb-align-left name"><?php _e('Name', 'zara'); ?></div>
            <div class="mb-col-1_1_2 icon"><?php _e('Has small image', 'zara'); ?></div>
            <div class="mb-col-1_1_2"><?php _e('Small image (50x30px - png)', 'zara'); ?></div>
            <div class="mb-col-1_1_2 icon"><?php _e('Has large image', 'zara'); ?></div>
            <div class="mb-col-1_1_2"><?php _e('Large image (150x250px - jpg)', 'zara'); ?></div>
            <div class="mb-col-1_1_2 mb-align-left fa-icon"><a href="http://fortawesome.github.io/Font-Awesome/icons/" target="_blank"><?php _e('Font-Awesome icon', 'zara'); ?></a></div>
            <div class="mb-col-1_1_2 mb-align-left color"><?php _e('Color', 'zara'); ?></div>
          </div>

          <?php zara_has_subcategories_special(Category::newInstance()->toTree(),  0); ?> 
        </div>
      </div>

      <div class="mb-foot">
        <button type="submit" class="mb-button"><?php _e('Save', 'zara');?></button>
      </div>
    </form>
  </div>



  <!-- HELP TOPICS -->
  <div class="mb-box" id="mb-help">
    <div class="mb-head"><i class="fa fa-question-circle"></i> <?php _e('Help', 'zara'); ?></div>

    <div class="mb-inside">
      <div class="mb-row mb-help"><span class="sup">(1)</span> <div class="h1"><?php _e('Leave blank to disable contact number. This number will be shown in theme header.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(2)</span> <div class="h2"><?php _e('Website name can be used in user menu and footer of website.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(3)</span> <div class="h3"><?php _e('Choose which currency you want to show in search menu on category/search page.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(4)</span> <div class="h4"><?php _e('Select date format that will be used on listings. This setting is valid for latest listings, search page and listing page.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(5)</span> <div class="h5"><?php _e('Select default view type for users. Listings can be shown in grid or as list. User can change view to prefered one. Note that this setting is valid for search page only.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(6)</span> <div class="h6"><?php _e('I want to help OSClass & MB themes by linking to <a href="https://osclass.osclasspoint.com/" target="_blank">Osclass</a> and <a href="https://osclasspoint.com" target="_blank">OsclassPoint.com</a> from my site', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(7)</span> <div class="h7"><?php _e('Show default logo in case you didn\'t upload one previously.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(8)</span> <div class="h8"><?php _e('Use new Drag & Drop image uploader instead old one. Note that it is required to have osclass version 3.3 or higher.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(9)</span> <div class="h9"><?php _e('Use FontAwesome icons instead of small image icons for categories on homepage', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(10)</span> <div class="h10"><?php _e('Email that will be shown in footer', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(11)</span> <div class="h11"><?php _e('Use categories/subcategories dropdown when publishing or editing listings. If unchecked, one select for categories & subcategories is used.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(12)</span> <div class="h12"><?php _e('Show photo thumbnails on listing page under image slide show (pager).', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(13)</span> <div class="h13"><?php _e('Choose position of currency symbol in price slider on search page.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(14)</span> <div class="h14"><?php _e('Choose if you want to use thousand separator in price slider on search page.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(16)</span> <div class="h16"><?php _e('Check if you want to show latest items on homepage in random order. Everytime you refresh your homepage, listings will be shuffled in random order.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(17)</span> <div class="h17"><?php _e('Check if you want to show in latest items section on homepage only listings with picture.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(18)</span> <div class="h18"><?php _e('Check if you want to show in latest items section on homepage only premium listings. When enabled, it helps to promote premium listings on your site and get more value for them.', 'zara'); ?></div></div>
      <div class="mb-row mb-help"><span class="sup">(19)</span> <div class="h19"><?php _e('Choose category from which latest items on homepage will be selected. You can choose All categories if you want to show all listings no matter in what category it is.', 'zara'); ?></div></div>

    </div>
  </div>
</div>

<?php echo zara_footer(); ?>