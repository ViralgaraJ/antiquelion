<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
</head>
<?php
  $al_ending = function_exists('antique_bids_ending_soon') ? antique_bids_ending_soon(4) : array();

  // Hero photo: a running auction's picture, else the newest listing with one
  $al_hero_img = '';
  $al_hero_alt = '';
  $al_hero_url = '';
  $al_res = Item::newInstance()->dao->query(
    "SELECT r.pk_i_id, r.s_path, r.s_extension, r.fk_i_item_id
     FROM " . DB_TABLE_PREFIX . "t_item_resource r
     JOIN " . DB_TABLE_PREFIX . "t_item i ON i.pk_i_id = r.fk_i_item_id
     WHERE i.b_enabled = 1 AND i.b_active = 1 AND i.b_spam = 0
     ORDER BY " . (count($al_ending) ? "FIELD(i.pk_i_id, " . implode(',', array_map('intval', array_reverse($al_ending))) . ") DESC, " : "") . "i.dt_pub_date DESC, r.pk_i_id ASC LIMIT 12"
  );
  if ($al_res) {
    foreach ($al_res->result() as $r) {
      if (is_file(osc_base_path() . $r['s_path'] . $r['pk_i_id'] . '.' . $r['s_extension'])) {
        $al_hero_img = osc_base_url() . $r['s_path'] . $r['pk_i_id'] . '.' . $r['s_extension'];
        $al_hero_item = Item::newInstance()->findByPrimaryKey($r['fk_i_item_id']);
        $al_hero_alt = isset($al_hero_item['s_title']) ? $al_hero_item['s_title'] : '';
        $al_hero_url = osc_item_url_ns($r['fk_i_item_id']);
        break;
      }
    }
  }

