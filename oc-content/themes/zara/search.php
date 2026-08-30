<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <?php if( osc_count_items() == 0 || Params::getParam('iPage') > 0 || stripos($_SERVER['REQUEST_URI'], 'search') )  { ?>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="googlebot" content="noindex, nofollow" />
  <?php } else { ?>
    <meta name="robots" content="index, follow" />
    <meta name="googlebot" content="index, follow" />
  <?php } ?>
</head>

<body id="body-search">
<?php osc_current_web_theme_path('header.php'); ?>

<div class="content list">
  <?php 
    // Get positioning
    if(zara_current('zc_cat_sort') <> '' && zara_current('zc_cat_sort') <> '1') {
      $zc_position_cat = explode(',', zara_current('zc_cat_sort')); 
    } else {
      $zc_position_cat = array(1,2); 
    }
    
    // Get search hooks
    GLOBAL $search_hooks;
    ob_start(); 

    if(osc_search_category_id()) { 
      osc_run_hook('search_form', osc_search_category_id());
    } else { 
      osc_run_hook('search_form');
    }
    
    //$search_hooks = trim(ob_get_clean());
    //ob_end_flush();

    $search_hooks = trim(ob_get_contents());
    ob_end_clean();

    $search_hooks = trim($search_hooks);
  ?>

  <div id="main" class="search <?php if($zc_position_cat[0] == 1) { ?>side-main<?php } else { ?>main-side<?php } ?>">

    <!-- TOP SEARCH TITLE -->
    <?php
      $search_cat_id = osc_search_category_id();
      $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : '';
    ?>


    <!-- SEARCH FILTERS - SORT / COMPANY / VIEW -->
    <div class="search-filter-head resp is767"><?php _e('Filter', 'zara'); ?></div>
    <div class="search-sort-head sc-click resp is767"><?php _e('Sort listings', 'zara'); ?></div>
    <div id="search-sort" class="sc-block">
      <div class="user-company-change">
        <div class="all <?php if(Params::getParam('sCompany') == '' or Params::getParam('sCompany') == null) { ?>active<?php } ?>"><span><?php _e('All results', 'zara'); ?></span></div>
        <div class="individual <?php if(Params::getParam('sCompany') == '0') { ?>active<?php } ?>"><span><?php _e('Personal', 'zara'); ?></span></div>
        <div class="company <?php if(Params::getParam('sCompany') == '1') { ?>active<?php } ?>"><span><?php _e('Company', 'zara'); ?></span></div>
      </div>

      <div class="list-grid">
        <?php $def_view = osc_get_preference('def_view', 'zara_theme') == 0 ? 'gallery' : 'list'; ?>
        <?php $old_show = Params::getParam('sShowAs') == '' ? $def_view : Params::getParam('sShowAs'); ?>
        <?php $params['sShowAs'] = 'list'; ?>
        <a href="<?php echo osc_update_search_url($params); ?>" title="<?php echo osc_esc_html(__('Switch to list view', 'zara')); ?>" <?php echo ($old_show == $params['sShowAs'] ? 'class="active"' : ''); ?>><i class="fa fa-th-list"></i></a>
        <?php $params['sShowAs'] = 'gallery'; ?>
        <a href="<?php echo osc_update_search_url($params); ?>" title="<?php echo osc_esc_html(__('Switch to grid view', 'zara')); ?>" <?php echo ($old_show == $params['sShowAs'] ? 'class="active"' : ''); ?>><i class="fa fa-th"></i></a>
      </div>

      <div class="counter">
        <?php echo osc_default_results_per_page_at_search()*(osc_search_page())+1;?> - <?php echo osc_default_results_per_page_at_search()*(osc_search_page()+1)+osc_count_items()-osc_default_results_per_page_at_search();?> <?php echo ' ' . __('of', 'zara') . ' '; ?> <?php echo osc_search_total_items() ?> <?php echo (osc_search_total_items() == 1 ? __('listing', 'zara') : __('listings', 'zara')); ?>                                                           
      </div>

      <div class="sort-it">
        <div class="sort-title">
          <div class="title-keep noselect">
            <?php $orders = osc_list_orders(); ?>
            <?php $current_order = osc_search_order(); ?>
            <?php foreach($orders as $label => $params) { ?>
              <?php $orderType = ($params['iOrderType'] == 'asc') ? '0' : '1'; ?>
              <?php if(osc_search_order() == $params['sOrder'] && osc_search_order_type() == $orderType) { ?>
                <?php if($current_order == 'dt_pub_date') { ?>
                  <i class="fa fa-sort-numeric-asc"></i>
                <?php } else { ?>
                  <?php if($orderType == 0) { ?>
                    <i class="fa fa-sort-amount-asc"></i>
                  <?php } else { ?>
                    <i class="fa fa-sort-amount-desc"></i>
                  <?php } ?>
                <?php } ?>

                <span>
                  <span class="non-resp not1200"><?php echo $label; ?></span>
                  <span class="resp is1200"><?php _e('Sort', 'zara'); ?></span>
                </span>
              <?php } ?>
            <?php } ?>
          </div>

          <div id="sort-wrap">
            <div class="sort-content">
              <div class="info"><?php _e('Select sorting', 'zara'); ?></div>

              <?php $i = 0; ?>
              <?php foreach($orders as $label => $params) { ?>
                <?php $orderType = ($params['iOrderType'] == 'asc') ? '0' : '1'; ?>
                <?php if(osc_search_order() == $params['sOrder'] && osc_search_order_type() == $orderType) { ?>
                  <a class="current" href="<?php echo osc_update_search_url($params) ; ?>"><span><?php echo $label; ?></span></a>
                <?php } else { ?>
                  <a href="<?php echo osc_update_search_url($params) ; ?>"><span><?php echo $label; ?></span></a>
                <?php } ?>
                <?php $i++; ?>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="search-items">
      <?php osc_run_hook('search_items_top'); ?>
      <?php osc_run_hook('search_items_filter'); ?>
      
      <?php if(osc_count_items() == 0) { ?>
        <div class="empty" ><?php printf(__('There are no results matching "%s"', 'zara'), osc_search_pattern()) ; ?></div>
      <?php } else { ?>
        <?php echo zara_banner('search_top'); ?>
        <?php require($old_show == 'list' ? 'search_list.php' : 'search_gallery.php') ; ?>
      <?php } ?>

      <?php osc_run_hook('search_items_bottom'); ?>
      
      <div class="paginate">
        <?php echo osc_search_pagination(); ?>

        <?php if(osc_search_pagination() <> '') { ?>
          <div class="lead"><?php _e('Select page', 'zara'); ?>:</div>
        <?php } ?>
      </div>

      <?php echo zara_banner('search_bottom'); ?>

      <div class="clear"></div>
    </div>
  </div>

  <div id="sidebar" class="noselect <?php if($zc_position_cat[0] == 1) { ?>side-main<?php } else { ?>main-side<?php } ?>">
    <div id="sidebar-search" class="round3">
      <?php osc_run_hook('search_sidebar_pre'); ?>
      
      <form action="<?php echo osc_base_url(true); ?>" method="get" onsubmit="" class="nocsrf">
        <input type="hidden" name="page" value="search" />
        <input type="hidden" name="cookie-action-side" id="cookie-action-side" value="" />
        <input type="hidden" name="sCategory" value="<?php echo osc_esc_html(Params::getParam('sCategory')); ?>" />
        <input type="hidden" name="sOrder" value="<?php echo osc_search_order(); ?>" />
        <input type="hidden" name="iOrderType" value="<?php $allowedTypesForSorting = Search::getAllowedTypesForSorting() ; echo isset($allowedTypesForSorting[osc_search_order_type()]) ? $allowedTypesForSorting[osc_search_order_type()] : ''; ?>" />
        <?php foreach(osc_search_user() as $userId) { ?>
          <input type="hidden" name="sUser[]" value="<?php echo $userId; ?>" />
        <?php } ?>
        <input type="hidden" name="sCompany" class="sCompany" id="sCompany" value="<?php echo osc_esc_html(Params::getParam('sCompany')); ?>" />
        <input type="hidden" id="priceMin" name="sPriceMin" value="<?php echo osc_esc_html(Params::getParam('sPriceMin')); ?>" size="6" maxlength="6" />
        <input type="hidden" id="priceMax" name="sPriceMax" value="<?php echo osc_esc_html(Params::getParam('sPriceMax')); ?>" size="6" maxlength="6" />

        <h3 class="head">
          <?php _e('Search', 'zara'); ?>

          <div id="show-hide" class="closed"></div>
        </h3>

        <div class="search-wrap">
          <fieldset class="box location">
            <?php osc_run_hook('search_sidebar_top'); ?>
            
            <div class="row">
              <h4><?php _e('Keyword', 'zara') ; ?></h4>                            
              <input type="text" name="sPattern" id="query" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php echo osc_esc_html(__('I\'m looking for...', 'zara')); ?>" />
            </div>

            <?php $aCountries = Country::newInstance()->listAll(); ?>
            
            <div class="row" <?php if(count($aCountries) <= 1 ) {?>style="display:none;"<?php } ?>>
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

                <?php foreach ($aCountries as $country) {?>
                  <option value="<?php echo isset($country['pk_c_code']) ? $country['pk_c_code'] : ''; ?>" <?php if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == @$country['pk_c_code'] or Params::getParam('sCountry') == @$country['s_name'] or Params::getParam('sCountry') == @$country['s_name_native']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] == @$country['pk_c_code']) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($country, 's_name'); ?></option>

                  <?php 
                    if(Params::getParam('sCountry') <> '' && (Params::getParam('sCountry') == @$country['pk_c_code'] or Params::getParam('sCountry') == @$country['s_name'] or Params::getParam('sCountry') == @$country['s_name_native']) or @$s_country['pk_c_code'] <> '' && @$s_country['pk_c_code'] == @$country['pk_c_code']) {
                      $current_country_code = isset($country['pk_c_code']) ? $country['pk_c_code'] : '';
                    } 
                  ?>
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

            <div class="row">
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
                <!--<input type="text" name="sRegion" id="sRegion-side" value="<?php echo Params::getParam('sRegion'); ?>" placeholder="<?php echo osc_esc_html(__('Enter a region', 'zara')); ?>" />-->
                <select id="regionId" name="sRegion" disabled><option value=""><?php _e('Select a region', 'zara'); ?></option></select>
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

            <div class="row">
              <h4><?php _e('City', 'zara') ; ?></h4>

              <?php if(is_array($aCities) && count($aCities) >= 1 && !empty($aCities)) { ?>
                <select name="sCity" id="cityId" <?php if(Params::getParam('sCity') == '' && Params::getParam('city') == '') {?>disabled<?php } ?>> 
                  <option value=""><?php _e('Select a city', 'zara'); ?></option>
            
                  <?php if(isset($aCities) && !empty($aCities) && $aCities <> '' && count($aCities) >= 1) { ?>
                    <?php foreach ($aCities as $city) {?>
                      <option value="<?php echo $city['pk_i_id']; ?>" <?php if(Params::getParam('sCity') != '' && (Params::getParam('sCity') == $city['pk_i_id'] or Params::getParam('sCity') == $city['s_name'] or Params::getParam('sCity') == @$city['s_name_native'])) { ?>selected="selected"<?php } ?>><?php echo osc_location_native_name_selector($city, 's_name'); ?></option>
                    <?php } ?>
                  <?php } ?>
                </select>
              <?php } else { ?>
                <!--<input type="text" name="sCity" id="sCity-side" value="<?php echo Params::getParam('sCity'); ?>" placeholder="<?php echo osc_esc_html(__('Enter a city', 'zara')); ?>" />-->
                <select id="cityId" name="sCity" disabled><option value=""><?php _e('Select a city', 'zara'); ?></option></select>
              <?php } ?>
            </div>
          </fieldset>

          <fieldset class="img-check">
            <?php if( osc_images_enabled_at_items() ) { ?>
              <div class="row checkboxes">
                <input type="checkbox" name="bPic" id="withPicture" value="1" <?php echo (osc_search_has_pic() ? 'checked="checked"' : ''); ?> />
                <label for="withPicture" class="with-pic-label">
                  <span class="non-resp"><?php _e('Show only listings with photo', 'zara'); ?></span>
                  <span class="resp is1200 is767"><?php _e('Only with photo', 'zara'); ?></span>
                </label>
              </div>
            <?php } ?>
          </fieldset>

          <?php if( osc_price_enabled_at_items() ) { ?>
            <fieldset class="price-box">
              <div class="row price">
                <h4><?php _e('Price', 'zara'); ?>:</h4>
                <div id="amount-min"></div><div id="amount-del">-</div><div id="amount-max"></div>
              </div>

              <div id="slider-range"></div>
            </fieldset>
          <?php } ?>

          <?php if($search_hooks != '') { ?>
            <div class="sidebar-hooks">
              <?php echo $search_hooks; ?>
            </div>
          <?php } ?>

          <div class="button-wrap">
            <button type="submit" class="btn btn-primary" id="search-button"><?php _e('Search', 'zara') ; ?></button>
          </div>
        </div>

        <div class="clear"></div>
        
        <?php osc_run_hook('search_sidebar_bottom'); ?>
      </form>
    </div>

    <div class="clear"></div>

    <?php echo zara_banner('search_sidebar'); ?>
    <?php osc_run_hook('search_sidebar_after'); ?>
  </div>
</div>


<?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>