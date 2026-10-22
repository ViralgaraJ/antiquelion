<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Search engines and sharing:
 * - page titles and descriptions written for each kind of page;
 * - one canonical URL per page (no tracking or display parameters), and noindex on
 *   filtered searches, sign-in and account pages;
 * - structured data: Organization + WebSite search box, breadcrumbs, seller pages;
 * - Open Graph / Twitter cards;
 * - old index.php?page=... addresses redirect (301) to friendly URLs once those are on;
 * - sitemap.xml and robots.txt written by the site itself (git-ignored, refreshed
 *   every 6 hours and whenever oc-admin asks), so they always use the live address.
 * Settings (Google Analytics, verification codes, home description) live under
 * Theme Setting > Marketplace > SEO in oc-admin.
 */

define('ANTIQUE_SEO_SITEMAP_HOURS', 6);
define('ANTIQUE_SEO_SITEMAP_MAX', 45000);   // URLs per file (the limit is 50,000)

function antique_seo_pref($name) {
  return trim((string)osc_get_preference($name, 'starter_theme'));
}

function antique_seo_default_home_desc() {
  return __('Buy and sell antiques, vintage collectibles and rare finds from dealers and collectors worldwide. Shop fixed prices, make offers or bid in live auctions.', 'starter');
}

function antique_seo_clip($text, $max = 158) {
  $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string)$text), ENT_QUOTES, 'UTF-8')));
  if (mb_strlen($text) <= $max) { return $text; }
  $cut = mb_substr($text, 0, $max - 1);
  $sp = mb_strrpos($cut, ' ');
  return rtrim($sp > $max * 0.6 ? mb_substr($cut, 0, $sp) : $cut, " ,.;:-") . '…';
}

/**
 * What kind of page this is, with the details the SEO functions need. Cached.
 */
function antique_seo_page() {
  static $p = null;
  if ($p !== null) { return $p; }
  $loc = osc_get_osclass_location();
  $sec = osc_get_osclass_section();
  $p = array('type' => 'other', 'page' => max(1, (int)Params::getParam('iPage')));
  if ($loc == 'error' || http_response_code() == 404) { $p['type'] = 'private'; }
  elseif (osc_is_home_page()) { $p['type'] = 'home'; }
  elseif (osc_is_ad_page()) { $p['type'] = 'item'; }
  elseif (osc_is_search_page()) {
    $p['type'] = 'search';
    $cats = osc_search_category_id();
    $p['cat'] = !empty($cats[0]) ? Category::newInstance()->findByPrimaryKey($cats[0]) : null;
    $p['auction'] = Params::getParam('sAuction') == '1';
    $p['pattern'] = trim((string)osc_search_pattern());
    $p['seller'] = (int)Params::getParam('seller_post') ?: (int)Params::getParam('sUser');
    // anything that narrows the results beyond category / auctions / a page number
    $filters = array('sPattern', 'sCity', 'sRegion', 'sCountry', 'sItemCountry', 'sCityArea', 'sPriceMin', 'sPriceMax', 'pmin', 'pmax',
      'sEra', 'sMaterial', 'sOrigin', 'sAvailable', 'sCondition', 'sTransaction', 'bPic', 'bPremium', 'sCompany', 'sOrder', 'iOrderType', 'sShowAs', 'seller_post', 'sUser', 'sPhone');
    $p['filtered'] = false;
    foreach ($filters as $f) { if (Params::getParam($f) !== '' && Params::getParam($f) !== null) { $p['filtered'] = true; break; } }
    if (osc_search_total_items() == 0) { $p['filtered'] = true; }   // empty result pages are not worth indexing
  }
  elseif ($loc == 'user' && $sec == 'pub_profile') { $p['type'] = 'shop'; $p['user'] = (int)osc_user_id(); }
  elseif ($loc == 'custom' && function_exists('antique_info_pages') && isset(antique_info_pages()[Params::getParam('route')])) { $p['type'] = 'info'; $p['route'] = Params::getParam('route'); }
  elseif ($loc == 'page') { $p['type'] = 'staticpage'; }
  elseif (in_array($loc, array('login', 'register', 'user', 'custom', 'ajax')) || in_array($sec, array('item_add', 'item_edit', 'send_friend', 'contact'))) { $p['type'] = 'private'; }
  elseif ($loc == 'contact') { $p['type'] = 'contact'; }
  return $p;
}

