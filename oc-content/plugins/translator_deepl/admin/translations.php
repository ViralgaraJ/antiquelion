<?php
  // Create menu
  $title = __('Configure', 'translator_deepl');
  trd_menu($title);

  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value


  $param_string_del = '&original_text=' . Params::getParam('original_text') . '&translated_text=' . Params::getParam('translated_text') . '&date=' . Params::getParam('date') . '&locale=' . Params::getParam('locale') . '&pageId=' . Params::getParam('pageId');

  if((int)Params::getParam('deleteId') > 0) {
    ModelTRD::newInstance()->deleteTranslation((int)Params::getParam('deleteId'));
    osc_add_flash_ok_message(__('Translation cache item removed successfully.', 'translator_deepl'), 'admin');

    header('Location:' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=translator_deepl/admin/translations.php' . $param_string_del);
    exit;
  }
  
  $per_page = 50;
  $locales = OSCLocale::newInstance()->listAll();

  $params = Params::getParamsAsArray();
  $translations = ModelTRD::newInstance()->getTranslations($params);
  $count_all = ModelTRD::newInstance()->getTranslations($params, true);
  
  $count = ModelTRD::newInstance()->countTranslations();
  $count_translations = (int)(isset($count['rec_count']) ? $count['rec_count'] : 0);
  $count_languages = (int)(isset($count['lang_count']) ? $count['lang_count'] : 0);
?>

<div class="mb-body">
  <div class="mb-notes">
    <div class="mb-line"><?php _e('Plugin collects existing translations from DeepL to reduce API usage and enhance translations speed.', 'translator_deepl'); ?></div>
    <div class="mb-line"><?php echo sprintf(__('Your cache now contains %d translation(s) in %d language(s).', 'translator_deepl'), $count_translations, $count_languages); ?></div>
  </div>


  <!-- TRANSLATIONS LIST  SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-language"></i> <?php _e('Translations Cache', 'translator_deepl'); ?></div>

    <div class="mb-inside">

      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=translator_deepl/admin/translations.php" method="POST" enctype="multipart/form-data" >
        <div id="mb-search-table">
          <div class="mb-col-4">
            <label for="locale"><?php _e('Locale', 'translator_deepl'); ?></label>
            <select name="locale">
              <option value="" <?php if(Params::getParam('locale') == "") { ?>selected="selected"<?php } ?>><?php _e('All', 'translator_deepl'); ?></option>

              <?php if(is_array($locales) && count($locales) > 0) { ?>
                <?php foreach($locales as $locale) { ?>
                  <option value="<?php echo $locale['pk_c_code']; ?>" <?php if(Params::getParam('locale') == $locale['pk_c_code']) { ?>selected="selected"<?php } ?>><?php echo $locale['s_name']; ?> (<?php echo $locale['pk_c_code']; ?>)</option>
                <?php } ?>
              <?php } ?>
            </select>
          </div>
          
          <div class="mb-col-8">
            <label for="original_text"><?php _e('Original text contains', 'translator_deepl'); ?></label>
            <input type="text" name="original_text" value="<?php echo osc_esc_html(Params::getParam('original_text')); ?>" />
          </div>
          
          <div class="mb-col-8">
            <label for="translated_text"><?php _e('Translated text contains', 'translator_deepl'); ?></label>
            <input type="text" name="translated_text" value="<?php echo osc_esc_html(Params::getParam('translated_text')); ?>" />
          </div>
          

          <div class="mb-col-3">
            <label for="">&nbsp;</label>
            <button type="submit" class="mb-button mb-button-black"><i class="fa fa-search"></i> <?php _e('Search', 'translator_deepl'); ?></button>
          </div>
        </div>
      </form>
      
      <div class="mb-table mb-table-translations">
        <div class="mb-table-head">
          <div class="mb-col-2 mb-align-left"><?php _e('ID', 'translator_deepl');?></div>
          <div class="mb-col-2 mb-align-left"><?php _e('Locale', 'translator_deepl');?></div>
          <div class="mb-col-7 mb-align-left"><?php _e('Original text', 'translator_deepl');?></div>
          <div class="mb-col-7 mb-align-left"><?php _e('Translated text', 'translator_deepl'); ?></div>
          <div class="mb-col-3"><?php _e('Date', 'translator_deepl'); ?></div>
          <div class="mb-col-3 mb-align-right">&nbsp;</div>
        </div>

        <?php if(count($translations) <= 0) { ?>
          <div class="mb-table-row mb-row-empty">
            <i class="fa fa-warning"></i><span><?php _e('No translations has been found', 'translator_deepl'); ?></span>
          </div>
        <?php } else { ?>
          <?php foreach($translations as $translation) { ?>
            <div class="mb-table-row">
              <div class="mb-col-2 mb-align-left"><?php echo $translation['pk_i_id']; ?></div>
              <div class="mb-col-2 mb-align-left"><strong><?php echo $translation['fk_c_locale_code']; ?></strong></div>
              <div class="mb-col-7 mb-align-left"><?php echo $translation['s_original_text']; ?></div>
              <div class="mb-col-7 mb-align-left"><?php echo $translation['s_translated_text']; ?></div>
              <div class="mb-col-3 mb-date mb-gray"><?php echo $translation['dt_create_date']; ?></div>
              
              <div class="mb-col-3 mb-align-right">
                <a class="mb-tran-remove mb-btn mb-button-red" href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=translator_deepl/admin/translations.php&deleteId=<?php echo $translation['pk_i_id']; ?><?php echo $param_string_del; ?>" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this translation item? Action cannot be undone.', 'translator_deepl')); ?>')"><i class="fa fa-trash"></i> <?php _e('Remove', 'translator_deepl'); ?></a>
              </div>
            </div>
          <?php } ?>
        <?php } ?>

        <?php 
          $param_string = '&original_text=' . Params::getParam('original_text') . '&translated_text=' . Params::getParam('translated_text') . '&date=' . Params::getParam('date') . '&locale=' . Params::getParam('locale');
          echo trd_admin_paginate('translator_deepl/admin/translations.php', Params::getParam('pageId'), $per_page, $count_all, '', $param_string); 
        ?>
        
        <div class="mb-row">&nbsp;</div>
      </div>
    </div>
  </div>


</div>


<?php echo trd_footer(); ?>