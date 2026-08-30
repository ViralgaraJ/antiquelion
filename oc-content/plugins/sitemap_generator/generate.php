<?php if (!defined('OC_ADMIN') || OC_ADMIN!==true) exit('Access is not allowed.'); ?>

<?php sitemap_generator(); ?>

<div id="settings_form">
  <div>
    <div>
      <fieldset>
        <h2><?php _e('Sitemap Generator Help', 'sitemap_generator'); ?></h2>
        <?php _e('Sitemap.xml generated correctly', 'sitemap_generator'); ?>.
      </fieldset>
    </div>
  </div>
</div>