/* ------------------------------ titles and descriptions ------------------------------ */

function antique_seo_title($text) {
  $site = osc_page_title();
  $p = antique_seo_page();
  $pageSuffix = $p['page'] > 1 ? ' – ' . sprintf(__('page %d', 'starter'), $p['page']) : '';
  switch ($p['type']) {
    case 'home':
      return $site . ' – ' . __('Antiques, collectibles and live auctions', 'starter');
    case 'item':
      $t = osc_item_title();
      $full = $t . ' | ' . osc_item_category() . ' | ' . $site;
      return mb_strlen($full) <= 65 ? $full : $t . ' | ' . $site;
    case 'search':
      if ($p['auction']) { $base = __('Live antique auctions', 'starter'); }
      elseif ($p['pattern'] !== '') { $base = sprintf(__('%s – antiques for sale', 'starter'), '“' . $p['pattern'] . '”'); }
      elseif ($p['cat']) { $base = sprintf(__('%s for sale', 'starter'), $p['cat']['s_name']); }
      elseif ($p['seller'] && ($u = User::newInstance()->findByPrimaryKey($p['seller']))) { $base = sprintf(__('Listings from %s', 'starter'), antique_seller_display_name($p['seller'], $u['s_name'])); }
      else { $base = __('Antiques and collectibles for sale', 'starter'); }
      return $base . $pageSuffix . ' | ' . $site;
    case 'shop':
      $name = antique_seller_display_name($p['user'], osc_user_name());
      return sprintf(osc_user_field('b_company') == 1 ? __('%s – antiques dealer', 'starter') : __('%s – antiques and collectibles', 'starter'), $name) . $pageSuffix . ' | ' . $site;
  }
  // everything else: "Page - Site" from Osclass, without the site name twice
  $text = preg_replace('/\s+-\s+' . preg_quote($site, '/') . '(\s+-\s+' . preg_quote($site, '/') . ')+$/u', ' - ' . $site, (string)$text);
  if (mb_substr_count($text, $site) > 1) { $text = preg_replace('/\s+-\s+' . preg_quote($site, '/') . '$/u', '', $text); }
  return str_replace(' - ' . $site, ' | ' . $site, $text);
}
osc_add_filter('meta_title_filter', 'antique_seo_title', 9);