?>
<body id="body-home" class="al-home">
  <?php osc_current_web_theme_path('header.php'); ?>
  <?php osc_run_hook('home_top'); ?>
  <?php echo starter_banner('home_top'); ?>

  <section class="al-hero">
    <div class="al-wrap">
      <div class="al-hero-text">
        <div class="al-eyebrow"><?php _e('Antiques · Auctions · Worldwide shipping', 'starter'); ?></div>
        <h1 class="al-hero-title"><?php _e('Authentic antiques from trusted sellers, delivered to your door.', 'starter'); ?></h1>
        <p class="al-hero-lead"><?php _e('Bid on live auctions or buy directly from dealers and private collectors. Every price is shown in your currency.', 'starter'); ?></p>
        <div class="al-hero-actions">
          <a class="al-btn al-btn-gilt" href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('Browse live auctions', 'starter'); ?></a>
          <a class="al-btn al-btn-outline-light" href="<?php echo osc_item_post_url(); ?>"><?php _e('Start selling', 'starter'); ?></a>
        </div>
      </div>
      <?php if ($al_hero_img !== '') { ?>
        <a class="al-hero-media" href="<?php echo $al_hero_url; ?>">
          <img src="<?php echo $al_hero_img; ?>" alt="<?php echo osc_esc_html($al_hero_alt); ?>" />
        </a>
      <?php } ?>
    </div>
  </section>

  <section class="al-trust" aria-label="<?php echo osc_esc_html(__('Why AntiqueLion', 'starter')); ?>">
    <div class="al-wrap">
      <div class="al-trust-item">
        <span class="al-trust-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg></span>
        <div>
          <strong><?php _e('Prices in your currency', 'starter'); ?></strong>
          <span><?php _e('Converted daily from the seller\'s price', 'starter'); ?></span>
        </div>
      </div>
      <div class="al-trust-item">
        <span class="al-trust-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 2M9 2h6"/></svg></span>
        <div>
          <strong><?php _e('Auctions on your clock', 'starter'); ?></strong>
          <span><?php _e('Every countdown shows your local time', 'starter'); ?></span>
        </div>
      </div>
      <div class="al-trust-item">
        <span class="al-trust-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></span>
        <div>
          <strong><?php _e('Deal directly with sellers', 'starter'); ?></strong>
          <span><?php _e('Ask questions and agree shipping before you buy', 'starter'); ?></span>
        </div>
      </div>
    </div>
  </section>

  <?php if (count($al_ending) > 0) { ?>
    <section class="al-section">
      <div class="al-wrap">
        <div class="al-section-head">
          <h2><?php _e('Live auctions ending soon', 'starter'); ?></h2>
          <a href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('View all auctions', 'starter'); ?> &rarr;</a>
        </div>
        <div class="al-grid">
          <?php
            $al_items = array();
            foreach ($al_ending as $id) {
              $it = Item::newInstance()->findByPrimaryKey($id);
              if ($it) { $al_items[] = $it; }
            }
            View::newInstance()->_exportVariableToView('items', $al_items);
            while (osc_has_items()) {
              include __DIR__ . '/inc.al-card.php';
            }
            View::newInstance()->_erase('items');
          ?>
        </div>
      </div>
    </section>
  <?php } ?>

  <section class="al-section al-section-alt">
    <div class="al-wrap">
      <div class="al-section-head">
        <h2><?php _e('Browse by category', 'starter'); ?></h2>
        <a href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('All listings', 'starter'); ?> &rarr;</a>
      </div>
      <div class="al-cat-grid">
        <?php osc_goto_first_category(); ?>
        <?php while (osc_has_categories()) { if (osc_category_total_items() < 1) { continue; } ?>
          <a class="al-cat-tile" href="<?php echo osc_search_url(array('page' => 'search', 'sCategory' => osc_category_id())); ?>">
            <span class="al-cat-name"><?php echo osc_esc_html(osc_category_name()); ?></span>
            <span class="al-cat-count"><?php echo sprintf(_n('%d listing', '%d listings', osc_category_total_items(), 'starter'), osc_category_total_items()); ?></span>
          </a>
        <?php } ?>
      </div>
    </div>
  </section>

  <?php osc_get_premiums(osc_get_preference('premium_home_count', 'starter_theme')); ?>
  <?php if (osc_count_premiums() > 0 && osc_get_preference('premium_home', 'starter_theme') == 1) { ?>
    <section class="al-section">
      <div class="al-wrap">
        <div class="al-section-head"><h2><?php _e('Featured listings', 'starter'); ?></h2></div>
        <div class="al-grid">
          <?php while (osc_has_premiums()) { include __DIR__ . '/inc.al-card.php'; } ?>
        </div>
      </div>
    </section>
  <?php } ?>
  <?php osc_run_hook('home_premium'); ?>

  <section class="al-section">
    <div class="al-wrap">
      <div class="al-section-head">
        <h2><?php _e('Latest listings', 'starter'); ?></h2>
        <a href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('See all listings', 'starter'); ?> &rarr;</a>
      </div>
      <?php
        // skip listings already shown in "ending soon" above
        $al_latest = array_values(array_filter((array)starter_random_items(), function ($it) use ($al_ending) {
          return !in_array((int)$it['pk_i_id'], array_map('intval', $al_ending));
        }));
        View::newInstance()->_exportVariableToView('latestItems', array_slice($al_latest, 0, 8));
      ?>
      <?php if (osc_count_latest_items() > 0) { ?>
        <div class="al-grid">
          <?php while (osc_has_latest_items()) { include __DIR__ . '/inc.al-card.php'; } ?>
        </div>
      <?php } else { ?>
        <p class="al-empty"><?php _e('No listings yet. Be the first to sell something.', 'starter'); ?></p>
      <?php } ?>
      <?php View::newInstance()->_erase('items'); ?>
    </div>
  </section>
  <?php osc_run_hook('home_latest'); ?>

  <section class="al-sell-band">
    <div class="al-wrap">
      <div>
        <h2><?php _e('Have an antique to sell?', 'starter'); ?></h2>
        <p><?php _e('List it for free, set a price or run an auction, and reach collectors around the world.', 'starter'); ?></p>
      </div>
      <a class="al-btn al-btn-gilt" href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
    </div>
  </section>

  <?php echo starter_banner('home_bottom'); ?>
  <?php osc_run_hook('home_bottom'); ?>
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
