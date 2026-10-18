<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
</head>
<body id="body-user-alerts" class="body-ua al-account-page">
  <?php osc_current_web_theme_path('header.php'); ?>

  <div class="al-wrap al-account-layout">
    <aside class="al-account-side"><?php echo antique_account_nav(); ?></aside>

    <main class="al-account-main">
      <div class="al-account-head">
        <h2><?php _e('Saved searches', 'starter'); ?></h2>
        <a class="al-btn al-btn-outline" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('New search', 'starter'); ?></a>
      </div>

      <?php if (osc_count_alerts() > 0) { ?>
        <div class="al-alerts">
          <?php while (osc_has_alerts()) { ?>
            <?php
              // Describe the saved search in words (s_search holds the stored search parameters)
              $alert = View::newInstance()->_current('alerts');
              $params = (array)json_decode($alert['s_search'], true);
              $bits = array();
              if (!empty($params['sPattern'])) { $bits[] = '"' . $params['sPattern'] . '"'; }
              if (!empty($params['aCategories'])) {
                foreach ((array)$params['aCategories'] as $cid) {
                  $cat = Category::newInstance()->findByPrimaryKey((int)$cid);
                  if (!empty($cat['s_name'])) { $bits[] = $cat['s_name']; }
                }
              }
              if (!empty($params['price_min'])) { $bits[] = sprintf(__('from %s', 'starter'), $params['price_min']); }
              if (!empty($params['price_max'])) { $bits[] = sprintf(__('up to %s', 'starter'), $params['price_max']); }
              $label = count($bits) ? implode(' · ', array_unique($bits)) : __('All listings', 'starter');
            ?>
            <section class="al-panel al-alert">
              <div class="al-alert-head">
                <div>
                  <h3><?php echo osc_esc_html($label); ?></h3>
                  <span><?php echo sprintf(_n('%d current match', '%d current matches', osc_count_items(), 'starter'), osc_count_items()); ?></span>
                </div>
                <a class="al-btn al-btn-danger" href="<?php echo osc_user_unsubscribe_alert_url(); ?>" onclick="return confirm('<?php echo osc_esc_js(__('Stop email alerts for this search?', 'starter')); ?>');"><?php _e('Delete', 'starter'); ?></a>
              </div>
              <?php if (osc_count_items() > 0) { ?>
                <div class="al-grid al-alert-grid">
                  <?php $al_n = 0; while (osc_has_items() && $al_n < 4) { $al_n++; include __DIR__ . '/inc.al-card.php'; } ?>
                </div>
              <?php } ?>
            </section>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="al-empty-state">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 0 1-3.4 0"/></svg>
          <h2><?php _e('No saved searches yet', 'starter'); ?></h2>
          <p><?php _e('Search for something you collect, then choose "Save search" at the bottom of the results. We will email you when a match is listed.', 'starter'); ?></p>
          <a class="al-btn al-btn-ink" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Start a search', 'starter'); ?></a>
        </div>
      <?php } ?>
    </main>
  </div>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
