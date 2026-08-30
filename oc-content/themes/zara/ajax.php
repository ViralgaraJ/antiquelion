<?php
define('ABS_PATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once ABS_PATH . 'oc-load.php';
require_once ABS_PATH . 'oc-content/themes/zara/functions.php';

// Ajax clear cookies
if($_GET['clearCookieSearch'] == 'done') {
  mb_set_cookie('zara-sCategory', '');
  mb_set_cookie('zara-sPattern', '');
  mb_set_cookie('zara-sPriceMin', '');
  mb_set_cookie('zara-sPriceMax', '');
}

if($_GET['clearCookieLocation'] == 'done') {
  mb_set_cookie('zara-sCountry', '');
  mb_set_cookie('zara-sRegion', '');
  mb_set_cookie('zara-sCity', '');
  mb_set_cookie('zara-sLocator', '');
}

if($_GET['clearCookieAll'] == 'done') {
  mb_set_cookie('zara-sCategory', '');
  mb_set_cookie('zara-sPattern', '');
  mb_set_cookie('zara-sPriceMin', '');
  mb_set_cookie('zara-sPriceMax', '');
  mb_set_cookie('zara-sCountry', '');
  mb_set_cookie('zara-sRegion', '');
  mb_set_cookie('zara-sCity', '');
  mb_set_cookie('zara-sLocator', '');
}

//echo 'test string';
?>