<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="index, follow" />
</head>
<?php
  $search_cat_id = osc_search_category_id();
  $search_cat_id = isset($search_cat_id[0]) ? (int)$search_cat_id[0] : 0;
  $al_auction = Params::getParam('sAuction') == '1';

  // Countries that currently have listings (keeps the filter honest)
  $al_countries = array();
  $al_res = Item::newInstance()->dao->query(
    "SELECT l.fk_c_country_code AS code, l.s_country AS name, COUNT(*) AS n
     FROM " . DB_TABLE_PREFIX . "t_item_location l
     JOIN " . DB_TABLE_PREFIX . "t_item i ON i.pk_i_id = l.fk_i_item_id
     WHERE i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0 AND l.fk_c_country_code <> ''
     GROUP BY l.fk_c_country_code, l.s_country ORDER BY l.s_country"
  );
  if ($al_res) { $al_countries = $al_res->result(); }

  // Sort options
  $al_orders = osc_list_orders();
  $al_current_order = '';
  foreach ($al_orders as $label => $params) {
    $type = ($params['iOrderType'] == 'asc') ? '0' : '1';
    if (osc_search_order() == $params['sOrder'] && osc_search_order_type() == $type) { $al_current_order = $label; }
  }

  // Active filter chips: label => URL without that filter
  $al_chips = array();
  // Every filter the page understands; a chip link keeps all of them except its own
  $al_keep = array('sPattern', 'sCategory', 'sAuction', 'sItemCountry', 'sCondition', 'sTransaction', 'sPeriod', 'bPic', 'seller_post', 'sOrder', 'iOrderType', 'sCompany', 'pmin', 'pmax', 'sEra', 'sMaterial', 'sOrigin');
  $al_strip = function ($keys) use ($al_keep) {
    $p = array('page' => 'search');
    foreach ($al_keep as $k) {
      if (!in_array($k, (array)$keys) && Params::getParam($k) != '') { $p[$k] = Params::getParam($k); }
    }
    return osc_search_url($p);
  };

  if (osc_search_pattern() != '') { $al_chips['"' . osc_search_pattern() . '"'] = $al_strip('sPattern'); }
  if ($search_cat_id > 0) {
    $c = Category::newInstance()->findByPrimaryKey($search_cat_id);
    if (!empty($c['s_name'])) { $al_chips[$c['s_name']] = $al_strip('sCategory'); }
  }
  if ($al_auction) { $al_chips[__('Live auctions', 'starter')] = $al_strip('sAuction'); }
  if (Params::getParam('sItemCountry') != '') {
    foreach ($al_countries as $co) {
      if (strtoupper($co['code']) == strtoupper(Params::getParam('sItemCountry'))) { $al_chips[$co['name']] = $al_strip('sItemCountry'); }
    }
  }
  if (Params::getParam('sCondition') != '') { $al_chips[Params::getParam('sCondition') == 1 ? __('New', 'starter') : __('Used', 'starter')] = $al_strip('sCondition'); }
  $al_det_opt = antique_detail_options();
  $al_origins = antique_details_origin_counts();
  if (isset($al_det_opt['era'][Params::getParam('sEra')])) { $al_chips[$al_det_opt['era'][Params::getParam('sEra')]] = $al_strip('sEra'); }
  if (isset($al_det_opt['material'][Params::getParam('sMaterial')])) { $al_chips[$al_det_opt['material'][Params::getParam('sMaterial')]] = $al_strip('sMaterial'); }
  if (isset($al_origins[strtoupper(Params::getParam('sOrigin'))])) { $al_chips[sprintf(__('Made in %s', 'starter'), $al_origins[strtoupper(Params::getParam('sOrigin'))][0])] = $al_strip('sOrigin'); }
  if (Params::getParam('sPeriod') != '') { $al_chips[sprintf(__('Listed in last %d days', 'starter'), (int)Params::getParam('sPeriod'))] = $al_strip('sPeriod'); }
  list($al_pmin, $al_pmax) = antique_price_filter_range();
  $al_cur = antique_visitor_currency();
  if ($al_pmin > 0 && $al_pmax > 0) {
    $al_chips[antique_money($al_pmin, $al_cur) . ' – ' . antique_money($al_pmax, $al_cur)] = $al_strip(array('pmin', 'pmax'));
  } elseif ($al_pmin > 0) {
    $al_chips[sprintf(__('From %s', 'starter'), antique_money($al_pmin, $al_cur))] = $al_strip('pmin');
  } elseif ($al_pmax > 0) {
    $al_chips[sprintf(__('Up to %s', 'starter'), antique_money($al_pmax, $al_cur))] = $al_strip('pmax');
  }
  if (osc_search_has_pic()) { $al_chips[__('With photos', 'starter')] = $al_strip('bPic'); }
  if (Params::getParam('seller_post') != '') { $al_chips[__('This seller', 'starter')] = $al_strip('seller_post'); }
