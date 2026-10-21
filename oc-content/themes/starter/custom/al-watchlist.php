<?php
  $al_items = antique_watch_items();
  View::newInstance()->_exportVariableToView('items', $al_items);
?>
<div class="al-account-head">
  <h2><?php _e('Watchlist', 'starter'); ?></h2>
  <?php if (count($al_items) > 0) { ?><span class="al-account-count"><?php echo sprintf(_n('%d item', '%d items', count($al_items), 'starter'), count($al_items)); ?></span><?php } ?>
</div>

<?php if (count($al_items) > 0) { ?>
  <p class="al-account-intro"><?php echo sprintf(__('We email you %d hours before a watched auction ends. Tap the heart on a listing to remove it.', 'starter'), ANTIQUE_WATCH_ENDING_HOURS); ?></p>
  <div class="al-grid al-watch-grid">
    <?php while (osc_has_items()) { include dirname(__DIR__) . '/inc.al-card.php'; } ?>
  </div>
<?php } else { ?>
  <div class="al-empty-state">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/></svg>
    <h2><?php _e('Your watchlist is empty', 'starter'); ?></h2>
    <p><?php _e('Tap the heart on any listing to keep it here. We will remind you before watched auctions end.', 'starter'); ?></p>
    <a class="al-btn al-btn-ink" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Browse listings', 'starter'); ?></a>
  </div>
<?php } ?>
