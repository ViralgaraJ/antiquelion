<?php
/*
 * Unit tests for the theme's pure functions (no database, no web server).
 *   php tests/run.php
 * Runs in GitHub Actions on every push (.github/workflows/ci.yml).
 * Command line only: this folder is also blocked from the web by tests/.htaccess.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('ABS_PATH', dirname(__DIR__) . '/');
define('DB_TABLE_PREFIX', 'oc_');
$THEME = ABS_PATH . 'oc-content/themes/starter/';

/* ---------- Minimal Osclass stand-ins ---------- */
$GLOBALS['prefs'] = array();
function osc_add_hook() {}
function osc_add_filter() {}
function osc_add_route() {}
function osc_run_hook() {}
function osc_apply_filter($name, $value) { return $value; }
function __($s) { return $s; }
function _e($s) { echo $s; }
function _n($a, $b, $n) { return $n == 1 ? $a : $b; }
function osc_get_preference($k, $section = '') { return isset($GLOBALS['prefs'][$k]) ? $GLOBALS['prefs'][$k] : ''; }
function osc_set_preference($k, $v) { $GLOBALS['prefs'][$k] = $v; }
function osc_esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function osc_current_web_theme_url($f = '') { return 'https://example.test/oc-content/themes/starter/' . $f; }
function osc_page_title() { return 'AntiqueLion'; }
function antique_money($amount, $code) { return $code . ' ' . number_format((float)$amount, abs($amount - round($amount)) < 0.005 ? 0 : 2); }
function antique_visitor_currency() { return $GLOBALS['visitor_currency']; }
function antique_convert($amount, $from, $to) {
  $rates = array('USD' => 1, 'LKR' => 300.5, 'GBP' => 0.79, 'EUR' => 0.92, 'AUD' => 1.52);
  return isset($rates[$from], $rates[$to]) ? $amount / $rates[$from] * $rates[$to] : null;
}

require $THEME . 'inc.payments.php';
require $THEME . 'inc.twofa.php';
require $THEME . 'inc.pwa.php';
require $THEME . 'inc.assets.php';
require $THEME . 'inc.stats.php';
require $THEME . 'inc.recent.php';

/* ---------- Tiny test runner ---------- */
$fails = 0; $count = 0;
function check($name, $actual, $expected) {
  global $fails, $count;
  $count++;
  if ($actual === $expected) { echo "  ok   $name\n"; return; }
  $fails++;
  echo "  FAIL $name\n       expected: " . var_export($expected, true) . "\n       actual:   " . var_export($actual, true) . "\n";
}

echo "PayHere\n";
// Fake values only. Never put real PayHere credentials in tests: this file is public on GitHub.
$fake_merchant_id = '1000000';
$fake_merchant_secret = 'TEST_ONLY_NOT_A_REAL_SECRET';
$GLOBALS['prefs'] = array('pay_merchant_id' => $fake_merchant_id, 'pay_merchant_secret' => $fake_merchant_secret);
check('checkout hash = MD5(id + order + amount + currency + MD5(secret)), uppercase',
  antique_pay_hash('ItemNo12345', 1000, 'LKR'),
  strtoupper(md5($fake_merchant_id . 'ItemNo12345' . '1000.00' . 'LKR' . strtoupper(md5($fake_merchant_secret)))));
// Fixed expected value, so a change to the formula itself is also caught (not only computed the same way).
check('checkout hash, known value for the fake credentials',
  antique_pay_hash('ItemNo12345', 1000, 'LKR'),
  'D846DE5C655CF74D1049E79AAE050DEF');