?>
<body id="body-search" class="al-search-page">
<?php osc_current_web_theme_path('header.php'); ?>

<div class="al-wrap al-search-layout">
  <!-- FILTERS -->
  <aside class="al-filters" id="al-filters" aria-label="<?php echo osc_esc_html(__('Filters', 'starter')); ?>">
    <div class="al-filters-head">
      <h2><?php _e('Filters', 'starter'); ?></h2>
      <button type="button" class="al-filters-close" aria-label="<?php echo osc_esc_html(__('Close filters', 'starter')); ?>">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <form action="<?php echo osc_base_url(true); ?>" method="get" class="al-form al-filter-form nocsrf">
      <input type="hidden" name="page" value="search" />
      <input type="hidden" name="sOrder" value="<?php echo osc_esc_html(osc_search_order()); ?>" />
      <input type="hidden" name="iOrderType" value="<?php $t = Search::getAllowedTypesForSorting(); echo isset($t[osc_search_order_type()]) ? $t[osc_search_order_type()] : ''; ?>" />
      <?php if (Params::getParam('seller_post') != '') { ?><input type="hidden" name="seller_post" value="<?php echo (int)Params::getParam('seller_post'); ?>" /><?php } ?>
      <?php foreach (osc_search_user() as $userId) { ?><input type="hidden" name="sUser[]" value="<?php echo (int)$userId; ?>" /><?php } ?>

      <?php osc_run_hook('search_sidebar_pre'); ?>

      <label><span><?php _e('Keyword', 'starter'); ?></span>
        <input type="search" name="sPattern" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php echo osc_esc_html(__('e.g. brass lamp', 'starter')); ?>" />
      </label>

      <label><span><?php _e('Category', 'starter'); ?></span>
        <select name="sCategory">
          <option value=""><?php _e('All categories', 'starter'); ?></option>
          <?php foreach (osc_get_categories() as $c) { ?>
            <option value="<?php echo (int)$c['pk_i_id']; ?>"<?php echo $search_cat_id == $c['pk_i_id'] ? ' selected' : ''; ?>><?php echo osc_esc_html($c['s_name']); ?></option>
            <?php if (!empty($c['categories'])) { foreach ($c['categories'] as $sc) { ?>
              <option value="<?php echo (int)$sc['pk_i_id']; ?>"<?php echo $search_cat_id == $sc['pk_i_id'] ? ' selected' : ''; ?>>&nbsp;&nbsp;&nbsp;<?php echo osc_esc_html($sc['s_name']); ?></option>
            <?php } } ?>
          <?php } ?>
        </select>
      </label>

      <label class="al-check"><input type="checkbox" name="sAuction" value="1"<?php echo $al_auction ? ' checked' : ''; ?> /><span><?php _e('Live auctions only', 'starter'); ?></span></label>

      <?php if (count($al_countries) > 0) { ?>
        <label><span><?php _e('Item location', 'starter'); ?></span>
          <select name="sItemCountry">
            <option value=""><?php _e('Anywhere', 'starter'); ?></option>
            <?php foreach ($al_countries as $co) { ?>
              <option value="<?php echo osc_esc_html($co['code']); ?>"<?php echo strtoupper(Params::getParam('sItemCountry')) == strtoupper($co['code']) ? ' selected' : ''; ?>><?php echo osc_esc_html($co['name']); ?> (<?php echo (int)$co['n']; ?>)</option>
            <?php } ?>
          </select>
        </label>
      <?php } ?>

      <fieldset class="al-price-range">
        <legend><?php echo sprintf(__('Price (%s)', 'starter'), osc_esc_html($al_cur)); ?></legend>
        <div class="al-price-range-row">
          <label><span class="al-sr"><?php _e('Minimum price', 'starter'); ?></span><input type="number" name="pmin" min="0" step="any" inputmode="decimal" placeholder="<?php echo osc_esc_html(__('Min', 'starter')); ?>" value="<?php echo $al_pmin > 0 ? osc_esc_html(rtrim(rtrim(number_format($al_pmin, 2, '.', ''), '0'), '.')) : ''; ?>" /></label>
          <span class="al-price-range-sep" aria-hidden="true">–</span>
          <label><span class="al-sr"><?php _e('Maximum price', 'starter'); ?></span><input type="number" name="pmax" min="0" step="any" inputmode="decimal" placeholder="<?php echo osc_esc_html(__('Max', 'starter')); ?>" value="<?php echo $al_pmax > 0 ? osc_esc_html(rtrim(rtrim(number_format($al_pmax, 2, '.', ''), '0'), '.')) : ''; ?>" /></label>
        </div>
      </fieldset>

      <label><span><?php _e('Condition', 'starter'); ?></span>
        <select name="sCondition">
          <option value=""><?php _e('Any condition', 'starter'); ?></option>
          <option value="1"<?php echo Params::getParam('sCondition') == '1' ? ' selected' : ''; ?>><?php _e('New', 'starter'); ?></option>
          <option value="2"<?php echo Params::getParam('sCondition') == '2' ? ' selected' : ''; ?>><?php _e('Used', 'starter'); ?></option>
        </select>
      </label>

      <label><span><?php _e('Period', 'starter'); ?></span>
        <select name="sEra">
          <option value=""><?php _e('Any period', 'starter'); ?></option>
          <?php foreach ($al_det_opt['era'] as $k => $l) { if ($k == 'unknown') { continue; } ?>
            <option value="<?php echo $k; ?>"<?php echo Params::getParam('sEra') === (string)$k ? ' selected' : ''; ?>><?php echo osc_esc_html($l); ?></option>
          <?php } ?>
        </select>
      </label>

      <label><span><?php _e('Material', 'starter'); ?></span>
        <select name="sMaterial">
          <option value=""><?php _e('Any material', 'starter'); ?></option>
          <?php foreach ($al_det_opt['material'] as $k => $l) { ?>
            <option value="<?php echo $k; ?>"<?php echo Params::getParam('sMaterial') === (string)$k ? ' selected' : ''; ?>><?php echo osc_esc_html($l); ?></option>
          <?php } ?>
        </select>
      </label>

      <?php if (count($al_origins) > 0) { ?>
        <label><span><?php _e('Country of origin', 'starter'); ?></span>
          <select name="sOrigin">
            <option value=""><?php _e('Any origin', 'starter'); ?></option>
            <?php foreach ($al_origins as $code => $o) { ?>
              <option value="<?php echo osc_esc_html($code); ?>"<?php echo strtoupper(Params::getParam('sOrigin')) == $code ? ' selected' : ''; ?>><?php echo osc_esc_html($o[0]); ?> (<?php echo (int)$o[1]; ?>)</option>
            <?php } ?>
          </select>
        </label>
      <?php } ?>

      <label><span><?php _e('Listed', 'starter'); ?></span>
        <select name="sPeriod">
          <option value=""><?php _e('Any time', 'starter'); ?></option>
          <?php foreach (array(1 => __('Last 24 hours', 'starter'), 7 => __('Last 7 days', 'starter'), 14 => __('Last 14 days', 'starter'), 31 => __('Last 30 days', 'starter')) as $v => $l) { ?>
            <option value="<?php echo $v; ?>"<?php echo Params::getParam('sPeriod') == $v ? ' selected' : ''; ?>><?php echo $l; ?></option>
          <?php } ?>
        </select>
      </label>

      <?php if (osc_images_enabled_at_items()) { ?>
        <label class="al-check"><input type="checkbox" name="bPic" value="1"<?php echo osc_search_has_pic() ? ' checked' : ''; ?> /><span><?php _e('With photos only', 'starter'); ?></span></label>
      <?php } ?>

      <div class="sidebar-hooks">
        <?php
          if (osc_search_category_id()) {
            osc_run_hook('search_form', osc_search_category_id());
          } else {
            osc_run_hook('search_form');
          }
        ?>
      </div>

      <div class="al-filter-actions">
        <button type="submit" class="al-btn al-btn-ink al-btn-block"><?php _e('Show results', 'starter'); ?></button>
        <a class="al-text-link" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Clear all filters', 'starter'); ?></a>
      </div>
      <?php osc_run_hook('search_sidebar_bottom'); ?>
    </form>

    <?php osc_run_hook('search_sidebar_after'); ?>
    <?php echo starter_banner('search_sidebar'); ?>
  </aside>
  <div class="al-filters-backdrop" hidden></div>

  <!-- RESULTS -->
  <main class="al-results" id="search-items">
    <div class="al-results-bar">
      <button type="button" class="al-btn al-btn-outline al-filters-open" aria-controls="al-filters" aria-expanded="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
        <?php _e('Filters', 'starter'); ?><?php echo count($al_chips) ? ' (' . count($al_chips) . ')' : ''; ?>
      </button>

      <?php if (count($al_orders) > 0) { ?>
        <label class="al-sort">
          <span><?php _e('Sort by', 'starter'); ?></span>
          <select onchange="if (this.value) { window.location.href = this.value; }">
            <?php foreach ($al_orders as $label => $params) { ?>
              <option value="<?php echo osc_esc_html(osc_update_search_url($params)); ?>"<?php echo $label == $al_current_order ? ' selected' : ''; ?>><?php echo osc_esc_html($label); ?></option>
            <?php } ?>
          </select>
        </label>
      <?php } ?>
    </div>

    <?php if (count($al_chips) > 0) { ?>
      <div class="al-chips">
        <?php foreach ($al_chips as $label => $url) { ?>
          <a class="al-chip" href="<?php echo osc_esc_html($url); ?>" aria-label="<?php echo osc_esc_html(sprintf(__('Remove filter %s', 'starter'), $label)); ?>"><?php echo osc_esc_html($label); ?> <span aria-hidden="true">&times;</span></a>
        <?php } ?>
        <a class="al-chip-clear" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Clear all', 'starter'); ?></a>
      </div>
    <?php } ?>

    <?php osc_run_hook('search_items_top'); ?>
    <?php osc_run_hook('search_items_filter'); ?>

    <?php if (osc_count_items() == 0) { ?>
      <div class="al-empty-state">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8 11h6"/></svg>
        <?php if (Params::getParam('sAuction') == '1' && trim((string)Params::getParam('sPattern')) == '' && Params::getParam('sCategory') == '') { ?>
          <h2><?php _e('No auctions are running right now', 'starter'); ?></h2>
          <p><?php _e('New auctions start every week. Browse fixed-price listings in the meantime, or sell your own antique by auction.', 'starter'); ?></p>
        <?php } else { ?>
          <h2><?php _e('No listings match your search', 'starter'); ?></h2>
          <p><?php _e('Try fewer filters or a different keyword. You can also save this search and we will email you when a match is listed.', 'starter'); ?></p>
        <?php } ?>
        <a class="al-btn al-btn-ink" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Browse all listings', 'starter'); ?></a>
      </div>
    <?php } else { ?>
      <?php echo starter_banner('search_top'); ?>
      <?php require 'search_gallery.php'; ?>
    <?php } ?>

    <?php osc_run_hook('search_items_bottom'); ?>

    <?php $al_pages = osc_search_pagination(); ?>
    <?php if (trim(strip_tags($al_pages)) !== '') { ?>
      <nav class="al-pagination" aria-label="<?php echo osc_esc_html(__('Pages', 'starter')); ?>"><?php echo $al_pages; ?></nav>
    <?php } ?>

    <?php
      // Save this search (email alerts)
      $al_alert = osc_search_alert();
    ?>
    <section class="al-save-search">
      <div>
        <h2><?php _e('Get an email when new matches are listed', 'starter'); ?></h2>
        <p><?php _e('We will send you new listings for this search. Unsubscribe at any time.', 'starter'); ?></p>
      </div>
      <form class="al-save-form" action="<?php echo osc_base_url(true); ?>" method="post" novalidate>
        <input type="hidden" name="alert" value="<?php echo osc_esc_html($al_alert); ?>" />
        <input type="hidden" name="userid" value="<?php echo (int)osc_logged_user_id(); ?>" />
        <?php if (osc_is_web_user_logged_in()) { ?>
          <input type="hidden" name="email" value="<?php echo osc_esc_html(osc_logged_user_email()); ?>" />
        <?php } else { ?>
          <label class="al-sr-only" for="al-alert-email"><?php _e('Email', 'starter'); ?></label>
          <input type="email" id="al-alert-email" name="email" required placeholder="<?php echo osc_esc_html(__('you@example.com', 'starter')); ?>" />
        <?php } ?>
        <button type="submit" class="al-btn al-btn-brass"><?php _e('Save search', 'starter'); ?></button>
        <p class="al-save-msg" role="status" hidden></p>
      </form>
    </section>

    <?php echo starter_banner('search_bottom'); ?>
  </main>
