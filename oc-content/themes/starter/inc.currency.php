<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }
/*
 * AntiqueLion currency display.
 *
 * Prices are always shown in the currency the seller listed them in, as
 * "CODE 12,500" (codes avoid the $/AUD/CAD ambiguity for worldwide buyers).
 * When the visitor picked a different display currency in the header, an
 * approximate converted price is appended: "LKR 650,000 ≈ USD 2,150".
 *
 * Rates come from open.er-api.com (free, no key, updated daily) and are cached
 * in t_preference; the replaced multicurrency plugin relied on a Yahoo API
 * that no longer exists.
 */

// Currencies a visitor can choose to view prices in (header selector)
define('ANTIQUE_DISPLAY_CURRENCIES', 'USD,GBP,EUR,AUD,CAD,NZD,SGD,INR,AED,LKR');
define('ANTIQUE_DEFAULT_CURRENCY', 'USD');
define('ANTIQUE_FX_TTL', 12 * 3600);       // refresh cached rates after 12h
define('ANTIQUE_FX_RETRY', 3600);          // wait 1h after a failed fetch
define('ANTIQUE_CURRENCY_COOKIE', 'al_currency');

function antique_display_currencies() {
  return explode(',', ANTIQUE_DISPLAY_CURRENCIES);
}

/**
 * Visitor's display currency: ?display_currency=XXX (remembered in a cookie) > cookie >
 * currency of the visitor's country (inc.geo.php) > default.
 * (Not "currency": that name is the listing currency field on the post/edit form.)
 */
function antique_visitor_currency() {
  static $code = null;
  if ($code !== null) {
    return $code;
  }

  $allowed = antique_display_currencies();
  $param = strtoupper(trim((string)Params::getParam('display_currency')));
  if ($param !== '' && in_array($param, $allowed)) {
    $code = $param;
    if (!headers_sent()) {
      setcookie(ANTIQUE_CURRENCY_COOKIE, $code, array(
        'expires' => time() + 365 * 86400,
        'path' => '/',
        'secure' => osc_is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax'
      ));
    }
    return $code;
  }

  $cookie = isset($_COOKIE[ANTIQUE_CURRENCY_COOKIE]) ? strtoupper($_COOKIE[ANTIQUE_CURRENCY_COOKIE]) : '';
  if (in_array($cookie, $allowed)) {
    $code = $cookie;
  } else {
    $country = antique_visitor_country();
    $code = antique_currency_for_country($country['code']);
    $code = in_array($code, $allowed) ? $code : ANTIQUE_DEFAULT_CURRENCY;
  }
  return $code;
}
// Resolve early so the cookie is sent before any output
osc_add_hook('init', 'antique_visitor_currency');

/**
 * USD-based rates, e.g. array('USD' => 1, 'LKR' => 302.4, ...). Empty array if unavailable.
 */
function antique_fx_rates() {
  static $rates = null;
  if ($rates !== null) {
    return $rates;
  }

  $cached = json_decode((string)osc_get_preference('fx_rates', 'antique'), true);
  $updated = (int)osc_get_preference('fx_updated', 'antique');
  $attempt = (int)osc_get_preference('fx_attempt', 'antique');
  $rates = is_array($cached) ? $cached : array();

  $stale = (time() - $updated) > ANTIQUE_FX_TTL;
  if (($stale || empty($rates)) && (time() - $attempt) > ANTIQUE_FX_RETRY) {
    osc_set_preference('fx_attempt', time(), 'antique', 'INTEGER');
    $fresh = antique_fx_fetch();
    if (!empty($fresh)) {
      $rates = $fresh;
      osc_set_preference('fx_rates', json_encode($rates), 'antique', 'STRING');
      osc_set_preference('fx_updated', time(), 'antique', 'INTEGER');
    }
  }

  return $rates;
}

function antique_fx_fetch() {
  if (!function_exists('curl_init')) {
    return array();
  }
  $ch = curl_init('https://open.er-api.com/v6/latest/USD');
  curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 5
  ));
  $body = curl_exec($ch);
  curl_close($ch);

  $data = json_decode((string)$body, true);
  if (!isset($data['result'], $data['rates']) || $data['result'] !== 'success') {
    return array();
  }

  // Keep only what the site can display or list in
  $keep = antique_display_currencies();
  foreach (osc_get_currencies() as $c) {
    $keep[] = $c['pk_c_code'];
  }
  $rates = array_intersect_key($data['rates'], array_flip(array_unique($keep)));
  return array_map(function($r) { return round((float)$r, 6); }, $rates);
}
osc_add_hook('cron_daily', 'antique_fx_rates');

/**
 * Convert an amount between currencies. Returns null when a rate is missing.
 */
function antique_convert($amount, $from, $to) {
  if ($from === $to) {
    return (float)$amount;
  }
  $rates = antique_fx_rates();
  if (empty($rates[$from]) || empty($rates[$to])) {
    return null;
  }
  return (float)$amount / $rates[$from] * $rates[$to];
}

/**
 * "USD 12,500" / "LKR 650,000" / "USD 12.50". Decimals only when needed.
 */
function antique_money($amount, $code) {
  $amount = (float)$amount;
  $decimals = (abs($amount - round($amount)) < 0.005) ? 0 : 2;
  return trim($code . ' ' . number_format($amount, $decimals, '.', ','));
}

/**
 * Price HTML for a listing price stored the Osclass way (amount * 1,000,000).
 */
function antique_price_html($price, $code) {
  if ($price === null || $price === '') {
    return __('Check with seller', 'starter');
  }
  if ($price == 0) {
    return __('Free', 'starter');
  }

  $amount = $price / 1000000;
  $code = $code ? $code : ANTIQUE_DEFAULT_CURRENCY;
  $html = osc_esc_html(antique_money($amount, $code));

  $visitor = antique_visitor_currency();
  if ($visitor !== $code) {
    $converted = antique_convert($amount, $code, $visitor);
    if ($converted !== null) {
      // round the estimate so it does not look like a quoted price
      if ($converted >= 100) {
        $converted = round($converted, -1);
      } elseif ($converted >= 10) {
        $converted = round($converted);
      } else {
        $converted = round($converted, 2);
      }
      $html .= ' <span class="price-converted" title="' . osc_esc_html(__('Approximate conversion, daily exchange rate', 'starter')) . '">&asymp; ' . osc_esc_html(antique_money($converted, $visitor)) . '</span>';
    }
  }

  return $html;
}
