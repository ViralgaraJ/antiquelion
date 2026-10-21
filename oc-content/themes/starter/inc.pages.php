<?php
if (!defined('ABS_PATH')) { exit('ABS_PATH is not loaded. Direct access is not allowed.'); }

/*
 * Information pages (About, safety, terms, privacy) served from the theme,
 * so they ship with the code instead of living only in one database.
 * URLs: osc_route_url('al-about') etc.
 */

define('ANTIQUE_PAGES_UPDATED', '21 October 2026');

function antique_info_pages() {
  return array(
    'al-about'   => array('slug' => 'about',   'title' => __('About AntiqueLion', 'starter'),         'nav' => __('About us', 'starter'),
                          'lead' => __('A marketplace for authentic antiques and collectibles, open to buyers and sellers worldwide.', 'starter')),
    'al-safety'  => array('slug' => 'safety',  'title' => __('Buying and selling safely', 'starter'), 'nav' => __('How it works & safety', 'starter'),
                          'lead' => __('How buying, bidding and selling work on AntiqueLion, and how to stay safe.', 'starter')),
    'al-terms'   => array('slug' => 'terms',   'title' => __('Terms of use', 'starter'),              'nav' => __('Terms of use', 'starter'),
                          'lead' => __('The rules for using AntiqueLion. By using the site you agree to them.', 'starter')),
    'al-privacy' => array('slug' => 'privacy', 'title' => __('Privacy and cookies', 'starter'),       'nav' => __('Privacy & cookies', 'starter'),
                          'lead' => __('What information AntiqueLion collects, why, and the choices you have.', 'starter')),
  );
}

function antique_register_info_routes() {
  foreach (antique_info_pages() as $id => $p) {
    osc_add_route($id, $p['slug'] . '/?$', $p['slug'] . '/', $id . '.php', false, 'custom', $id, $p['title']);
  }
}
antique_register_info_routes();

function antique_info_page_url($id) {
  return osc_route_url($id);
}

// <title> for the information pages
function antique_info_page_title($text) {
  $pages = antique_info_pages();
  $route = Params::getParam('route');
  if (osc_get_osclass_location() == 'custom' && isset($pages[$route])) {
    return $pages[$route]['title'] . ' - ' . osc_page_title();
  }
  return $text;
}
osc_add_filter('meta_title_filter', 'antique_info_page_title');

/**
 * Page frame: title band, side navigation between the pages, article body.
 */
// the title band is printed by header.php (full width, like every other page)
function antique_info_page_open($id) {
  $pages = antique_info_pages();
  ?>
  <div class="al-wrap al-info">
    <nav class="al-info-nav al-panel" aria-label="<?php echo osc_esc_html(__('Help pages', 'starter')); ?>">
      <?php foreach ($pages as $pid => $p) { ?>
        <a href="<?php echo antique_info_page_url($pid); ?>"<?php echo $pid == $id ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo osc_esc_html($p['nav']); ?></a>
      <?php } ?>
      <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact us', 'starter'); ?></a>
    </nav>
    <article class="al-info-body al-panel">
  <?php
}

function antique_info_page_close($show_updated = true) {
  ?>
      <?php if ($show_updated) { ?>
        <p class="al-info-updated"><?php echo sprintf(__('Last updated %s.', 'starter'), ANTIQUE_PAGES_UPDATED); ?></p>
      <?php } ?>
    </article>
  </div>
  <?php
}