</div>

<script>
  (function () {
    // Mobile filter drawer
    var panel = document.getElementById('al-filters');
    var openBtn = document.querySelector('.al-filters-open');
    var closeBtn = document.querySelector('.al-filters-close');
    var backdrop = document.querySelector('.al-filters-backdrop');
    function setOpen(open) {
      document.body.classList.toggle('al-filters-is-open', open);
      backdrop.hidden = !open;
      openBtn.setAttribute('aria-expanded', open);
    }
    openBtn.addEventListener('click', function () { setOpen(true); });
    closeBtn.addEventListener('click', function () { setOpen(false); });
    backdrop.addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });

    // Save search via the core alerts endpoint
    var form = document.querySelector('.al-save-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var msg = form.querySelector('.al-save-msg');
        var email = form.querySelector('[name=email]');
        if (email.type === 'email' && !email.checkValidity()) {
          msg.hidden = false; msg.className = 'al-save-msg is-error';
          msg.textContent = '<?php echo osc_esc_js(__('Please enter a valid email address.', 'starter')); ?>';
          email.focus();
          return;
        }
        var body = new URLSearchParams();
        body.set('page', 'ajax'); body.set('action', 'alerts');
        body.set('email', email.value);
        body.set('userid', form.querySelector('[name=userid]').value);
        body.set('alert', form.querySelector('[name=alert]').value);
        var btn = form.querySelector('button'); btn.disabled = true;
        fetch(form.action, { method: 'POST', body: body, credentials: 'same-origin' })
          .then(function (r) { return r.text(); })
          .then(function (t) {
            t = t.trim();
            msg.hidden = false;
            if (t === '1') {
              msg.className = 'al-save-msg is-ok';
              msg.textContent = '<?php echo osc_esc_js(__('Search saved. Check your inbox to confirm the alert.', 'starter')); ?>';
            } else if (t === '-1') {
              msg.className = 'al-save-msg is-error';
              msg.textContent = '<?php echo osc_esc_js(__('That email address is not valid.', 'starter')); ?>';
            } else {
              msg.className = 'al-save-msg is-error';
              msg.textContent = '<?php echo osc_esc_js(__('You already saved this search, or it could not be saved. Please try again.', 'starter')); ?>';
            }
          })
          .catch(function () {
            msg.hidden = false; msg.className = 'al-save-msg is-error';
            msg.textContent = '<?php echo osc_esc_js(__('Network error. Please try again.', 'starter')); ?>';
          })
          .then(function () { btn.disabled = false; });
      });
    }
  })();
</script>

<?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
