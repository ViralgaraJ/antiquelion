    <?php
      osc_show_widgets('footer');
      $sQuery = __('Search in', 'zara') . ' ' . osc_total_active_items() . ' ' .  __('listings', 'zara');
    ?>
  </div>
</div>

<?php osc_run_hook('footer') ; ?>

<?php if ( zara_is_demo() && osc_is_home_page() ) { ?>
  <div id="piracy" class="noselect" title="Click to hide this box">This theme is ownership of MB Themes and can be bought only on <a href="https://osclasspoint.com/graphic-themes/general/zara-osclass-theme_i64">OsclassPoint.com</a>. When bought on other site, there is no support and updates provided. Do not support stealers, support developer!</div>
  <script>$(document).ready(function(){ $('#piracy').click(function(){ $(this).fadeOut(200); }); });</script>
<?php } ?>

<?php osc_run_hook('footer_pre'); ?>

<?php if(zara_current('zc_footer') == 1) { ?>
  <div id="footer-new">
    <div class="inside">
      <?php osc_run_hook('footer_top'); ?>
      
      <div class="bottom-place">
        <?php if(osc_is_search_page()) { ?>
          <?php osc_alert_form(); ?>
        <?php } else { ?>
          <div id="n-block" class="block quick">
            <div class="head sc-click not767"> 
              <h4><?php _e('Quick publish', 'zara'); ?></h4> 
            </div>
            
            <div class="n-wrap sc-block not767">
              <form action="<?php echo osc_item_post_url_in_category(); ?>" method="post" name="add_listing" id="add_listing">
                <input type="text" value="<?php _e('Enter name of item', 'zara'); ?>" name="add_title" id="add_title" />
                <input type="submit" value="<?php _e('Publish listing', 'zara'); ?>" class="button orange-button round2" name="submitNewsletter"> 
              </form>
            </div>
              
            <div class="under not767">
              <div class="row"><?php _e('Buy & sell items with us', 'zara'); ?>:</div>
              <div class="row"><i class="fa fa-dollar"></i> <?php _e('Lower price', 'zara'); ?></div>
              <div class="row"><i class="fa fa-truck"></i> <?php _e('Fast delivery', 'zara'); ?></div>
            </div>


            <div id="footer-share" class="no-border">
              <div class="text">
                <span class="facebook"><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo osc_base_url(); ?>" title="<?php echo osc_esc_html(__('Share us on Facebook', 'zara')); ?>" target="_blank"><i class="fa fa-facebook"></i></a></span>
                <span class="pinterest"><a href="https://pinterest.com/pin/create/button/?url=<?php echo osc_base_url(); ?>/oc-content/themes/' . osc_current_web_theme() . '/images/logo.jpg&media=<?php echo osc_base_url(); ?>&description=" title="<?php echo osc_esc_html(__('Share us on Pinterest', 'zara')); ?>" target="_blank"><i class="fa fa-pinterest"></i></a></span>
                <span class="twitter"><a href="https://twitter.com/home?status=<?php echo osc_base_url(); ?>%20-%20<?php _e('your', 'zara'); ?>%20<?php _e('classifieds', 'zara'); ?>" title="<?php echo osc_esc_html(__('Tweet us', 'zara')); ?>" target="_blank"><i class="fa fa-twitter"></i></a></span>
                <span class="linkedin"><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo osc_base_url(); ?>&title=<?php echo osc_esc_html(__('My', 'zara')); ?>%20<?php echo osc_esc_html(__('classifieds', 'zara')); ?>&summary=&source=" title="<?php echo osc_esc_html(__('Share us on LinkedIn', 'zara')); ?>" target="_blank"><i class="fa fa-linkedin"></i></a></span>
              </div>
            </div>
          </div>
        <?php } ?>


        <div class="some-block not767">
          <h4><?php _e('Categories', 'zara'); ?></h4>
          <div class="text">
            <?php osc_goto_first_category(); $c = 1; ?>
            <?php while(osc_has_categories() and $c <= 8) { ?>
              <span><a href="<?php echo osc_search_category_url() ; ?>" title="<?php echo osc_esc_html(osc_category_name()); ?>"><?php echo ucfirst(osc_category_name());?></a></span>
            <?php $c++; } ?>
          </div>
        </div>

        <div class="some-block not767 not1200">
          <h4><?php _e('Information', 'zara'); ?></h4>
          <div class="text">
            <?php osc_reset_static_pages(); ?>
            <?php $pages = Page::newInstance()->listAll($indelible = 0, $b_link = 1, $locale = null, $start = null, $limit = 8); ?>

            <?php foreach($pages as $p) { ?>
              <?php View::newInstance()->_exportVariableToView('page', $p); ?>
              <span><a href="<?php echo osc_static_page_url(); ?>" title="<?php echo osc_esc_html(osc_static_page_title()); ?>"><?php echo ucfirst(osc_static_page_title());?></a></span>
            <?php } ?>
            
            <?php osc_run_hook('footer_links'); ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>


<?php if(zara_current('zc_partner') == 1) { ?>
  <div id="footer-sponsor" class="not767">
    <div class="sponsor-inside">
      <div id="sponsor">
        <div class="lead"><?php _e('Our partners', 'zara'); ?></div>

        <?php 
          $sponsor_path = osc_base_path() . 'oc-content/themes/' . osc_current_web_theme() . '/images/sponsor-logos'; 
          $sponsor_url = osc_base_url() . 'oc-content/themes/' . osc_current_web_theme() . '/images/sponsor-logos'; 
          $sponsor_images = scandir($sponsor_path);

          if(isset($sponsor_images) && !empty($sponsor_images) && $sponsor_images <> '') {
            foreach($sponsor_images as $img) {
              $ext = strtolower(pathinfo($sponsor_path . '/' . $img, PATHINFO_EXTENSION));
              $allowed_ext = array('png', 'jpg', 'jpeg', 'gif');

              if(in_array($ext, $allowed_ext)) {
                echo '<img class="sponsor-image" src="' . $sponsor_url . '/' . $img . '" alt="' . __('Our sponsor logo', 'zara') . '" />';
              }
            }
          }
        ?>
      </div>
    </div>
  </div>
<?php } ?>

<div id="footer-contact">
  <div class="contact-inside">
    <div class="top-place">
      <div class="not767">
        <a class="orang" href="<?php echo osc_base_url(); ?>"><?php echo osc_esc_html( osc_get_preference('website_name', 'zara_theme') ); ?></a> | 
        <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact', 'zara'); ?></a> | 

        <?php if(osc_get_preference('footer_email', 'zara_theme') <> '') { ?>
          <a href="mailto:<?php echo osc_esc_html(osc_get_preference('footer_email', 'zara_theme')); ?>"><?php _e('Mail us to', 'zara'); ?> <?php echo osc_get_preference('footer_email', 'zara_theme'); ?></a> | 
        <?php } ?>

        <?php
          $order_id = (int)osc_get_preference('link_remover_order_id', 'zara_theme');

          if($order_id < 8000 || $order_id > 500000 || osc_get_preference('remove_footer_link', 'zara_theme') == 0) {
            $session_mark = (isset($_SESSION['mb_support_link']) ? $_SESSION['mb_support_link'] : '');
            
            if($session_mark == '' || $session_mark == 'zar') {
              $_SESSION['mb_support_link'] = 'zar';
              ?>
                <div class="mb-support-link zar-support-link" data-plugin="zara"><a href="https://osclass-classifieds.com/">Classifieds Ads Software</a></div>
              <?php
            }
          }
        ?>
      </div>

      <div class="cop"><?php _e('Copyright', 'zara'); ?> &copy; <?php echo date("Y"); ?> <strong><?php echo osc_esc_html( osc_get_preference('website_name', 'zara_theme') ); ?></strong></div>
    </div>
  </div>
</div>

<?php osc_run_hook('footer_after'); ?>

<div id="rs-cover"></div>
<div id="ro-cover"></div>

<div id="o-box" class="closed resp">
  <div class="o-lead">
    <?php if( osc_is_web_user_logged_in() ) { ?>
      <span><?php echo __('Hello', 'zara') . ' ' . osc_logged_user_name() . '!'; ?></span>
    <?php } else { ?>
      <span><?php _e('Hello!', 'zara'); ?></span> <a href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in', 'zara'); ?></a> <span><?php _e('to get more.', 'zara'); ?></span>
    <?php } ?>
  </div>

  <div class="o-body">
    <a href="<?php echo osc_base_url(); ?>"><i class="fa fa-home"></i><?php _e('Home', 'zara'); ?></a>
    <a class="o-border-bottom" href="<?php echo osc_search_url(); ?>"><i class="fa fa-search"></i><?php _e('Search', 'zara'); ?></a>

    <?php if(function_exists('fi_list_items')) { ?>
      <a href="<?php echo osc_route_url('favorite-lists', array('list-id' => '0', 'current-update' => '0', 'notification-update' => '0', 'list-remove' => '0', 'iPage' => '0')); ?>"><i class="fa fa-star-o"></i><?php _e('My favorite items', 'zara'); ?></a>
    <?php } ?>

    <a href="<?php echo osc_user_dashboard_url(); ?>"><i class="fa fa-folder-o"></i><?php _e('My account', 'zara'); ?></a>

    <?php if(function_exists('im_messages')) { ?>
      <?php echo im_messages(); ?>
    <?php } ?>

    <a href="<?php echo osc_user_alerts_url(); ?>"><i class="fa fa-bell-o"></i><?php _e('My alerts', 'zara'); ?></a>
    <a href="<?php echo osc_user_profile_url(); ?>"><i class="fa fa-edit"></i><?php _e('My personal info', 'zara'); ?></a>
    <a href="<?php echo osc_user_list_items_url(); ?>"><i class="fa fa-list-ul"></i><?php _e('My listings', 'zara'); ?></a>
    <?php if( osc_is_web_user_logged_in() ) { ?><a href="<?php echo osc_user_public_profile_url(osc_logged_user_id()); ?>"><i class="fa fa-copy"></i><?php _e('My public profile', 'zara'); ?></a><?php } ?>
    <?php if( osc_is_web_user_logged_in() ) { ?><a href="<?php echo osc_user_logout_url(); ?>"><i class="fa fa-sign-out"></i><?php _e('Log out', 'zara'); ?></a><?php } ?>

    <a class="o-border-top" href="<?php echo osc_contact_url(); ?>"><i class="fa fa-envelope-o"></i><?php _e('Mail us', 'zara'); ?></a>

    <?php if(osc_get_preference('phone', 'zara_theme') <> '') { ?>
      <a href="tel:<?php echo osc_esc_html( osc_get_preference('phone', 'zara_theme') ); ?>"><i class="fa fa-phone"></i><?php _e('Call us', 'zara'); ?></a>
    <?php } ?>


    <?php if ( osc_count_web_enabled_locales() > 1) { ?>
      <?php $current_locale = mb_get_current_user_locale(); ?>

      <?php osc_goto_first_locale(); ?>

      <div class="o-elem">
        <div class="o-head sc-click">
          <?php _e('Language', 'zara'); ?>
        </div>

        <div class="o-lang sc-block">
          <?php while ( osc_has_web_enabled_locales() ) { ?>
            <a id="<?php echo osc_locale_code() ; ?>" href="<?php echo osc_change_language_url ( osc_locale_code() ) ; ?>" <?php if (osc_locale_code() == $current_locale['pk_c_code'] ) { ?>class="current"<?php } ?>>
              <span><?php echo osc_locale_field('s_short_name'); ?></span>
              <?php if (osc_locale_code() == $current_locale['pk_c_code']) { ?>
                <i class="fa fa-check"></i>
              <?php } ?>
            </a>
          <?php } ?>
        </div>
      </div>
    <?php } ?>


    <?php $pages = Page::newInstance()->listAll($indelible = 0, $b_link = null, $locale = null, $start = null, $limit = 10); ?>

    <?php if(is_array($pages) && count($pages) > 0) { ?>
      <?php $current_locale = mb_get_current_user_locale(); ?>

      <div class="o-elem">
        <div class="o-head sc-click">
          <?php _e('Information', 'zara'); ?>
        </div>

        <div class="o-info sc-block">
          <?php foreach($pages as $p) { ?>
            <?php View::newInstance()->_exportVariableToView('page', $p); ?>

            <a href="<?php echo osc_static_page_url(); ?>">
              <span><?php echo osc_static_page_title(); ?></span>
            </a>
          <?php } ?>
        </div>
      </div>
    <?php } ?>

  </div>
</div>


<div id="s-box" class="closed resp">
  <form action="<?php echo osc_base_url(true); ?>" method="get" onsubmit="" class="nocsrf">
    <input type="hidden" name="page" value="search" />
    <input type="hidden" name="cookie-action-side" id="cookie-action-side" value="" />
    <input type="hidden" name="sOrder" value="<?php echo osc_search_order(); ?>" />
    <input type="hidden" name="iOrderType" value="<?php $allowedTypesForSorting = Search::getAllowedTypesForSorting() ; echo isset($allowedTypesForSorting[osc_search_order_type()]) ? $allowedTypesForSorting[osc_search_order_type()] : ''; ?>" />
    <?php foreach(osc_search_user() as $userId) { ?>
      <input type="hidden" name="sUser[]" value="<?php echo $userId; ?>" />
    <?php } ?>
    <input type="hidden" name="sCompany" class="sCompany" id="sCompany" value="<?php echo Params::getParam('sCompany');?>" />
    <input type="hidden" id="priceMin" name="sPriceMin" value="<?php echo Params::getParam('sPriceMin'); ?>" size="6" maxlength="6" />
    <input type="hidden" id="priceMax" name="sPriceMax" value="<?php echo Params::getParam('sPriceMax'); ?>" size="6" maxlength="6" />

    <fieldset>
      <div class="s-lead"><span><?php _e('Refine search', 'zara'); ?></span></div>

      <div class="s-elem">
        <div class="s-head sc-click">
          <?php _e('Keyword', 'zara'); ?>
        </div>

        <div class="s-body sc-block">
          <input type="text" name="sPattern" id="query" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php echo osc_esc_html(__('I\'m looking for...', 'zara')); ?>" />
        </div>
      </div>

      <div class="s-elem">
        <div class="s-head sc-click">
          <?php _e('Category', 'zara'); ?>
        </div>

        <div class="s-body sc-block">
          <?php
            $cat_id = Params::getParam('sCategory');
            $cat = array('pk_i_id' => $cat_id); 
          ?>

          <?php osc_categories_select('sCategory', $cat, __('Select a category', 'zara')); ?>
        </div>
      </div>

      <div class="s-elem">
        <div class="s-head sc-click">
          <?php _e('Location', 'zara'); ?>
        </div>

        <div class="s-body sc-block">
          <?php $aCountries = Country::newInstance()->listAll(); ?>
        
          <div class="s-row" <?php if(count($aCountries) <= 1 ) {?>style="display:none;"<?php } ?>>
            <h4><?php _e('Country', 'zara') ; ?></h4>

            <?php
              // IF THERE IS JUST 1 COUNTRY, PRE-SELECT IT TO ENABLE REGION DROPDOWN
              $s_country = Country::newInstance()->listAll();
              if(is_array($s_country) && count($s_country) <= 1) {
                $s_country = isset($s_country[0]) ? $s_country[0] : '';
              }
            ?>

            <select id="countryId" name="sCountry">
              <option value=""><?php _e('Select a country', 'zara'); ?></option>

              <?php if(is_array($aCountries) && count($aCountries) > 0) { ?>
                <?php foreach($aCountries as $country) {?>
                  <?php
                    if(!isset($country['pk_c_code'])) {
                      $country['pk_c_code'] = '';
                    }
                  ?>

                  <option value="<?php echo $country['pk_c_code']; ?>" <?php if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == $country['pk_c_code'] or Params::getParam('sCountry') == $country['s_name'] or Params::getParam('sCountry') == @$country['s_name_native']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] == $country['pk_c_code']) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($country, 's_name') ; ?></option>

                  <?php 
                    if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == $country['pk_c_code'] or Params::getParam('sCountry') == $country['s_name'] or Params::getParam('sCountry') == @$country['s_name_native']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] == $country['pk_c_code']) {
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
              if(osc_count_countries() <= 1) {
                $aRegions = Region::newInstance()->findByCountry(@$s_country['pk_c_code']);
              } else {
                $aRegions = '';
              }
            }
          ?>

          <div class="s-row">
            <h4><?php _e('Region', 'zara') ; ?></h4>

            <?php if(is_array($aRegions) && count($aRegions) >= 1 ) { ?>
              <select id="regionId" name="sRegion" <?php if(Params::getParam('sRegion') == '' && Params::getParam('region')) {?>disabled<?php } ?>>
                <option value=""><?php _e('Select a region', 'zara'); ?></option>
                
                <?php if(isset($aRegions) && !empty($aRegions) && $aRegions <> '' && count($aRegions) >= 1) { ?>
                  <?php foreach ($aRegions as $region) {?>
                    <option value="<?php echo $region['pk_i_id']; ?>" <?php if(Params::getParam('sRegion') != '' && (Params::getParam('sRegion') == $region['pk_i_id'] or Params::getParam('sRegion') == $region['s_name'] or Params::getParam('sRegion') == @$region['s_name_native'])) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($region, 's_name'); ?></option>
                  <?php } ?>
                <?php } ?>
              </select>
            <?php } else { ?>
              <input type="text" name="sRegion" id="sRegion-side" value="<?php echo Params::getParam('sRegion'); ?>" placeholder="<?php _e('Enter a region', 'zara'); ?>" />
            <?php } ?>
          </div>
        
          <?php 
            $current_region = Params::getParam('region') <> '' ? Params::getParam('region') : Params::getParam('sRegion');

            if(!is_numeric($current_region) && $current_region <> '') {
              $reg = Region::newInstance()->findByName($current_region);
              $current_region = $reg['pk_i_id'];
            }

            if($current_region <> '' && !empty($current_region)) {
              $aCities = City::newInstance()->findByRegion($current_region);
            } else {
              $aCities = '';
            }
          ?> 

          <div class="s-row last">
            <h4><?php _e('City', 'zara') ; ?></h4>

            <?php if(is_array($aCities) && count($aCities) >= 1 && !empty($aCities) ) { ?>
              <select name="sCity" id="cityId" <?php if(Params::getParam('sCity') == '' && Params::getParam('city') == '') {?>disabled<?php } ?>> 
                <option value=""><?php _e('Select a city', 'zara'); ?></option>
          
                <?php if(isset($aCities) && !empty($aCities) && $aCities <> '' && count($aCities) >= 1) { ?>
                  <?php foreach ($aCities as $city) {?>
                    <option value="<?php echo $city['pk_i_id']; ?>" <?php if(Params::getParam('sCity') != '' && (Params::getParam('sCity') == $city['pk_i_id'] or Params::getParam('sCity') == $city['s_name'] or Params::getParam('sCity') == @$city['s_name_native'])) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($city, 's_name'); ?></option>
                  <?php } ?>
                <?php } ?>
              </select>
            <?php } else { ?>
              <input type="text" name="sCity" id="sCity-side" value="<?php echo Params::getParam('sCity'); ?>" placeholder="<?php _e('Enter a city', 'zara'); ?>" />
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="s-elem check">
        <div class="s-head sc-click">
          <?php _e('Pictures', 'zara'); ?>
        </div>

        <div class="s-body sc-block">
          <?php if( osc_images_enabled_at_items() ) { ?>
            <input type="checkbox" name="bPic" id="withPicture" value="1" <?php echo (osc_search_has_pic() ? 'checked="checked"' : ''); ?> />
            <label for="withPicture" class="with-pic-label"><?php _e('Only listings with picture', 'zara') ; ?></label>
          <?php } ?>
        </div>
      </div>


      <?php if( osc_price_enabled_at_items() ) { ?>
        <div class="s-elem price">
          <div class="s-head sc-click">
            <?php _e('Price', 'zara'); ?>
          </div>

          <div class="s-body sc-block">
            <h4><?php _e('Price', 'zara'); ?>:</h4>
            <div id="amount-min"></div><div id="amount-del">-</div><div id="amount-max"></div>
            <div id="slider-range"></div>
          </div>
        </div>
      <?php } ?>


      <div class="s-elem s-hooks">
        <div class="s-head sc-click">
          <?php _e('Advanced', 'zara'); ?>
        </div>

        <div class="s-body sc-block s-hooks">
          <div class="sidebar-hooks">
            <?php 
              GLOBAL $search_hooks;
              echo $search_hooks;
            ?>
          </div>
        </div>
      </div>

      <div class="s-elem buttons">
        <button type="submit" id="blue"><?php _e('Search', 'zara') ; ?></button>
        <a href="<?php echo osc_search_url(array('page' => 'search'));?>" class="clear-search" title="<?php echo osc_esc_html(__('Clear search parameters', 'zara')); ?>"><i class="fa fa-eraser"></i><?php _e('Clean search', 'zara'); ?></a>
      </div>

    </fieldset>
  </form>
</div>



<script>
  <?php if(osc_is_search_page()) { ?>
    var addQuery = "<?php echo osc_esc_js(AlertForm::default_email_text()); ?>";
  <?php } else { ?>
    var addQuery = "<?php echo osc_esc_js(__('Enter name of item', 'zara')); ?>";
  <?php } ?>
</script>



<!-- JAVASCRIPT FOR PRICE SLIDER IN SEARCH BOX -->
<script>
  <?php
    $search_cat_id = osc_search_category_id();
    $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : '';

    $max = zara_max_price($search_cat_id, Params::getParam('sCountry'), Params::getParam('sRegion'), Params::getParam('sCity'));
    $max_price = ceil($max['max_price']/50)*50;
    $max_currency = $max['max_currency'];
    $format_sep = osc_get_preference('format_sep', 'zara_theme');
    $format_cur = osc_get_preference('format_cur', 'zara_theme');

    if($format_cur == 0) {
      $format_prefix = $max_currency;
      $format_suffix = '';
    } else if ($format_cur == 1) {
      $format_prefix = '';
      $format_suffix = $max_currency;
    } else {
      $format_prefix = '';
      $format_suffix = '';
    }
  ?>

  $(function() {
    $( "#slider-range" ).slider({
      range: true,
      step: <?php echo round($max_price/25, 0); ?>,
      min: 0,
      max: <?php echo $max_price; ?>,
      values: [<?php echo (Params::getParam('sPriceMin') <> '' ? Params::getParam('sPriceMin') : '0'); ?>, <?php echo (Params::getParam('sPriceMax') <> '' ? Params::getParam('sPriceMax') : $max_price); ?> ],
      slide: function( event, ui ) {
        if(ui.values[ 0 ] <= 0) {
          $( "#amount-min" ).text( "<?php echo osc_esc_js(__('Free', 'zara')); ?>" );
          $( "#amount-max" ).text( ui.values[ 1 ] );
          $( "#amount-max" ).priceFormat({prefix: '<?php echo $format_prefix; ?>', suffix: '<?php echo $format_suffix; ?>', thousandsSeparator: '<?php echo $format_sep; ?>', centsLimit: 0});
        } else {
          $( "#amount-min" ).text( ui.values[ 0 ] );
          $( "#amount-max" ).text( ui.values[ 1 ] );
          $( "#amount-min" ).priceFormat({prefix: '<?php echo $format_prefix; ?>', suffix: '<?php echo $format_suffix; ?>', thousandsSeparator: '<?php echo $format_sep; ?>', centsLimit: 0});
          $( "#amount-max" ).priceFormat({prefix: '<?php echo $format_prefix; ?>', suffix: '<?php echo $format_suffix; ?>', thousandsSeparator: '<?php echo $format_sep; ?>', centsLimit: 0});
        }

        if(ui.values[ 0 ] <= 0) { 
          $( "#priceMin" ).val('');
        } else {
          $( "#priceMin" ).val(ui.values[ 0 ]);
        }

        if(ui.values[ 1 ] >= <?php echo $max_price; ?>) {
          $( "#priceMax" ).val('');
        } else {
          $( "#priceMax" ).val(ui.values[ 1 ]);
        }

        $("#cookie-action-side").val('done');
      }
    });
    

    if( $( "#slider-range" ).slider( "values", 0 ) <= 0 ) {
      if( $( "#slider-range" ).slider( "values", 1 ) <= 0 ) {
        $( "#amount-min" ).text( "<?php echo osc_esc_js(__('Free', 'zara')); ?>" );
        $( "#amount-max" ).text( "" );
        $( "#amount-del" ).hide(0);
      } else {
        $( "#amount-min" ).text( "<?php echo osc_esc_js(__('Free', 'zara')); ?>" );
        $( "#amount-max" ).text( $( "#slider-range" ).slider( "values", 1 ) );
        $( "#amount-del" ).show(0);
        $( "#amount-max" ).priceFormat({prefix: '<?php echo $format_prefix; ?>', suffix: '<?php echo $format_suffix; ?>', thousandsSeparator: '<?php echo $format_sep; ?>', centsLimit: 0});
      }
    } else {
      $( "#amount-min" ).text( $( "#slider-range" ).slider( "values", 0 ) );
      $( "#amount-max" ).text( $( "#slider-range" ).slider( "values", 1 ) );
      $( "#amount-min" ).priceFormat({prefix: '<?php echo $format_prefix; ?>', suffix: '<?php echo $format_suffix; ?>', thousandsSeparator: '<?php echo $format_sep; ?>', centsLimit: 0});
      $( "#amount-max" ).priceFormat({prefix: '<?php echo $format_prefix; ?>', suffix: '<?php echo $format_suffix; ?>', thousandsSeparator: '<?php echo $format_sep; ?>', centsLimit: 0});
    }
  });
</script>



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
                  if(data[key].s_name_native != '' && data[key].s_name_native != 'null' &&data[key].s_name_native != null && locationsNative == "1") {
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