function antique_seo_description($text) {
  $p = antique_seo_page();
  switch ($p['type']) {
    case 'home':
      $d = antique_seo_pref('seo_home_desc');
      return $d !== '' ? $d : antique_seo_default_home_desc();
    case 'item':
      $desc = antique_seo_clip(osc_item_description(), 125);
      $price = osc_item_price() > 0 ? ' ' . sprintf(__('Price: %s.', 'starter'), antique_money(osc_item_price() / 1000000, osc_item_currency())) : '';
      return antique_seo_clip($desc . $price, 158);
    case 'search':
      $n = (int)osc_search_total_items();
      if ($p['auction']) { return sprintf(__('Bid on %d antiques and collectibles in live auctions. Set a maximum bid and we bid for you, with anti-sniping protection.', 'starter'), $n); }
      if ($p['cat']) {
        $own = trim((string)$p['cat']['s_description']);
        return antique_seo_clip($own !== '' ? $own : sprintf(__('Browse %1$d %2$s from dealers and collectors worldwide. Buy at a fixed price, make an offer or bid in live auctions.', 'starter'), $n, mb_strtolower($p['cat']['s_name'])));
      }
      if ($p['pattern'] !== '') { return sprintf(__('%1$d results for “%2$s” on AntiqueLion: antiques and collectibles from dealers and collectors worldwide.', 'starter'), $n, $p['pattern']); }
      return sprintf(__('Browse %d antiques and collectibles for sale from dealers and collectors worldwide.', 'starter'), $n);
    case 'shop':
      $shop = function_exists('antique_shop') ? antique_shop($p['user']) : array('tagline' => '');
      $about = trim((string)osc_user_info());
      $name = antique_seller_display_name($p['user'], osc_user_name());
      $lead = $shop['tagline'] !== '' ? $shop['tagline'] : ($about !== '' ? $about : sprintf(__('Antiques and collectibles from %s.', 'starter'), $name));
      return antique_seo_clip($lead . ' ' . sprintf(__('%d items for sale on AntiqueLion.', 'starter'), (int)osc_user_items_validated()));
    case 'info':
      $map = array(
        'al-about' => __('AntiqueLion is a worldwide marketplace for antiques and collectibles: fixed prices, offers and live auctions from dealers and private sellers.', 'starter'),
        'al-safety' => __('How buying and selling works on AntiqueLion, and how to pay, ship and meet safely.', 'starter'),
        'al-terms' => __('The rules for buying, selling and bidding on AntiqueLion.', 'starter'),
        'al-privacy' => __('What information AntiqueLion collects, how it is used and the choices you have.', 'starter')
      );
      if (isset($map[$p['route']])) { return $map[$p['route']]; }
  }
  return $text;
}
osc_add_filter('meta_description_filter', 'antique_seo_description', 9);

/* ------------------------------ canonical and robots ------------------------------ */

function antique_seo_canonical_url() {
  $p = antique_seo_page();
  $page = $p['page'] > 1 ? $p['page'] : 0;
  switch ($p['type']) {
    case 'home': return osc_base_url();
    case 'item': return osc_item_url();
    case 'search':
      if ($p['filtered'] && !$p['auction'] && !$p['cat']) { return ''; }
      $args = array('page' => 'search');
      if ($p['cat']) { $args['sCategory'] = $p['cat']['pk_i_id']; }
      if ($p['auction']) { $args['sAuction'] = 1; }
      if ($page) { $args['iPage'] = $page; }
      return osc_search_url($args);
    case 'shop':
      return osc_user_public_profile_url($p['user']);
    case 'info':
      return osc_route_url($p['route']);
  }
  return '';
}

function antique_seo_canonical($url) {
  $mine = antique_seo_canonical_url();
  return $mine !== '' ? $mine : $url;
}
osc_add_filter('canonical_url_osc', 'antique_seo_canonical', 9);

function antique_seo_robots() {
  $p = antique_seo_page();
  if ($p['type'] == 'private') { return 'noindex, nofollow'; }
  if ($p['type'] == 'search' && $p['filtered']) { return 'noindex, follow'; }
  if ($p['type'] == 'item' && (osc_item_is_expired() || !osc_item_is_active() || osc_item_is_spam())) { return 'noindex, follow'; }
  if (stripos(osc_base_url(), 'staging') !== false) { return 'noindex, nofollow'; }   // the staging copy must stay out of Google
  return 'index, follow, max-image-preview:large';
}

/* ------------------------------ structured data and social cards ------------------------------ */

function antique_seo_jsonld($data) {
  return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>' . PHP_EOL;
}

function antique_seo_logo_url() {
  return osc_current_web_theme_url('images/favicons/android-chrome-512x512.png');
}

/**
 * Tags for <head> (called from head.php): robots, verification codes, social cards,
 * structured data, analytics.
 */
