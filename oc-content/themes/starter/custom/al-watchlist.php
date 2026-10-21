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
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M12 20.5s-7.5-4.6-9.3-9.4C1.5 7.9 3.6 4.5 7.2 4.5c2 0 3.6 1.1 4.8 2.8 1.2-1.7 2.8-2.8 4.8-2.8 3.6 0 5.7 3.4 4.5 6.6-1.8 4.8-9.3 9.4-9.3 9.4z"/></svg>
    <h2><?php _e('Your watchlist is empty', 'starter'); ?></h2>
    <p><?php _e('Tap the heart on any listing to keep it here. We will remind you before watched auctions end.', 'starter'); ?></p>
    <a class="al-btn al-btn-ink" href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('Browse listings', 'starter'); ?></a>
  </div>
<?php } ?>
