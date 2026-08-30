<?php
  // Create menu
  $title = __('Configure', 'translator_deepl');
  trd_menu($title);

  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  if(!trd_is_demo()) {
    $api_key = mb_param_update('api_key', 'plugin_action', 'value', 'plugin-translator_deepl');
  } else {
    $api_key = '*****-*****-********-****:***';
  }
  
  $limit = mb_param_update('limit', 'plugin_action', 'value', 'plugin-translator_deepl');
  $context = mb_param_update('context', 'plugin_action', 'value', 'plugin-translator_deepl');
  $glossary_id = mb_param_update('glossary_id', 'plugin_action', 'value', 'plugin-translator_deepl');
  $override_translation = mb_param_update('override_translation', 'plugin_action', 'check', 'plugin-translator_deepl');
  $use_cache = mb_param_update('use_cache', 'plugin_action', 'check', 'plugin-translator_deepl');
  $enable_beta = mb_param_update('enable_beta', 'plugin_action', 'check', 'plugin-translator_deepl');
  
  if(Params::getParam('plugin_action') == 'done') {
    osc_add_flash_ok_message(__('Settings were successfully saved.', 'translator_deepl'), 'admin');
    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=translator_deepl/admin/configure.php');
    exit;
  }
  
  if(Params::getParam('what') == 'cleancache') {
    ModelTRD::newInstance()->deleteAllTranslations();
    osc_add_flash_ok_message(__('Translations cache cleaned successfully.', 'translator_deepl'), 'admin');

    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=translator_deepl/admin/configure.php');
    exit;
  }
  
  
  $count = ModelTRD::newInstance()->countTranslations();
  $count_translations = (int)(isset($count['rec_count']) ? $count['rec_count'] : 0);
  $count_languages = (int)(isset($count['lang_count']) ? $count['lang_count'] : 0);
?>