function antique_seo_head() {
  $p = antique_seo_page();
  $site = osc_page_title();
  $out = '<meta name="robots" content="' . antique_seo_robots() . '" />' . PHP_EOL;
  foreach (array('seo_google_verify' => 'google-site-verification', 'seo_bing_verify' => 'msvalidate.01') as $pref => $name) {
    $v = preg_replace('/[^A-Za-z0-9_\-]/', '', antique_seo_pref($pref));
    if ($v !== '') { $out .= '<meta name="' . $name . '" content="' . $v . '" />' . PHP_EOL; }
  }

  // social cards (the listing page writes its own product tags)
  $out .= '<meta name="twitter:card" content="summary_large_image" />' . PHP_EOL;
  if ($p['type'] != 'item') {
    $title = meta_title();
    $out .= '<meta property="og:site_name" content="' . osc_esc_html($site) . '" />' . PHP_EOL
          . '<meta property="og:type" content="website" />' . PHP_EOL
          . '<meta property="og:title" content="' . osc_esc_html($title) . '" />' . PHP_EOL
          . '<meta property="og:description" content="' . osc_esc_html(meta_description()) . '" />' . PHP_EOL;
    $canon = antique_seo_canonical_url();
    if ($canon !== '') { $out .= '<meta property="og:url" content="' . osc_esc_html($canon) . '" />' . PHP_EOL; }
    if ($p['type'] != 'shop' || !function_exists('antique_shop') || antique_shop($p['user'])['banner'] === '') {
      $out .= '<meta property="og:image" content="' . osc_esc_html(antique_seo_logo_url()) . '" />' . PHP_EOL;
    }
  }

  if ($p['type'] == 'home') {
    $out .= antique_seo_jsonld(array(
      '@context' => 'https://schema.org',
      '@graph' => array(
        array('@type' => 'Organization', '@id' => osc_base_url() . '#org', 'name' => $site, 'url' => osc_base_url(), 'logo' => antique_seo_logo_url()),
        array('@type' => 'WebSite', '@id' => osc_base_url() . '#website', 'name' => $site, 'url' => osc_base_url(), 'publisher' => array('@id' => osc_base_url() . '#org'),
          'potentialAction' => array('@type' => 'SearchAction',
            'target' => array('@type' => 'EntryPoint', 'urlTemplate' => osc_base_url(true) . '?page=search&sPattern={search_term_string}'),
            'query-input' => 'required name=search_term_string'))
      )
    ));
  }
  if ($p['type'] == 'item') {
    $crumbs = array(array('name' => __('Home', 'starter'), 'item' => osc_base_url()));
    $cat = Category::newInstance()->findByPrimaryKey(osc_item_category_id());
    if ($cat && !empty($cat['fk_i_parent_id'])) {
      $parent = Category::newInstance()->findByPrimaryKey($cat['fk_i_parent_id']);
      if ($parent) { $crumbs[] = array('name' => $parent['s_name'], 'item' => osc_search_url(array('page' => 'search', 'sCategory' => $parent['pk_i_id']))); }
    }
    if ($cat) { $crumbs[] = array('name' => $cat['s_name'], 'item' => osc_search_url(array('page' => 'search', 'sCategory' => $cat['pk_i_id']))); }
    $crumbs[] = array('name' => osc_item_title(), 'item' => osc_item_url());
    $out .= antique_seo_breadcrumbs($crumbs);
  }
  if ($p['type'] == 'search' && $p['cat'] && !$p['filtered']) {
    $out .= antique_seo_breadcrumbs(array(
      array('name' => __('Home', 'starter'), 'item' => osc_base_url()),
      array('name' => $p['cat']['s_name'], 'item' => osc_search_url(array('page' => 'search', 'sCategory' => $p['cat']['pk_i_id'])))
    ));
  }
  if ($p['type'] == 'shop') {
    $u = User::newInstance()->findByPrimaryKey($p['user']);
    $node = array(
      '@context' => 'https://schema.org',
      '@type' => (!empty($u['b_company']) ? 'Store' : 'Person'),
      'name' => antique_seller_display_name($p['user'], osc_user_name()),
      'url' => osc_user_public_profile_url($p['user']),
      'image' => starter_profile_picture($p['user'], 'medium')
    );
    $place = array_filter(array('addressLocality' => osc_user_city(), 'addressRegion' => osc_user_region(), 'addressCountry' => osc_user_country()));
    if ($place && !empty($u['b_company'])) { $node['address'] = array_merge(array('@type' => 'PostalAddress'), $place); }
    $out .= antique_seo_jsonld($node);
  }
  echo $out;
  if (function_exists('antique_consent_head')) { antique_consent_head(); }
}