check('amount always has two decimals', antique_pay_amount_str(5), '5.00');
check('amount rounding', antique_pay_amount_str(12.345), '12.35');
$GLOBALS['visitor_currency'] = 'LKR';
check('LKR visitor pays in LKR, rounded to 10', antique_pay_quote(3, antique_pay_currency()), array(900.0, 'LKR'));
$GLOBALS['visitor_currency'] = 'GBP';
check('GBP visitor pays whole pounds', antique_pay_quote(10, antique_pay_currency()), array(8.0, 'GBP'));
$GLOBALS['visitor_currency'] = 'INR';
check('currency PayHere cannot charge falls back to USD', antique_pay_quote(7.5, antique_pay_currency()), array(7.5, 'USD'));
check('converted price never below 1', antique_pay_quote(0.5, 'GBP'), array(1.0, 'GBP'));
$GLOBALS['prefs']['pay_enabled'] = '1';
$GLOBALS['prefs']['pay_mode'] = 'sandbox';
check('sandbox checkout URL', antique_pay_checkout_url(), 'https://sandbox.payhere.lk/pay/checkout');
$GLOBALS['prefs']['pay_mode'] = 'live';
check('live checkout URL', antique_pay_checkout_url(), 'https://www.payhere.lk/pay/checkout');
$GLOBALS['prefs']['pay_prices'] = json_encode(array('top' => array(7 => 12.5), 'bump' => array(3 => -1)));
$p = antique_promo_products();
check('admin price overrides the default', $p['top']['prices'][7], 12.5);
check('invalid admin price keeps the default', $p['bump']['prices'][3], 2);

