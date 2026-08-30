<?php
  // Create menu
  $title = __('Configure', 'minify');
  mnf_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value
  $css_enabled = mb_param_update('css_enabled', 'plugin_action', 'check', 'plugin-minify');
  $css_footer = mb_param_update('css_footer', 'plugin_action', 'check', 'plugin-minify');
  $js_enabled = mb_param_update('js_enabled', 'plugin_action', 'check', 'plugin-minify');
  $js_all = mb_param_update('js_all', 'plugin_action', 'check', 'plugin-minify');
  $support = mb_param_update('support', 'plugin_action', 'check', 'plugin-minify');


  if(Params::getParam('plugin_action') == 'done') {
    message_ok( __('Settings were successfully saved', 'minify') );
  }

  if(Params::getParam('refresh') == 1) {
    message_ok( __('Style sheets and javascript files refreshed', 'minify') );
  }

?>


<div class="mb-body">

  <div class="mb-message-js"></div>

  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'minify'); ?></div>

    <div class="mb-inside mb-minify">
      <div class="mb-row mb-notes">
        <div class="mb-line"><?php _e('Test plugin well before using it on live site, there may be some problems especially with JS compression.', 'minify'); ?></div>
        <div class="mb-line"><?php _e('Only CSS added using osc_enqueue_style and JS added using osc_register_script functions will be minified.', 'minify'); ?></div>
        <div class="mb-line"><?php _e('Minified files must be refreshed each time theme is changed, new plugins are installed or any CSS or JS file is updated.', 'minify'); ?></div>
      </div>

      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />

        <div class="mb-row">
          <label for="css_enabled" class="h1"><span><?php _e('CSS Minify', 'minify'); ?></span></label> 
          <input name="css_enabled" id="css_enabled" type="checkbox" class="element-slide" <?php echo ($css_enabled == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('CSS compression will be enabled.', 'minify'); ?></div>
        </div>

        <div class="mb-row">
          <label for="js_enabled" class="h2"><span><?php _e('JS Minify', 'minify'); ?></span></label> 
          <input name="js_enabled" id="js_enabled" type="checkbox" class="element-slide" <?php echo ($js_enabled == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('JS compression will be enabled.', 'minify'); ?></div>
        </div>

        <div class="mb-row">
          <label for="js_all" class="h5"><span><?php _e('Compress all JS files', 'minify'); ?></span></label> 
          <input name="js_all" id="js_all" type="checkbox" class="element-slide" <?php echo ($js_all == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('All JS files registered on your site will be compressed into one. When disabled, only scripts registered on homepage are compressed into 1 file.', 'minify'); ?></div>
        </div>

        <div class="mb-row">
          <label for="support" class="h3"><span><?php _e('Support Author', 'minify'); ?></span></label> 
          <input name="support" id="support" type="checkbox" class="element-slide" <?php echo ($support == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('Provide credits to author of this great and free plugin. It does not cost you anything, author spent time to develop this for you.', 'minify'); ?></div>
        </div>

        <div class="mb-row">
          <label for="css_footer" class="h4"><span><?php _e('Move CSS to Footer', 'minify'); ?></span></label> 
          <input name="css_footer" id="css_footer" type="checkbox" class="element-slide" <?php echo ($css_footer == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, minified style sheet will be placed in footer instead of header.', 'minify'); ?></div>
        </div>

        <div class="mb-row">
          <a href="<?php echo osc_base_url(true); ?>?mnfRefresh=1" class="mb-button refresh"><?php _e('Refresh CSS & JS now!', 'minify'); ?></a>

          <em style="clear:both;margin:10px 0 0 25%;display:block;font-size:12px;color:#999;line-height:16px;">
            <?php 
              if(osc_get_preference('css_name', 'plugin-minify') <> '') {
                $css = osc_base_path() . 'oc-content/plugins/minify/generated/css/' . osc_get_preference('css_name', 'plugin-minify') . '.css';
            
                if(file_exists($css)) {
                  echo __('CSS last update:', 'minify') . ' '  . date("F d Y H:i:s.", filemtime($css)) . '<br/>';
                }
              }

              if(osc_get_preference('js_name', 'plugin-minify') <> '') {
                $js = osc_base_path() . 'oc-content/plugins/minify/generated/js/' . osc_get_preference('js_name', 'plugin-minify') . '.js';
            
                if(file_exists($js)) {
                  echo __('JS last update:', 'minify') . ' '  . date("F d Y H:i:s.", filemtime($js)) . '<br/>';
                }
              }
            ?>
          </em>
        </div>

        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <button type="submit" class="mb-button"><?php _e('Save', 'minify');?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php echo mnf_footer(); ?>