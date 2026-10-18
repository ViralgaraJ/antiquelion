<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }
/*
 * Visitor country from IP, using the bundled DB-IP Country Lite database
 * (lib/geo, CC BY 4.0) and MaxMind's pure-PHP reader (lib/maxmind-db,
 * Apache 2.0). Lookups are local (<1 ms) and the result is cached in a cookie.
 *
 * Used to pre-select the display currency and the phone country code, and
 * to show "Ship to" in the header. Visitors can always override the currency.
 */

define('ANTIQUE_GEO_COOKIE', 'al_geo');

/**
 * array('code' => 'GB', 'name' => 'United Kingdom'), or code '' when unknown.
 */
function antique_visitor_country() {
  static $country = null;
  if ($country !== null) {
    return $country;
  }

  // Local development: ?geo_test=GB simulates a visitor from that country
  $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
  $test = strtoupper((string)Params::getParam('geo_test'));
  if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host) && preg_match('/^[A-Z]{2}$/', $test)) {
    $country = array('code' => $test, 'name' => $test);
    return $country;
  }

  if (isset($_COOKIE[ANTIQUE_GEO_COOKIE])) {
    $parts = explode('|', (string)$_COOKIE[ANTIQUE_GEO_COOKIE], 2);
    if (preg_match('/^([A-Z]{2})?$/', $parts[0])) {
      $country = array('code' => $parts[0], 'name' => isset($parts[1]) ? substr(strip_tags($parts[1]), 0, 60) : '');
      return $country;
    }
  }

  $country = antique_geo_lookup(antique_visitor_ip());
  if (!headers_sent()) {
    setcookie(ANTIQUE_GEO_COOKIE, $country['code'] . '|' . $country['name'], array(
      'expires' => time() + 30 * 86400,
      'path' => '/',
      'secure' => osc_is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax'
    ));
  }
  return $country;
}
osc_add_hook('init', 'antique_visitor_country');

function antique_visitor_ip() {
  // REMOTE_ADDR is the only header a visitor cannot forge without a proxy in front
  return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

function antique_geo_lookup($ip) {
  $unknown = array('code' => '', 'name' => '');
  $db = __DIR__ . '/lib/geo/dbip-country-lite.mmdb';
  if ($ip === '' || !is_readable($db) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    return $unknown;
  }

  try {
    if (!class_exists('MaxMind\\Db\\Reader')) {
      $lib = __DIR__ . '/lib/maxmind-db/';
      require_once $lib . 'Reader/Decoder.php';
      require_once $lib . 'Reader/InvalidDatabaseException.php';
      require_once $lib . 'Reader/Metadata.php';
      require_once $lib . 'Reader/Util.php';
      require_once $lib . 'Reader.php';
    }
    $reader = new MaxMind\Db\Reader($db);
    $record = $reader->get($ip);
    $reader->close();
  } catch (Throwable $e) {
    return $unknown;
  }

  if (empty($record['country']['iso_code'])) {
    return $unknown;
  }
  return array(
    'code' => $record['country']['iso_code'],
    'name' => isset($record['country']['names']['en']) ? $record['country']['names']['en'] : $record['country']['iso_code']
  );
}

/**
 * Display currency that fits a country, falling back to the site default.
 */
function antique_currency_for_country($code) {
  $map = array(
    'GB' => 'GBP', 'IM' => 'GBP', 'JE' => 'GBP', 'GG' => 'GBP',
    'US' => 'USD', 'PR' => 'USD',
    'AU' => 'AUD', 'CA' => 'CAD', 'NZ' => 'NZD', 'SG' => 'SGD',
    'IN' => 'INR', 'AE' => 'AED', 'LK' => 'LKR'
  );
  $euro = array('AT','BE','HR','CY','EE','FI','FR','DE','GR','IE','IT','LV','LT','LU','MT','NL','PT','SK','SI','ES','AD','MC','SM','VA','ME','XK');

  if (isset($map[$code])) {
    return $map[$code];
  }
  if (in_array($code, $euro)) {
    return 'EUR';
  }
  return ANTIQUE_DEFAULT_CURRENCY;
}
