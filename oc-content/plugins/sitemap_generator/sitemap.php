<div id="settings_form">
  <div>
    <div>
      <fieldset>
        <h2><?php _e('Sitemap Generator Help', 'sitemap_generator'); ?></h2>
        <h3><?php _e('What is Sitemap Generator Plugin?', 'sitemap_generator') ;?></h3>
        <?php _e('Sitemap Generator plugin allows you to generate a sitemap.xml file and ping the major search engines so they will be able to index your site', 'sitemap_generator'); ?>.

        <h3><?php _e('How does Sitemap Generator plugin work?', 'sitemap_generator') ;?></h3>
        <?php _e('The plugin will generate a sitemap.xml file on the root of your OSClass installation. The folder <b>must have write permissions</b> to work correctly. The sitemap.xml file will be generated hourly and at the same time will ping the major search engines. No user interaction is needed', 'sitemap_generator'); ?>.

        <h3><?php _e('How do I generate sitemaps manually?', 'sitemap_generator') ;?></h3>
        <?php _e('Sitemap file generation could take some resources and time depending on how big your website is. We strongly suggest to run it manually via a system\'s cron. To achieve that, you should modify index.php file, and comment or remove tha last line (osc_add_hook(\'cron_daily\', \'sitemap_generator\');) and run manual_cron.php instead on your system\'s cron', 'sitemap_generator'); ?>.
      </fieldset>
    </div>
  </div>
</div>