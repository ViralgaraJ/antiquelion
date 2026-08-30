<?php
// ************************************************** //
// ** AJAX CALLS HAS BEEN MOVED TO USER LOGIN PAGE ** //
// ************************************************** //

define('ABS_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once ABS_PATH . 'oc-load.php';
require_once ABS_PATH . 'oc-content/themes/starter/functions.php';


// GET LOCATIONS FOR LOCATION PICKER VIA AJAX
if(isset($_GET['ajaxLoc']) && $_GET['ajaxLoc'] == '1' && isset($_GET['term']) && $_GET['term'] <> '') {
  $term = osc_esc_html(Params::getParam('term'));
  $max = 12;

  if(osc_get_current_user_locations_native() == 1) {
    $sql = '
      (SELECT "country" as type, coalesce(s_name_native, s_name) as name, null as name_top, null as city_id, null as region_id, pk_c_code as country_code  FROM ' . DB_TABLE_PREFIX . 't_country WHERE s_name like "' . $term . '%" OR s_name_native like "' . $term . '%")
      UNION ALL
      (SELECT "region" as type, coalesce(r.s_name_native, r.s_name) as name, coalesce(c.s_name_native, c.s_name) as name_top, null as city_id, r.pk_i_id  as region_id, r.fk_c_country_code as country_code  FROM ' . DB_TABLE_PREFIX . 't_region r, ' . DB_TABLE_PREFIX . 't_country c WHERE r.fk_c_country_code = c.pk_c_code AND (r.s_name like "' . $term . '%" OR r.s_name_native like "' . $term . '%"))
      UNION ALL
      (SELECT "city" as type, coalesce(c.s_name_native, c.s_name) as name, concat(coalesce(r.s_name_native, r.s_name), concat(", ", upper(c.fk_c_country_code))) as name_top, c.pk_i_id as city_id, c.fk_i_region_id as region_id, c.fk_c_country_code as country_code  FROM ' . DB_TABLE_PREFIX . 't_city c, ' . DB_TABLE_PREFIX . 't_region r WHERE (c.s_name like "' . $term . '%" OR c.s_name_native like "' . $term . '%") AND c.fk_i_region_id = r.pk_i_id limit ' . $max . ')
    ';  
  } else {
    $sql = '
      (SELECT "country" as type, s_name as name, null as name_top, null as city_id, null as region_id, pk_c_code as country_code  FROM ' . DB_TABLE_PREFIX . 't_country WHERE s_name like "' . $term . '%")
      UNION ALL
      (SELECT "region" as type, r.s_name as name, c.s_name as name_top, null as city_id, r.pk_i_id  as region_id, r.fk_c_country_code as country_code  FROM ' . DB_TABLE_PREFIX . 't_region r, ' . DB_TABLE_PREFIX . 't_country c WHERE r.fk_c_country_code = c.pk_c_code AND r.s_name like "' . $term . '%")
      UNION ALL
      (SELECT "city" as type, c.s_name as name, concat(r.s_name, concat(", ", upper(c.fk_c_country_code))) as name_top, c.pk_i_id as city_id, c.fk_i_region_id as region_id, c.fk_c_country_code as country_code  FROM ' . DB_TABLE_PREFIX . 't_city c, ' . DB_TABLE_PREFIX . 't_region r WHERE c.s_name like "' . $term . '%" AND c.fk_i_region_id = r.pk_i_id limit ' . $max . ')
    ';  
  }

  $result = City::newInstance()->dao->query($sql);

  if( !$result ) { 
    $prepare = array(); 
  } else {
    $prepare = $result->result();
  }

  echo json_encode($prepare);
}


// CLEAR COOKIES VIA AJAX
if(isset($_GET['clearCookieAll']) && $_GET['clearCookieAll'] == 'done') {
  mb_set_cookie('starter-sCategory', '');
  //mb_set_cookie('starter-sPattern', '');
  //mb_set_cookie('starter-sPriceMin', '');
  //mb_set_cookie('starter-sPriceMax', '');
  mb_set_cookie('starter-sCountry', '');
  mb_set_cookie('starter-sRegion', '');
  mb_set_cookie('starter-sCity', '');
}



// GET ITEMS FOR AUTOCOMPLETE VIA AJAX
if(isset($_GET['ajaxItem']) && $_GET['ajaxItem'] == '1' && isset($_GET['pattern']) && $_GET['pattern'] <> '') {
  $pattern = osc_esc_html(Params::getParam('pattern'));
  $max = 12;

  $db_prefix = DB_TABLE_PREFIX;
  $sql = "
    SELECT i.pk_i_id, d.s_title, i.i_price, i.fk_c_currency_code, CONCAT(r.s_path, r.pk_i_id,'_thumbnail.',r.s_extension) as image_url
    FROM {$db_prefix}t_item i
    INNER JOIN {$db_prefix}t_item_description d
    ON d.fk_i_item_id = i.pk_i_id
    LEFT OUTER JOIN {$db_prefix}t_item_resource r
    ON r.fk_i_item_id = i.pk_i_id AND r.pk_i_id = (
      SELECT rs.pk_i_id
      FROM {$db_prefix}t_item_resource rs
      WHERE i.pk_i_id = rs.fk_i_item_id
      LIMIT 1
    )

    WHERE d.fk_c_locale_code = '" . osc_current_user_locale() . "' AND (s_title LIKE '%" . $pattern . "%' OR s_description LIKE '%" . $pattern . "%') 
    ORDER BY dt_pub_date DESC
    LIMIT " . $max . ";
  ";

  $result = Item::newInstance()->dao->query($sql);

  if( !$result ) { 
    $prepare = array(); 
  } else {
    $prepare = $result->result();
  }

  foreach( $prepare as $i => $p ) {
    $prepare[$i]['s_title'] = str_ireplace($pattern, '<b>' . $pattern . '</b>', $prepare[$i]['s_title']);
    $prepare[$i]['i_price'] = starter_ajax_item_format_price($prepare[$i]['i_price'], $prepare[$i]['fk_c_currency_code']);
    $prepare[$i]['item_url'] = osc_item_url_ns($prepare[$i]['pk_i_id']);
    if($prepare[$i]['image_url'] <> '') {
      $prepare[$i]['image_url'] = osc_base_url() . $prepare[$i]['image_url'];
    } else {
      $prepare[$i]['image_url'] = osc_current_web_theme_url('images/no-image.png');
    }
  }

  echo json_encode($prepare);
}



// INCREASE CLICK COUNT ON PHONE NUMBER
if(isset($_GET['ajaxPhoneClick']) && $_GET['ajaxPhoneClick'] == '1' && isset($_GET['itemId']) && $_GET['itemId'] > 0) {
  starter_increase_clicks(Params::getParam('itemId'), Params::getParam('itemUserId'));
  echo 1;
}


//echo 'test string';
?>