<div class="mb-body">
  <div class="mb-notes">
    <div class="mb-line"><?php _e('DeepL provides 500000 characters free translations monthly. Register for developer account of type "DeepL API Free".', 'translator_deepl'); ?> <a  target="_blank" href="https://www.deepl.com/pro#developer"><?php _e('Go to DeepL.com', 'translator_deepl'); ?></a></div>
    <div class="mb-line"><?php _e('Translations are sent to DeepL.com term by term, request by request. It means it can take 1 minute to translate 200-400 terms. It\'s recommended to maximize PHP max execution time (at least 10min). Plugin automatically end translation when reaching PHP max execution time to avoid error.', 'translator_deepl'); ?></div>
    <div class="mb-line"><?php _e('Plugin use translations cache. If requested term was already translated by DeepL algorithm and is exact match, this translation is used from Database instead, reducing API usage.', 'translator_deepl'); ?></div>
  </div>

  <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
    <?php if(!trd_is_demo()) { ?>
      <input type="hidden" name="page" value="plugins" />
      <input type="hidden" name="action" value="renderplugin" />
      <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
      <input type="hidden" name="plugin_action" value="done" />
    <?php } ?>


    <!-- CONFIGURE SECTION -->
    <div class="mb-box">
      <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'translator_deepl'); ?></div>

      <div class="mb-inside">

        <div class="mb-row">
          <label for="api_key" class=""><span><?php _e('DeepL Api Key', 'translator_deepl'); ?></span></label> 
          <input name="api_key" id="api_key" size="50" type="text" value="<?php echo osc_esc_html($api_key); ?>" <?php if(trd_is_demo()) { ?>disabled<?php } ?>/>
          
          <div class="mb-explain"><?php _e('Your DeepL.com API key.', 'translator_deepl'); ?> <a href="https://www.deepl.com/pro#developer" target="_blank"><?php _e('Get a new API key', 'translator_deepl'); ?></a></div>
        </div>
        
        <div class="mb-row">
          <label for="glossary_id" class=""><span><?php _e('Glossary ID', 'translator_deepl'); ?></span></label> 
          <input name="glossary_id" id="glossary_id" size="30" type="text" value="<?php echo osc_esc_html($glossary_id); ?>" />
          
          <div class="mb-explain"><?php _e('(Optional) Glossary ID of glossary to use for translation (Glossary is created on DeepL.com abd can provide additional context to translations).', 'translator_deepl'); ?></div>
        </div>


        <div class="mb-row">
          <label for="context" class=""><span><?php _e('Context', 'translator_deepl'); ?></span></label> 
          <textarea name="context" id="context"><?php echo osc_esc_html($context); ?></textarea>
          
          <div class="mb-explain"><?php _e('(Optional) Specifies additional context to influence translations, that is not translated itself.', 'translator_deepl'); ?></div>
        </div>


        <div class="mb-row">
          <label for="enable_beta"><span><?php _e('Enable Beta', 'translator_deepl'); ?></span></label> 
          <input name="enable_beta" id="enable_beta" type="checkbox" class="element-slide" <?php echo ($enable_beta == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, beta languages will be supported.', 'translator_deepl'); ?></div>
          </div>
        </div>
        
        
        <hr/>
        

        <div class="mb-row">
          <label for="limit" class=""><span><?php _e('API Monthly Chars Limit', 'translator_deepl'); ?></span></label> 
          <input name="limit" id="limit" size="10" type="number" value="<?php echo osc_esc_html($limit); ?>" />
          <div class="mb-input-desc"><?php _e('characters', 'translator_deepl'); ?></div>
          
          <div class="mb-explain"><?php _e('Your API key monthly characters limit. When reached, plugin will not attempt to do additional translations.', 'translator_deepl'); ?></div>
        </div>

        <div class="mb-row">
          <label for="limit_spent" class=""><span><?php _e('Limit Used', 'translator_deepl'); ?></span></label> 
          <input size="10" type="number" readonly value="<?php echo osc_esc_html(intval(trd_param('limit_spent'))); ?>" />
          <div class="mb-input-desc"><?php _e('characters', 'translator_deepl'); ?></div>
          
          <div class="mb-explain"><?php echo sprintf(__('Usage of your API key in month you have used DeepL last time (%s). Limit is reset every month.', 'translator_deepl'), (trd_param('limit_last_use') <> '' ? trd_param('limit_last_use') : '-')); ?></div>
        </div>


        <hr/>
        
        
        <div class="mb-row">
          <label for="use_cache"><span><?php _e('Use Cache for Translations', 'translator_deepl'); ?></span></label> 
          <input name="use_cache" id="use_cache" type="checkbox" class="element-slide" <?php echo ($use_cache == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, translations from DeepL are stored in database and reused on repetitive translations or translations of other files.', 'translator_deepl'); ?></div>
            <div class="mb-line"><?php _e('Can help to significantly reduce API usage.', 'translator_deepl'); ?></div>
          </div>
        </div>
        
        
        <div class="mb-row">
          <label for="clean_cache"><span>&nbsp;</span></label> 
          <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=translator_deepl/admin/configure.php&what=cleancache" class="mb-button-blue mb-add" onclick="return confirm('Are you sure you want to remove all translations in plugin cache? Action cannot be undone');">
            <i class="fa fa-trash"></i> <?php _e('Clean translations cache', 'translator_deepl'); ?>
          </a>

          <div class="mb-explain">
            <div class="mb-line"><?php echo sprintf(__('Your cache now contains %d translation(s) in %d language(s).', 'translator_deepl'), $count_translations, $count_languages); ?></div>
          </div>
        </div>
        
        
        <hr/>
        
        
        <div class="mb-row">
          <label for="override_translation"><span><?php _e('Override Existing Translations', 'translator_deepl'); ?></span></label> 
          <input name="override_translation" id="override_translation" type="checkbox" class="element-slide" <?php echo ($override_translation == 1 ? 'checked' : ''); ?>/>
          
          <div class="mb-explain">
            <div class="mb-line"><?php _e('When enabled, existing translations will be overwritten by DeepL translations.', 'translator_deepl'); ?></div>
            <div class="mb-line"><?php _e('Can significantly increase API usage.', 'translator_deepl'); ?></div>
            <div class="mb-line"><?php _e('For large translation catalogs (core, osclass pay plugin, ...) it may not be possible to translate whole catalog on 1 run due to max execution time. If this is enabled, each repetitive translation starts from begin of catalog!', 'translator_deepl'); ?></div>
          </div>
        </div>
        
        
        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(trd_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'translator_deepl')); ?>"><?php _e('Save', 'translator_deepl');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'translator_deepl');?></button>
          <?php } ?>
        </div>
      </div>
    </div>
  </form>


</div>


<?php echo trd_footer(); ?>