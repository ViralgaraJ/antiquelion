<?php
  // Create menu
  $title = __('Configure', 'pass_strength');
  pss_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  $link_remover_order_id = mb_param_update('link_remover_order_id', 'plugin_action', 'value', 'plugin-pass_strength');
  $remove_footer_link = mb_param_update('remove_footer_link', 'plugin_action', 'check', 'plugin-pass_strength');



  if(Params::getParam('plugin_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved.', 'pass_strength'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=pass_strength/admin/configure.php');
    exit;
  }
?>


<div class="mb-body">
  
  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'pass_strength'); ?></div>

    <div class="mb-inside mb-minify">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <?php if(!pss_is_demo()) { ?>
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />
        <?php } ?>

        <div class="mb-notes">
          <div class="mb-line"><?php _e('Plugin generates backlink link added to footer.', 'pass_strength'); ?></div>
          <div class="mb-line"><?php echo sprintf(__('You can use plugin for free with link in footer, or purchase "%s" for this product and disable link.', 'pass_strength'), '<a target="_blank" href="https://osclasspoint.com/services/customer-services/remove-free-product-footer-link-i221">Link remover service</a>'); ?></div>
          <div class="mb-line" style="font-weight:bold;"><?php _e('Removal of link without link remover service purchased violates product license!', 'pass_strength'); ?></div>
        </div>

        <div class="mb-row">
          <label for="link_remover_order_id" class=""><span><?php _e('OsclassPoint Order Id', 'pass_strength'); ?></span></label> 
          <input name="link_remover_order_id" size="20" type="text" value="<?php echo $link_remover_order_id; ?>" />

          <div class="mb-explain"><?php _e('Enter valid OsclassPoint order ID that contains "Link removal service" product.', 'pass_strength'); ?></div>
        </div>
        
        
        <div class="mb-row <?php if($link_remover_order_id < 8000 || $link_remover_order_id > 100000) { ?>mb-disabled<?php } ?>">
          <label for="remove_footer_link" class=""><span><?php _e('Remove Backlink from Footer', 'pass_strength'); ?></span></label> 
          <input name="remove_footer_link" type="checkbox" class="element-slide" <?php if($link_remover_order_id < 8000 || $link_remover_order_id > 100000) { ?>disabled<?php } ?> <?php echo ($remove_footer_link == 1 ? 'checked' : ''); ?> />

          <div class="mb-explain">
            <div class="mb-link"><?php _e('When checked, footer backlink will be removed from footer. It require valid OsclassPoint order ID.', 'pass_strength'); ?></div>
            <div class="mb-link"><?php _e('This plugin license allows to remove link just in case "Link remover service" was purchased for it.', 'pass_strength'); ?></div>
          </div>
        </div>
        
        
        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(pss_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'pass_strength')); ?>"><?php _e('Save', 'pass_strength');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'pass_strength');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>


  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'pass_strength'); ?></div>

    <div class="mb-inside">
      <div class="mb-row"><?php _e('Plugin does not require modifications in files.', 'pass_strength'); ?></div>
    </div>
  </div>
</div>


<?php echo pss_footer(); ?>