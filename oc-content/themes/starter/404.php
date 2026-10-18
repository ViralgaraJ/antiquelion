<!DOCTYPE html>
<html dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
</head>
<body id="body-404">
  <?php osc_current_web_theme_path('header.php'); ?>
  <div class="al-wrap al-404">
    <div class="al-empty-state">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M9 9l4 4M13 9l-4 4"/></svg>
      <h2><?php _e('We could not find that page', 'starter'); ?></h2>
      <p><?php _e('The listing may have sold or been removed, or the link may be wrong.', 'starter'); ?></p>
      <form class="al-404-search" action="<?php echo osc_base_url(true); ?>" method="get">
        <input type="hidden" name="page" value="search" />
        <label class="al-sr-only" for="al-404-q"><?php _e('Search antiques', 'starter'); ?></label>
        <input type="search" id="al-404-q" name="sPattern" placeholder="<?php echo osc_esc_html(__('Search antiques', 'starter')); ?>" />
        <button type="submit" class="al-btn al-btn-ink"><?php _e('Search', 'starter'); ?></button>
      </form>
      <div class="al-inline-actions al-404-links">
        <a class="al-text-link" href="<?php echo osc_base_url(); ?>"><?php _e('Go to the home page', 'starter'); ?></a>
        <a class="al-text-link" href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('Browse live auctions', 'starter'); ?></a>
      </div>
    </div>
  </div>
  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
