<?php if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); } ?>
<div class="search-items-wrap">
  <div class="al-grid al-search-grid">
    <?php
      // Featured (premium) listings first, when enabled in the theme settings
      osc_get_premiums(osc_get_preference('premium_search_gallery_count', 'starter_theme'));
      if (osc_count_premiums() > 0 && osc_get_preference('premium_search_gallery', 'starter_theme') == 1) {
        while (osc_has_premiums()) {
          include __DIR__ . '/inc.al-card.php';
        }
      }

      while (osc_has_items()) {
        include __DIR__ . '/inc.al-card.php';
      }
    ?>
  </div>
  <?php View::newInstance()->_erase('items'); ?>
</div>