echo "Two-step sign-in (RFC 6238 / RFC 4648)\n";
$secret = antique_base32_encode('12345678901234567890');
check('base32 of the RFC test key', $secret, 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ');
check('base32 round trip', antique_base32_decode(antique_base32_encode("\x00\xffhello")), "\x00\xffhello");
check('TOTP T=59s', antique_totp($secret, 1), '287082');
check('TOTP T=1111111109s', antique_totp($secret, (int)floor(1111111109 / 30)), '081804');
check('TOTP T=2000000000s', antique_totp($secret, (int)floor(2000000000 / 30)), '279037');
$now = (int)floor(time() / 30);
check('current code accepted', antique_totp_check($secret, antique_totp($secret, $now)), $now);
check('code with spaces accepted', antique_totp_check($secret, ' ' . substr(antique_totp($secret, $now), 0, 3) . ' ' . substr(antique_totp($secret, $now), 3)), $now);
check('used code refused (replay)', antique_totp_check($secret, antique_totp($secret, $now), $now), 0);
check('old code refused', antique_totp_check($secret, antique_totp($secret, $now - 5)), 0);
check('wrong length refused', antique_totp_check($secret, '12345'), 0);
$codes = antique_2fa_new_recovery();
check('ten recovery codes', count($codes), 10);
check('recovery code format', (bool)preg_match('/^[a-f0-9]{5}-[a-f0-9]{5}$/', $codes[0]), true);
check('recovery codes unique', count(array_unique($codes)), 10);

echo "Web Push (VAPID)\n";
$key = openssl_pkey_new(array('curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC));
openssl_pkey_export($key, $pem);
$GLOBALS['prefs']['push_vapid_private'] = $pem;
$d = openssl_pkey_get_details($key);
$GLOBALS['prefs']['push_vapid_public'] = antique_b64url("\x04" . $d['ec']['x'] . $d['ec']['y']);
$ok = true;
for ($i = 0; $i < 25; $i++) {
  openssl_sign('message ' . $i, $der, $pem, OPENSSL_ALGO_SHA256);
  $raw = antique_der_to_raw($der);
  if (strlen($raw) !== 64) { $ok = false; break; }
  // back to DER and verify, so a short r or s (leading zero) is handled too
  $int = function ($b) { $b = ltrim($b, "\0"); if ($b === '' || ord($b[0]) & 0x80) { $b = "\0" . $b; } return "\x02" . chr(strlen($b)) . $b; };
  $seq = $int(substr($raw, 0, 32)) . $int(substr($raw, 32));
  if (openssl_verify('message ' . $i, "\x30" . chr(strlen($seq)) . $seq, $d['key'], OPENSSL_ALGO_SHA256) !== 1) { $ok = false; break; }
}
check('ECDSA DER -> JOSE r||s keeps signatures valid (25 runs)', $ok, true);
check('base64url has no padding or +/', (bool)preg_match('/^[A-Za-z0-9_-]+$/', antique_b64url(random_bytes(65))), true);
$GLOBALS['prefs']['push_vapid_private'] = $pem;
function osc_contact_email() { return 'admin@example.test'; }
function osc_base_url() { return 'https://example.test/'; }
$jwt = antique_vapid_jwt('https://fcm.googleapis.com/fcm/send/abc:def');
$parts = explode('.', $jwt);
$claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
check('JWT has three parts', count($parts), 3);
check('JWT audience is the push service origin', $claims['aud'], 'https://fcm.googleapis.com');
check('JWT expires within 24 hours', $claims['exp'] > time() && $claims['exp'] <= time() + 86400, true);

echo "CSS bundle\n";
check('comments and whitespace removed', antique_minify_css("a {\n  color: red;\n}\n/* note */\nb { margin: 0 }", 'https://x/'), 'a{color:red}b{margin:0}');
check('descendant pseudo-class keeps its space', antique_minify_css('a :hover { x: y }', 'https://x/'), 'a :hover{x:y}');
check('relative url made absolute', antique_minify_css('a{background:url(../images/a.png)}', 'https://x/css/'), 'a{background:url(https://x/css/../images/a.png)}');
check('quoted relative url made absolute', antique_minify_css("a{background:url('img/b.gif')}", 'https://x/css/'), "a{background:url('https://x/css/img/b.gif')}");
check('data and absolute urls untouched', antique_minify_css('a{b:url(data:image/png;base64,AA==);c:url(https://cdn/x.png);d:url(/root.png)}', 'https://x/'), 'a{b:url(data:image/png;base64,AA==);c:url(https://cdn/x.png);d:url(/root.png)}');
check('calc() spacing kept', antique_minify_css('a { width: calc(100% - 2px); }', 'https://x/'), 'a{width:calc(100% - 2px)}');
check('attribute selector string kept exactly', antique_minify_css('li:has(> label[style*="display: none"]) { display: none; }', 'https://x/'), 'li:has(> label[style*="display: none"]){display:none}');
check('content string kept exactly', antique_minify_css("a::after { content: ' : ,  { } ; '; }", 'https://x/'), "a::after{content:' : ,  { } ; '}");
check('@charset removed', antique_minify_css('@charset "UTF-8"; a{b:c}', 'https://x/'), 'a{b:c}');
check('important comments kept', strpos(antique_minify_css('/*! licence */a{b:c}', 'https://x/'), 'licence') !== false, true);

echo "Analytics\n";
check('change vs previous period', antique_stats_change(150, 100), '+50%');
check('drop vs previous period', antique_stats_change(75, 100), '-25%');
check('nothing to compare', antique_stats_change(10, 0), '');

echo "Recently viewed\n";
check('empty cookie', antique_recent_parse(''), array());
check('not a string', antique_recent_parse(array('1')), array());
check('ints kept in order', antique_recent_parse('11,4,10'), array(11, 4, 10));
check('junk, zero, negative and SQL dropped', antique_recent_parse('5,abc,0,-3, 7 ,1 OR 1=1,8x,'), array(5, 7));
check('duplicates dropped', antique_recent_parse('3,3,2,3'), array(3, 2));
check('capped at ' . ANTIQUE_RECENT_MAX, count(antique_recent_parse(implode(',', range(1, 30)))), ANTIQUE_RECENT_MAX);
check('push puts new item first', antique_recent_push(array(4, 10), 11), array(11, 4, 10));
check('push moves existing item to front', antique_recent_push(array(4, 10, 11), 10), array(10, 4, 11));
check('push trims to max', count(antique_recent_push(range(1, ANTIQUE_RECENT_MAX), 99)), ANTIQUE_RECENT_MAX);
check('push ignores bad id', antique_recent_push(array(4), 0), array(4));
// No IDs left -> array() before any DB call (Item/DB_TABLE_PREFIX don't exist here, so a query would fatal)
$_COOKIE[ANTIQUE_RECENT_COOKIE] = '5';
check('only the current listing -> no query, empty', antique_recent_items(5), array());
$_COOKIE[ANTIQUE_RECENT_COOKIE] = 'abc,0,-1';
check('only junk -> no query, empty', antique_recent_items(5), array());
unset($_COOKIE[ANTIQUE_RECENT_COOKIE]);
check('no cookie -> no query, empty', antique_recent_items(5), array());

echo "\n" . ($count - $fails) . "/$count passed\n";
exit($fails > 0 ? 1 : 0);
