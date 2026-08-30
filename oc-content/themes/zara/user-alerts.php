<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo zara_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="body-user-alerts">
  <?php osc_current_web_theme_path('header.php') ; ?>
  <div class="content user_account">
    <div class="user-menu-sh resp is767 sc-click"><?php _e('User menu', 'zara'); ?></div>

    <div id="sidebar" class="sc-block">
      <?php if(function_exists('profile_picture_show')) { ?>
        <div class="user-side-img">
          <a href="#" id="pict-update">
            <?php profile_picture_show(null, null, 80); ?>
          </a>
        </div>
      <?php } ?>

      <?php echo osc_private_user_menu(); ?>
      <?php if(function_exists('profile_picture_upload')) { profile_picture_upload(); } ?>
    </div>

    <div id="main">
      <h3 class="title_block user-alerts"><?php _e('Your alerts', 'zara'); ?></span></h3>
      <?php if(osc_count_alerts() == 0) { ?>
        <div class="empty"><?php _e('You do not have any alerts yet', 'zara'); ?></div>
      <?php } else { ?>
        <?php $c = 1; ?>
        <?php while(osc_has_alerts()) { ?>
          <?php 
            // PARAMETERS IN ALERT: price_min, price_max, aCategories, city_areas, cities, regions, countries, sPattern
            $alert_details = View::newInstance()->_current('alerts');
            $alert_details = (array)json_decode($alert_details['s_search']);


            // CONNECTION & DB INFO
            $conn = DBConnectionClass::newInstance();
            $data = $conn->getOsclassDb();
            $comm = new DBCommandClass($data);
            $db_prefix = DB_TABLE_PREFIX;


            // COUNTRIES
            $c_filter = $alert_details['countries'];
            $c_filter = isset($c_filter[0]) ? $c_filter[0] : '';
            $c_filter = str_replace('item_location.fk_c_country_code', 'country.pk_c_code', $c_filter);

            $c_query = "SELECT * FROM {$db_prefix}t_country WHERE " . $c_filter;
            $c_result = $comm->query($c_query);

            if( !$c_result ) { 
              $c_prepare = array();
            } else {
              $c_prepare = $c_result->result();
            }
   

            // REGIONS
            $r_filter = $alert_details['regions'];
            $r_filter = isset($r_filter[0]) ? $r_filter[0] : '';
            $r_filter = str_replace('item_location.fk_i_region_id', 'region.pk_i_id', $r_filter);

            $r_query = "SELECT * FROM {$db_prefix}t_region WHERE " . $r_filter;
            $r_result = $comm->query($r_query);

            if( !$r_result ) { 
              $r_prepare = array();
            } else {
              $r_prepare = $r_result->result();
            }


            // CITIES
            $t_filter = $alert_details['cities'];
            $t_filter = isset($t_filter[0]) ? $t_filter[0] : '';
            $t_filter = str_replace('item_location.fk_i_city_id', 'city.pk_i_id', $t_filter);

            $t_query = "SELECT * FROM {$db_prefix}t_city WHERE " . $t_filter;
            $t_result = $comm->query($t_query);

            if( !$t_result ) { 
              $t_prepare = array();
            } else {
              $t_prepare = $t_result->result();
            }


            // CATEGORIES
            $cat_list = $alert_details['aCategories'];
            $cat_list = implode(', ', $cat_list);
            $locale = '"' . osc_current_user_locale() . '"';

            $cat_query = "SELECT * FROM {$db_prefix}t_category_description WHERE fk_i_category_id IN (" . $cat_list . ") AND fk_c_locale_code = " . $locale;
            $cat_result = $comm->query($cat_query);

            if( !$cat_result ) { 
              $cat_prepare = array();
            } else {
              $cat_prepare = $cat_result->result();
            }
          ?>

          <div class="userItem" >
            <div class="hed"><i class="fa fa-bell-o"></i>&nbsp;<?php echo __('Alert', 'zara') . ' #' . $c; ?> <a class="tr1" onclick="javascript:return confirm('<?php echo osc_esc_js(__('This action can\'t be undone. Are you sure you want to continue?', 'zara')); ?>');" href="<?php echo osc_user_unsubscribe_alert_url(); ?>"><i class="fa fa-trash-o"></i>&nbsp;<?php _e('Delete', 'zara'); ?> <span class="non-resp"><?php _e('this alert', 'zara'); ?></span></a></div>

            <div class="alert-show-detail sc-click resp is767"><i class="fa fa-angle-down"></i> <?php _e('Show details', 'zara'); ?> <i class="fa fa-angle-down"></i></div>

            <div class="hed-param sc-block">
              <div class="elem w33 <?php if($alert_details['sPattern'] == '') { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('Pattern', 'zara'); ?></div>
                <div class="right"><?php if($alert_details['sPattern'] == '') { echo '--'; } else { echo $alert_details['sPattern']; } ?></div>
              </div>

              <div class="elem w33 <?php if($alert_details['price_min'] == 0) { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('Min. price', 'zara'); ?></div>
                <div class="right"><?php if($alert_details['price_min'] == 0) { echo '--'; } else { echo $alert_details['price_min'] . osc_get_preference('def_cur', 'zara_theme'); } ?></div>
              </div>

              <div class="elem w33 <?php if($alert_details['price_max'] == 0) { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('Max. price', 'zara'); ?></div>
                <div class="right"><?php if($alert_details['price_max'] == 0) { echo '--'; } else { echo $alert_details['price_max'] . osc_get_preference('def_cur', 'zara_theme'); } ?></div>
              </div>

              <div class="elem w33 <?php if($alert_details['countries'] == '' or empty($c_prepare)) { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('Country', 'zara'); ?></div>
                <div class="right">
                  <?php 
                    if($alert_details['countries'] == '' or empty($c_prepare)) { 
                      echo '--'; 
                    } else { 
                      $i = 0;
                      foreach($c_prepare as $country) {
                        echo $country['s_name'];

                        if($i < count($c_prepare) - 1) {
                          echo ', ';
                        }

                        $i++;
                      }
                    } 
                  ?>
                </div>
              </div>

              <div class="elem w33 <?php if($alert_details['regions'] == '' or empty($r_prepare)) { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('Region', 'zara'); ?></div>
                <div class="right">
                  <?php 
                    if($alert_details['regions'] == '' or empty($r_prepare)) { 
                      echo '--'; 
                    } else { 
                      $i = 0;
                      foreach($r_prepare as $region) {
                        echo $region['s_name'];

                        if($i < count($r_prepare) - 1) {
                          echo ', ';
                        }

                        $i++;
                      }
                    } 
                  ?>
                </div>
              </div>

              <div class="elem w33 <?php if($alert_details['cities'] == '' or empty($t_prepare)) { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('City', 'zara'); ?></div>
                <div class="right">
                  <?php 
                    if($alert_details['cities'] == '' or empty($t_prepare)) { 
                      echo '--'; 
                    } else { 
                      $i = 0;
                      foreach($t_prepare as $city) {
                        echo $city['s_name'];

                        if($i < count($t_prepare) - 1) {
                          echo ', ';
                        }

                        $i++;
                      }
                    } 
                  ?>
                </div>
              </div>

              <div class="elem w100 <?php if($alert_details['aCategories'] == '' or empty($cat_prepare)) { ?>no-entry<?php } ?>">
                <div class="left"><?php _e('Categories', 'zara'); ?></div>
                <div class="right">
                  <?php 
                    if($alert_details['aCategories'] == '' or empty($cat_prepare)) { 
                      echo '--'; 
                    } else { 
                      $i = 0;
                      foreach($cat_prepare as $category) {
                        echo $category['s_name'];

                        if($i < count($cat_prepare) - 1) {
                          echo ', ';
                        }

                        $i++;
                      }
                    } 
                  ?>
                </div>
              </div>

              <div class="elem warn"><?php _e('Note that not all conditions are listed, only base alert conditions are shown.', 'zara'); ?></div>
            </div>

            <div id="alerts_list" >
            <?php while(osc_has_items()) { ?>
              <div class="item-entry" >
                <?php if( osc_images_enabled_at_items() ) { ?>
                  <?php if(osc_count_item_resources()) { ?>
                    <a class="photo" href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_resource_thumbnail_url(); ?>" width="150" height="125" title="<?php echo osc_esc_html(osc_item_title()); ?>" alt="<?php echo osc_esc_html(osc_item_title()); ?>" /></a>
                  <?php } else { ?>
                    <a class="photo" href="<?php echo osc_item_url(); ?>"><img src="<?php echo osc_current_web_theme_url('images/no-image.png'); ?>" title="<?php echo osc_esc_html(__('No picture', 'zara')); ?>" alt="<?php echo osc_esc_html(__('No picture', 'zara')); ?>" width="150" height="125"/></a>
                  <?php } ?>
                <?php } ?>

                <div class="data-wrap">
                  <div class="row"><a href="<?php echo osc_item_url(); ?>"><?php echo osc_item_title(); ?></a></div>
                  <div class="row"><span class="left"><span class="non-resp"><?php _e('Publication date', 'zara'); ?></span><span class="resp is1200"><?php _e('Published', 'zara'); ?></span>:</span> <span class="right"><?php echo osc_format_date(osc_item_pub_date()); ?></span></div>
                  <div class="row"><span class="left"><?php if( osc_price_enabled_at_items() ) { _e('Price', 'zara'); ?>:</span> <span class="right"><?php echo osc_format_price(osc_item_price()); } ?></span></div>
                </div>
              </div>
            <?php } ?>
            <?php if(osc_count_items() == 0) { ?>
              <div class="item-entry" >
                <?php _e('No listings fits to search/alert criteria', 'zara'); ?>
              </div>
            <?php } ?>
            </div>
          </div>
          <?php $c++; ?>
        <?php } ?>
      <?php  } ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>