function antique_seo_breadcrumbs($crumbs) {
  $list = array();
  foreach (array_values($crumbs) as $i => $c) {
    $list[] = array('@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['item']);
  }
  return antique_seo_jsonld(array('@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list));
}

/* ------------------------------ friendly URL redirects ------------------------------ */

/**
 * With friendly URLs on, old index.php?page=... links (bookmarks, Google, emails sent
 * earlier) move permanently to the friendly address so each page has one URL.
 */
function antique_seo_redirect_old_urls() {
  if (!osc_rewrite_enabled() || $_SERVER['REQUEST_METHOD'] !== 'GET' || defined('OC_ADMIN')) { return; }
  $uri = (string)$_SERVER['REQUEST_URI'];
  if (strpos($uri, 'index.php') === false || strpos($uri, 'page=') === false) { return; }
  $page = Params::getParam('page');
  $keep = array();
  foreach ($_GET as $k => $v) { if (preg_match('/^(utm_[a-z]+|gclid|fbclid|msclkid)$/', $k) && is_string($v)) { $keep[$k] = $v; } }
  $target = '';
  if ($page == 'item' && Params::getParam('action') == '' && (int)Params::getParam('id') > 0) {
    $item = Item::newInstance()->findByPrimaryKey((int)Params::getParam('id'));
    if ($item) { $target = osc_item_url_from_item($item); }
  } elseif ($page == 'user' && Params::getParam('action') == 'pub_profile' && (int)Params::getParam('id') > 0 && count(array_diff(array_keys($_GET), array_merge(array('page', 'action', 'id'), array_keys($keep)))) == 0) {
    $target = osc_user_public_profile_url((int)Params::getParam('id'));
  } elseif ($page == 'search' && count(array_diff(array_keys($_GET), array_merge(array('page', 'sCategory', 'sAuction', 'iPage'), array_keys($keep)))) == 0) {
    $args = array('page' => 'search');
    foreach (array('sCategory', 'sAuction', 'iPage') as $k) { if (Params::getParam($k) !== '') { $args[$k] = Params::getParam($k); } }
    $target = osc_search_url($args);
  } elseif ($page == 'custom' && function_exists('antique_info_pages') && isset(antique_info_pages()[Params::getParam('route')])) {
    $target = osc_route_url(Params::getParam('route'));
  }
  if ($target !== '' && strpos($target, 'index.php') === false) {
    if ($keep) { $target .= (strpos($target, '?') === false ? '?' : '&') . http_build_query($keep); }
    header('Location: ' . $target, true, 301);
    exit;
  }
}
osc_add_hook('init', 'antique_seo_redirect_old_urls', 3);

/* ------------------------------ sitemap.xml and robots.txt ------------------------------ */

function antique_seo_sitemap_urls() {
  $db = Item::newInstance()->dao;
  $p = DB_TABLE_PREFIX;
  $urls = array();
  $urls[] = array('loc' => osc_base_url(), 'changefreq' => 'daily', 'priority' => '1.0');
  $urls[] = array('loc' => osc_search_url(array('page' => 'search', 'sAuction' => 1)), 'changefreq' => 'hourly', 'priority' => '0.9');
  $urls[] = array('loc' => osc_search_url(array('page' => 'search')), 'changefreq' => 'daily', 'priority' => '0.6');
  foreach (Category::newInstance()->listEnabled() as $c) {
    $urls[] = array('loc' => osc_search_url(array('page' => 'search', 'sCategory' => $c['pk_i_id'])), 'changefreq' => 'daily', 'priority' => empty($c['fk_i_parent_id']) ? '0.8' : '0.7');
  }
  if (function_exists('antique_info_pages')) {
    foreach (array_keys(antique_info_pages()) as $id) { $urls[] = array('loc' => osc_route_url($id), 'changefreq' => 'monthly', 'priority' => '0.3'); }
  }
  // shops with something for sale
  $res = $db->query("SELECT DISTINCT i.fk_i_user_id FROM `{$p}t_item` i WHERE i.fk_i_user_id > 0 AND i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0 AND (i.dt_expiration IS NULL OR i.dt_expiration > NOW()) LIMIT 5000");
  foreach (($res ? $res->result() : array()) as $r) {
    $urls[] = array('loc' => osc_user_public_profile_url((int)$r['fk_i_user_id']), 'changefreq' => 'weekly', 'priority' => '0.5');
  }
  // live listings, newest first, with their photos
  $res = $db->query("SELECT i.* FROM `{$p}t_item` i WHERE i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0 AND (i.dt_expiration IS NULL OR i.dt_expiration > NOW()) ORDER BY i.dt_pub_date DESC LIMIT " . ANTIQUE_SEO_SITEMAP_MAX);
  $items = $res ? $res->result() : array();
  $images = array();
  if ($items) {
    $ids = implode(',', array_map(function ($r) { return (int)$r['pk_i_id']; }, $items));
    $ir = $db->query("SELECT pk_i_id, fk_i_item_id, s_path, s_extension FROM `{$p}t_item_resource` WHERE fk_i_item_id IN ({$ids}) ORDER BY pk_i_id");
    foreach (($ir ? $ir->result() : array()) as $r) {
      if (count(isset($images[$r['fk_i_item_id']]) ? $images[$r['fk_i_item_id']] : array()) < 10) {
        $images[$r['fk_i_item_id']][] = osc_base_url() . $r['s_path'] . $r['pk_i_id'] . '.' . $r['s_extension'];
      }
    }
  }
  $locale = osc_current_user_locale();
  foreach ($items as $it) {
    $titleRes = $db->query(sprintf("SELECT s_title FROM `%st_item_description` WHERE fk_i_item_id = %d ORDER BY (fk_c_locale_code = %s) DESC LIMIT 1", $p, (int)$it['pk_i_id'], $db->escape($locale)));
    $it['s_title'] = ($titleRes && $titleRes->numRows() > 0) ? $titleRes->row()['s_title'] : '';
    $it['locale'] = array($locale => array('s_title' => $it['s_title']));
    $urls[] = array(
      'loc' => osc_item_url_from_item($it),
      'lastmod' => date('Y-m-d', strtotime($it['dt_mod_date'] ? $it['dt_mod_date'] : $it['dt_pub_date'])),
      'changefreq' => 'weekly', 'priority' => '0.7',
      'images' => isset($images[$it['pk_i_id']]) ? $images[$it['pk_i_id']] : array()
    );
  }
  return array_slice($urls, 0, 49000);
}

function antique_seo_write_sitemap() {
  $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
     . "<!-- antiquelion-sitemap -->\n"
     . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
  foreach (antique_seo_sitemap_urls() as $u) {
    $x .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>'
        . (isset($u['lastmod']) ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
        . '<changefreq>' . $u['changefreq'] . '</changefreq><priority>' . $u['priority'] . '</priority>';
    if (!empty($u['images'])) {
      foreach ($u['images'] as $img) { $x .= '<image:image><image:loc>' . htmlspecialchars($img, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</image:loc></image:image>'; }
    }
    $x .= "</url>\n";
  }
  $x .= "</urlset>\n";
  $staging = stripos(osc_base_url(), 'staging') !== false;
  $robots = "# Written by the AntiqueLion theme (inc.seo.php). Changes here are overwritten.\nUser-agent: *\n";
  if ($staging) {
    $robots .= "Disallow: /\n";
  } else {
    $robots .= "Disallow: /oc-admin/\nDisallow: /oc-content/downloads/\nDisallow: /oc-content/languages/\nDisallow: /oc-content/uploads/HTML/\nDisallow: /oc-content/uploads/minify/\nDisallow: /oc-content/uploads/temp/\nDisallow: /oc-content/uploads/al-private/\nDisallow: /oc-includes/\n"
             . "Disallow: /*page=ajax\nDisallow: /user/\nDisallow: /account/\nDisallow: /item/new\nDisallow: /item/edit\nDisallow: /item/send-friend\nAllow: /user/profile/\nAllow: /oc-content/uploads/\nAllow: /oc-includes/osclass/assets/\n\n"
             . 'Sitemap: ' . osc_base_url() . "sitemap.xml\n";
  }
  $ok = @file_put_contents(osc_base_path() . 'sitemap.xml.tmp', $x) !== false && @rename(osc_base_path() . 'sitemap.xml.tmp', osc_base_path() . 'sitemap.xml');
  $ok = (@file_put_contents(osc_base_path() . 'robots.txt', $robots) !== false) && $ok;
  osc_set_preference('seo_sitemap_built', time(), 'starter_theme', 'INTEGER');
  return $ok;
}

// refresh on a normal page view at most every few hours (after the page is sent)
function antique_seo_sitemap_cron() {
  $last = (int)osc_get_preference('seo_sitemap_built', 'starter_theme');
  $file = osc_base_path() . 'sitemap.xml';
  // rebuild when stale, missing, or overwritten by another sitemap tool (e.g. the old sitemap plugin)
  $ours = is_file($file) && strpos((string)file_get_contents($file, false, null, 0, 200), 'antiquelion-sitemap') !== false;
  if (time() - $last < ANTIQUE_SEO_SITEMAP_HOURS * 3600 && $ours) { return; }
  osc_set_preference('seo_sitemap_built', time(), 'starter_theme', 'INTEGER');   // claim before the work
  if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); } elseif (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); }
  antique_seo_write_sitemap();
}
osc_add_hook('footer', 'antique_seo_sitemap_cron', 10);

/* ------------------------------ Google Analytics with consent ------------------------------ */

function antique_ga_id() {
  $id = strtoupper(antique_seo_pref('seo_ga4'));
  return preg_match('/^G-[A-Z0-9]{4,16}$/', $id) ? $id : '';
}

/**
 * Google Analytics 4 in consent mode: nothing is stored until the visitor accepts
 * the cookie banner; the choice is remembered for a year (cookie al_consent).
 */
function antique_consent_head() {
  $ga = antique_ga_id();
  if ($ga === '') { return; }
  $choice = isset($_COOKIE['al_consent']) ? $_COOKIE['al_consent'] : '';
  ?>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: '<?php echo $choice === 'yes' ? 'granted' : 'denied'; ?>' });
  gtag('js', new Date());
  gtag('config', '<?php echo $ga; ?>', { anonymize_ip: true });
</script>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $ga; ?>"></script>
  <?php
}

function antique_consent_banner() {
  if (antique_ga_id() === '' || isset($_COOKIE['al_consent'])) { return; }
  ?>
  <div class="al-consent" role="dialog" aria-live="polite" aria-label="<?php echo osc_esc_html(__('Cookies', 'starter')); ?>">
    <p><?php echo sprintf(__('We use analytics cookies to see which pages are useful and improve the site. They are off unless you accept. <a href="%s">Privacy and cookies</a>', 'starter'), osc_route_url('al-privacy')); ?></p>
    <div class="al-consent-actions">
      <button type="button" class="al-btn al-btn-outline" data-al-consent="no"><?php _e('Only necessary', 'starter'); ?></button>
      <button type="button" class="al-btn al-btn-ink" data-al-consent="yes"><?php _e('Accept analytics', 'starter'); ?></button>
    </div>
  </div>
  <script>
    (function () {
      var box = document.querySelector('.al-consent');
      box.addEventListener('click', function (e) {
        var b = e.target.closest('[data-al-consent]');
        if (!b) { return; }
        var yes = b.getAttribute('data-al-consent') === 'yes';
        document.cookie = 'al_consent=' + (yes ? 'yes' : 'no') + '; path=/; max-age=31536000; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        if (yes && window.gtag) { gtag('consent', 'update', { analytics_storage: 'granted' }); }
        box.remove();
      });
    })();
  </script>
  <?php
}
osc_add_hook('footer', 'antique_consent_banner', 5);
