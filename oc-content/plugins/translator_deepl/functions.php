<?php

// Map Osclass language code to DeepL lang code
function trd_osc_to_deepl_lang_code($lang) {
  $lang = str_replace('_', '-', strtoupper((string)$lang));
  $lang_2char = substr(strtoupper((string)$lang), 0, 2);
  
  
  switch ($lang_2char) {
    case 'NO': return 'NB';
    case 'JA': return 'JA';
    default:   return $lang;
  }  
}


// Protect sprintf variables, can be single text or array of strings
function trd_protect_tags($text) {
  if(is_array($text) && count($text) > 0) {
    for($i = 0; $i < count($text); $i++) {
      $text[$i] = trd_protect_tags_text($text[$i]);
    }
  } else {
    $text = trd_protect_tags_text($text);
  }
  
  return $text;
}

// Protect one sprintf variable
function trd_protect_tags_text($text) {
  $text = str_replace('%s', '<pxs/>', $text);
  $text = str_replace('%d', '<pxd/>', $text);
  $text = str_replace('%f', '<pxf/>', $text);
  $text = str_replace('%b', '<pxb/>', $text);
  $text = str_replace('%c', '<pxc/>', $text);
  
  return $text;
}

// Restore sprintf variables, can be single text or array of strings
function trd_restore_tags($text) {
  if(is_array($text) && count($text) > 0) {
    for($i = 0; $i < count($text); $i++) {
      $text[$i] = trd_restore_tags_text($text[$i]);
    }
  } else {
    $text = trd_restore_tags_text($text);
  }
  
  return $text;
}

// Restore one sprintf variables
function trd_restore_tags_text($text) {
  $text = str_replace('<pxs/>', '%s', $text);
  $text = str_replace('<pxd/>', '%d', $text);
  $text = str_replace('<pxf/>', '%f', $text);
  $text = str_replace('<pxb/>', '%b', $text);
  $text = str_replace('<pxc/>', '%c', $text);
  
  return $text;
}


$trd_translate_errors = [];

// Translate string with DeepL API
// lang code as en-US, pt-BR, ...
function trd_translate_with_deepl($translator, $text, $target_lang_code) {
  global $trd_translate_errors;
  $fail_after_errors = 5;
  
  // Avoid failing too many times on same error
  if(!empty($trd_translate_errors)) {
    foreach($trd_translate_errors as $err => $count) {
      if($count >= $fail_after_errors) {
        osc_add_flash_error_message(sprintf(__('Translation failed %d times on same error: "%s". Stopping process.', 'translator_deepl'), $count, $err), 'admin');
        header('Location: ' . osc_admin_base_url(true) . '?page=translations&action=edit&language=' . Params::getParam('language') . '&type=' . Params::getParam('type') . '&section=' . Params::getParam('section') . '&theme=' . Params::getParam('theme') . '&plugin=' . Params::getParam('plugin') . '&octoken=' . osc_csrfguard_generate_token());
        exit;
      }
    }
  }
  
  $protected_text = trd_protect_tags($text);

  $options = array(
    'tag_handling' => 'html',             // html, xml
    'formality' => 'prefer_less',   
    // 'split_sentences' => 'nonewlines',    // off, on, nonewlines
    'max_retries' => 1,                   // default 5
    'timeout' => 3,                       // default 10
    'enable_beta_languages' => 1
  );
  
  if(trim(trd_param('context')) <> '') {
    $options['context'] = trd_param('context');        // Extra context for better translations
  }
  
  if(trd_param('glossary_id') <> '') {
    $options['glossary'] = trd_param('glossary_id');    // Glossary created on DeepL.com to be used when translating
  }

  try {
    $result = $translator->translateText($protected_text, 'en', $target_lang_code, $options);

  } catch (\DeepL\DeepLException $e) {
    osc_add_flash_error_message(sprintf(__('Error while getting translation: %s', 'translator_deepl'), $e->getMessage()), 'admin');
    $trd_translate_errors[$e->getMessage()] = ($trd_translate_errors[$e->getMessage()] ?? 0) + 1;

    return '';

  } catch (\Exception $e) {
    osc_add_flash_error_message(sprintf(__('Error while getting translation: %s', 'translator_deepl'), $e->getMessage()), 'admin');
    $trd_translate_errors[$e->getMessage()] = ($trd_translate_errors[$e->getMessage()] ?? 0) + 1;

    return '';
  }
  

  if(is_array($result) && count($result) > 0) {
    $output = array();
    
    foreach($result as $i => $res) {
      $output[$i] = ($res->text ?? '');
    }

    return $output;
    
  } else if(is_object($result)) {
    return ($result->text ?? '');
  }

  return '';
}


// Helper for batch translation 
function trd_process_po_batch($translator, $batch, $batch_map, $deepl_lang_code, $target_lang_code, &$limit_spent, &$translated_deepl) {
  $translated = trd_translate_with_deepl($translator, $batch, $deepl_lang_code);

  foreach($translated as $i => $text) {
    $limit_spent += mb_strlen($batch[$i]);
    $translated_deepl++;

    $batch_map[$i]->translate($text); // Execute translate on PO object

    ModelTRD::newInstance()->insertTranslation([
      'fk_c_locale_code' => $target_lang_code,
      's_original_text' => $batch[$i],
      's_translated_text'=> $text,
      'dt_create_date' => date('Y-m-d H:i:s')
    ]);
  }
}



// Translate whole PO file - one by one
function trd_translate_po_file($path, $target_lang_code) {
  if(trd_param('api_key') == '') {
    return false;
  }
  
  if(!file_exists($path)) {
    osc_add_flash_error_message(sprintf(__('PO catalog does not exists, create it first! <br/>Path: %s', 'translator_deepl'), $path), 'admin');
    return false;
  }
  
  if(!is_readable($path)) {
    osc_add_flash_error_message(sprintf(__('PO catalog exists, but is not readable (probably permissions problem)! <br/>Path: %s', 'translator_deepl'), $path), 'admin');
    return false;
  }
  
  $start = microtime(true);
  
  // Get rid of deprecated warnings
  $current_error_reporting = error_reporting();
  error_reporting($current_error_reporting & ~E_DEPRECATED);

  require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'src/deepl/vendor/autoload.php';

  $translator = new DeepL\Translator(trd_param('api_key'));
  $loader = new Gettext\Loader\PoLoader();
  $translations = $loader->loadFile($path);

  $deepl_limit_reached = false;
  $time_limit_reached = false;

  $translated_deepl = 0;
  $translated_db = 0;

  $limit_spent = (int)trd_param('limit_spent');
  $limit = (int)trd_param('limit');

  // Check usage stats on DeepL
  try {
    $usage = $translator->getUsage();

  } catch (\DeepL\DeepLException $e) {
    osc_add_flash_error_message(sprintf(__('Error checking usage stats: %s', 'translator_deepl'), $e->getMessage()), 'admin');
    return false;

  } catch (\Exception $e) {
    osc_add_flash_error_message(sprintf(__('Error checking usage stats: %s', 'translator_deepl'), $e->getMessage()), 'admin');
    return false;
  }
  
  if($usage->anyLimitReached()) {
    $deepl_limit_reached = true;
  }

  if($usage->character) {
    osc_set_preference('limit_spent', $usage->character->count, 'plugin-translator_deepl', 'INTEGER');  
    osc_set_preference('limit', $usage->character->limit, 'plugin-translator_deepl', 'INTEGER');  
    
    $limit_spent = $usage->character->count;
    $limit = $usage->character->limit;
  }

  $max_sec = (ini_get('max_execution_time') > 0 ? ini_get('max_execution_time') : 60)-10;

  
  // Check if target language is supported
  $avl_languages = $translator->getTargetLanguages();
  $avl_languages_codes = [];
  $lang_supported = false;
  $deepl_lang_code = '';

  
  if(is_countable($avl_languages) && count($avl_languages) > 0) {
    // Collect list ov languages
    foreach($avl_languages as $avl_lang) {
      $avl_languages_codes[] = $avl_lang->code;
    }
    
    // Try to find 5 letter lang code (ie en_US)
    foreach($avl_languages as $avl_lang) {
      if(strtolower($avl_lang->code) == strtolower(trd_osc_to_deepl_lang_code($target_lang_code))) {
        $lang_supported = true;
        $deepl_lang_code = $avl_lang->code;
        break;
      }
    }
    
    // Try to find 2 letter lang code (ie en)
    if($lang_supported === false) {
      foreach($avl_languages as $avl_lang) {
        if(strtolower(substr($avl_lang->code, 0, 2)) == strtolower(substr(trd_osc_to_deepl_lang_code($target_lang_code), 0, 2))) {
          $lang_supported = true;
          $deepl_lang_code = $avl_lang->code;
          break;
        }
      }
    }
  }
  
  // Language not supported
  if($lang_supported === false && trd_param('enable_beta') != 1) {
    osc_add_flash_error_message(sprintf(__('DeepL does not support your language code %s. List of supported language codes: %s', 'translator_deepl'), $target_lang_code, implode(', ', $avl_languages_codes)), 'admin');
    return false;
  }

  // Language not supported, prepare code for Beta (no way to verify it's correct)
  if($deepl_lang_code == '') {
    $deepl_lang_code = substr(strtoupper(trd_osc_to_deepl_lang_code($target_lang_code)), 0, 2);   // hi_IN --> HI
  }
  
  // Check last time limit was updated
  // if(trd_param('limit_last_use') <> '' &&  date('n',strtotime(trd_param('limit_last_use'))) <> date('n')) {
    // osc_set_preference('limit_spent', 0, 'plugin-translator_deepl', 'INTEGER');  
    // osc_set_preference('limit_last_use', date('Y-m-d H:i:s'), 'plugin-translator_deepl', 'STRING');
    // osc_reset_preference();
  // }


  if(is_countable($translations) && count($translations) > 0) {
    foreach($translations as $translation) {
      $deepl_used = false;
      $original_text = $translation->getOriginal();
      $existing_translation = $translation->getTranslation();

      $db_translation = false;
      
      $translated_text = '';
      
      // Stop if DeepL limit used already. Do not exit to save existing translations.
      if($limit_spent >= $limit) {
        $deepl_limit_reached = true;
        osc_add_flash_error_message(__('Your API translation char limit has been spent, usage of DeepL service has been stopped!', 'translator_deepl'), 'admin');
      }


      // Time limit reached
      if($time_limit_reached) {
        $translation->translate($existing_translation);
        continue;
      }
      
      // Skip if translated already
      if(trd_param('override_translation') != 1 && trim((string)$existing_translation) != '') {
        $translation->translate($existing_translation);
        continue;
      }
      
      if(!empty($original_text)) {
        // Check in database for existing translations
        $db_translation = ModelTRD::newInstance()->getTranslationByStringAndCode($original_text, $target_lang_code);

        if(trd_param('use_cache') == 1 && $db_translation !== false) {
          $translated_text = $db_translation;
        }
        
        // Not found in DB, use DeepL
        if($deepl_limit_reached === false && ($translated_text === false || trim((string)$translated_text) == '')) { 
          $translated_text = trd_translate_with_deepl($translator, $original_text, $deepl_lang_code);
          $deepl_used = true;
        
          $limit_spent = $limit_spent + mb_strlen($original_text);
        }
        

        $translation->translate($translated_text === false ? '' : (string)$translated_text);

        if($translated_text !== false && trim((string)$translated_text) != '') {
          if($deepl_used) {
            if($db_translation === false) {
              ModelTRD::newInstance()->insertTranslation(array(
                'fk_c_locale_code' => $target_lang_code,
                's_original_text' => $original_text,
                's_translated_text' => $translated_text,
                'dt_create_date' => date('Y-m-d H:i:s')
              ));
            }
            
            $translated_deepl++;
            
          } else {
            if(trd_param('use_cache') == 1) {
              $translated_db++;
            }
          }
        }
      }
      
      $time_elapsed_secs = round(microtime(true) - $start, 2);
      
      if($time_limit_reached === false && $time_elapsed_secs > $max_sec) {
        osc_add_flash_warning_message(sprintf(__('Script paused after %d seconds.', 'translator_deepl'), $max_sec), 'admin');
        $time_limit_reached = true;
      }
    }
  }

  osc_set_preference('limit_spent', $limit_spent, 'plugin-translator_deepl', 'INTEGER');  

  
  $po_generator = new Gettext\Generator\PoGenerator();
  $po_generator->generateFile($translations, $path);
  
  $path_mo = substr($path, 0, -3) . '.mo';
  $mo_generator = new Gettext\Generator\MoGenerator();
  $mo_generator->generateFile($translations, $path_mo);
  
  $time_elapsed_secs = round(microtime(true) - $start, 2);

  osc_add_flash_ok_message(sprintf(__('Successfully translated %d terms via DeepL and %d terms using Database translation cache in %s seconds. Your API usage is %d characters out of %d (%s%%).', 'translator_deepl'), $translated_deepl, $translated_db, $time_elapsed_secs, $limit_spent, $limit, round($limit_spent*100/$limit, 2)), 'admin');
  return true;
}


// Translate whole PO file - batch by 50
function trd_translate_po_file_batch($path, $target_lang_code) {
  if(trd_param('api_key') == '') {
    return false;
  }
  
  if(!file_exists($path)) { 
    osc_add_flash_error_message(sprintf(__('PO catalog does not exists, create it first! <br/>Path: %s','translator_deepl'),$path),'admin'); 
    return false; 
  }
  
  if(!is_readable($path)) { 
    osc_add_flash_error_message(sprintf(__('PO catalog exists, but is not readable! <br/>Path: %s','translator_deepl'),$path),'admin'); 
    return false;
  }

  $start = microtime(true);
  $current_error_reporting = error_reporting();
  error_reporting($current_error_reporting & ~E_DEPRECATED);

  require_once osc_plugins_path() . osc_plugin_folder(__FILE__) . 'src/deepl/vendor/autoload.php';

  $translator = new DeepL\Translator(trd_param('api_key'));
  $loader = new Gettext\Loader\PoLoader();
  $translations = $loader->loadFile($path);

  $deepl_limit_reached = false;
  $time_limit_reached  = false;
  $translated_deepl = 0;
  $translated_db = 0;
  $limit_spent = (int)trd_param('limit_spent');
  $limit = (int)trd_param('limit');

  // Usage check
  try { 
    $usage = $translator->getUsage();
    
  } catch(Exception $e) {
    osc_add_flash_error_message(sprintf(__('Error checking usage stats: %s','translator_deepl'), $e->getMessage()), 'admin');
    return false;
  }

  if($usage->anyLimitReached()) {
    $deepl_limit_reached = true;
  }
  
  if($usage->character) {
    osc_set_preference('limit_spent',$usage->character->count,'plugin-translator_deepl','INTEGER');
    osc_set_preference('limit',$usage->character->limit,'plugin-translator_deepl','INTEGER');
    
    $limit_spent = $usage->character->count;
    $limit = $usage->character->limit;
  }

  $max_sec = (ini_get('max_execution_time') > 0 ? ini_get('max_execution_time') : 60) - 10;

  // language resolving identical to your original
  try { 
    $avl_languages = $translator->getTargetLanguages();
    
  } catch(Exception $e) {
    osc_add_flash_error_message(sprintf(__('Error checking getting language list: %s','translator_deepl'), $e->getMessage()), 'admin');
    return false;
  }
  
  $deepl_lang_code = '';
  $found = false;
  $codes = [];

  foreach($avl_languages as $l) {
    $codes[] = $l->code;
  }
  
  foreach($avl_languages as $l) {
    if(strtolower($l->code) == strtolower(trd_osc_to_deepl_lang_code($target_lang_code))) { 
      $found=true; 
      $deepl_lang_code=$l->code; 
      break;
    }
  }
  
  if(!$found) {
    foreach($avl_languages as $l) {
      if(strtolower(substr($l->code,0,2)) == strtolower(substr(trd_osc_to_deepl_lang_code($target_lang_code),0,2))) { 
        $found=true; 
        $deepl_lang_code=$l->code; 
        break;
      }
    }
  }

  if(!$found && trd_param('enable_beta') != 1) {
    osc_add_flash_error_message(sprintf(__('DeepL does not support your language code %s. List: %s','translator_deepl'), $target_lang_code, implode(',',$codes)), 'admin');
    return false;
  }

  if($deepl_lang_code=='') {
    $deepl_lang_code = substr(strtoupper(trd_osc_to_deepl_lang_code($target_lang_code)),0,2);
  }

  // --- BATCHING ---
  $batch = [];
  $batch_map = [];
  $batch_limit = 50;

  foreach($translations as $translation) {
    $original = $translation->getOriginal();
    $existing = $translation->getTranslation();

    if($limit_spent >= $limit) {
      $deepl_limit_reached = true;
      osc_add_flash_error_message(__('Your API translation char limit has been spent!','translator_deepl'), 'admin');
    }

    if($time_limit_reached) {
      $translation->translate($existing);
      continue;
    }

    if(trd_param('override_translation') != 1 && trim((string)$existing) != '') {
      $translation->translate($existing);
      continue;
    }

    if(!empty($original)) {
      $db = ModelTRD::newInstance()->getTranslationByStringAndCode($original, $target_lang_code);

      if(trd_param('use_cache') == 1 && $db !== false) {
        $translation->translate($db);
        $translated_db++;
        continue;
      }

      if(!$deepl_limit_reached) {
        $batch[] = $original;
        $batch_map[] = $translation;  // PO data

        if(count($batch) >= $batch_limit) {
          // $limit_spent and $translated_deepl are updated by function
          trd_process_po_batch($translator, $batch, $batch_map, $deepl_lang_code, $target_lang_code, $limit_spent, $translated_deepl);
          $batch = [];
          $batch_map = [];
        }
      }
    }

    if((microtime(true)-$start) > $max_sec) {
      osc_add_flash_warning_message(sprintf(__('Script paused after %d seconds.','translator_deepl'), $max_sec), 'admin');
      $time_limit_reached = true;
    }
  }

  if(!empty($batch)) {
    // $limit_spent and $translated_deepl are updated by function
    trd_process_po_batch($translator, $batch, $batch_map, $deepl_lang_code, $target_lang_code, $limit_spent, $translated_deepl);
  }

  osc_set_preference('limit_spent', $limit_spent, 'plugin-translator_deepl', 'INTEGER');

  $po_generator = new Gettext\Generator\PoGenerator();
  $po_generator->generateFile($translations, $path);

  $mo_generator = new Gettext\Generator\MoGenerator();
  $mo_generator->generateFile($translations, substr($path,0,-3).'.mo');

  $elapsed = round(microtime(true)-$start,2);
  
  osc_add_flash_ok_message(sprintf(__('Successfully translated %d terms via DeepL and %d via DB in %s seconds. API usage %d/%d (%s%%).','translator_deepl'), $translated_deepl, $translated_db, $elapsed, $limit_spent, $limit, round($limit_spent*100/$limit,2)), 'admin');

  return true;
}


// Catch action to do translation
function trd_execute_translation() {
  if(Params::getParam('trdDoTranslation') == 1) {
    // $src_path = trd_get_path('en_US', Params::getParam('type'), Params::getParam('section'), Params::getParam('plugin'), Params::getParam('theme'));
    $trgt_path = trd_get_path(Params::getParam('language'), Params::getParam('type'), Params::getParam('section'), Params::getParam('plugin'), Params::getParam('theme'));
    $lang_code = Params::getParam('language');
    
    // Translate in batch of 50 or sequentially (slow)
    if(defined('TRD_BATCH_PROCESS') && TRD_BATCH_PROCESS === true) {
      trd_translate_po_file_batch($trgt_path, $lang_code);
    } else {
      trd_translate_po_file($trgt_path, $lang_code);
    }

    // osc_add_flash_ok_message(__('Translation via DeepL Translator is completed', 'translator_deepl'), 'admin');
    header('Location: ' . osc_admin_base_url(true) . '?page=translations&action=edit&language=' . Params::getParam('language') . '&type=' . Params::getParam('type') . '&section=' . Params::getParam('section') . '&theme=' . Params::getParam('theme') . '&plugin=' . Params::getParam('plugin') . '&octoken=' . osc_csrfguard_generate_token());
    exit;
  }
}

osc_add_hook('init_admin', 'trd_execute_translation', 9);


// Add button to backoffice to do translation
function trd_translation_button() {
  //he=translations&action=edit&language=cs_CZ&type=CORE&section=CORE&theme=&plugin=
  $type = Params::getParam('type');
  $section = Params::getParam('section');
  $theme = Params::getParam('theme');
  $plugin = Params::getParam('plugin');
  $lang = Params::getParam('language');
  
  ?>
  <style>
  #translation-actions a.btn.trdBtn {background: #d93838; border-color: #bc2121; color: #fff;margin-top:2px;margin-bottom:12px;}
  #translation-actions a.btn.trdBtn i {border-right-color:#bc2121;}
  #translation-actions a.btn.trdBtn:hover {background: #c22d2d; border-color: #9f1f1f; color: #fff;}
  #translation-actions a.btn.trdBtn i {border-right-color:#9f1f1f;}
  </style>
  <?php

  // $btn  = '<a class="trdBtn btn" href="' . osc_admin_base_url(true) . '?page=translations&action=update_from_source&trdDoTranslation=1&language=' . $lang . '&type=' . $type . '&section=' . $section . '&theme=' . $theme . '&plugin=' . $plugin . '&octoken=' . osc_csrfguard_generate_token() . '" title="' . osc_esc_html(__('Update via DeepL API', 'translator_deepl')) . '">';
  $btn  = '<a class="trdBtn btn" href="' . osc_admin_base_url(true) . '?trdDoTranslation=1&language=' . $lang . '&type=' . $type . '&section=' . $section . '&theme=' . $theme . '&plugin=' . $plugin . '&octoken=' . osc_csrfguard_generate_token() . '" title="' . osc_esc_html(__('Update via DeepL API', 'translator_deepl')) . '">';
  $btn .= '<i class="fa fa-fire"></i> ';
  $btn .= '<strong>' . __('Translate via DeepL Plugin', 'translator_deepl') . '</strong>';
  $btn .= '</a>';
  
  echo $btn; 
}

osc_add_hook('admin_translations_edit_buttons_middle', 'trd_translation_button', 7);



// Get path to translation files (copy of function in oc-admin/translations.php)
function trd_get_path($language, $type, $section = '', $plugin = '', $theme = '') {
  if($type == 'CORE') {
    $path = osc_translations_path() . $language . '/';
    
    if($section == 'CORE') {
      $path .= 'core';
    } else if($section == 'MESSAGES') {
      $path .= 'messages';
    } else if($section == 'THEME') {
      $path .= 'theme'; 
    }
  } else if($type == 'ADMIN') {
    $path = osc_admin_base_path() . 'themes/' . AdminThemes::newInstance()->getCurrentTheme() . '/languages/' . $language . '/messages';
  } else if($type == 'PLUGIN') {
    $path = osc_plugins_path() . $plugin . '/languages/' . $language . '/messages';
  } else if($type == 'THEME') {
    $path = osc_themes_path() . $theme . '/languages/' . $language . '/theme';
  }
  
  $path .= '.po';

  return $path;
}





// CORE FUNCTIONS
function trd_param($name) {
  return osc_get_preference($name, 'plugin-translator_deepl');
}


if(!function_exists('mb_param_update')) {
  function mb_param_update( $param_name, $update_param_name, $type = NULL, $plugin_var_name = NULL ) {
  
  $val = '';
  if( $type == 'check') {

    // Checkbox input
    if( Params::getParam( $param_name ) == 'on' ) {
    $val = 1;
    } else {
    if( Params::getParam( $update_param_name ) == 'done' ) {
      $val = 0;
    } else {
      $val = ( osc_get_preference( $param_name, $plugin_var_name ) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
    }
    }
  } else {

    // Other inputs (text, password, ...)
    if( Params::getParam( $update_param_name ) == 'done' && Params::existParam($param_name)) {
    $val = Params::getParam( $param_name );
    } else {
    $val = ( osc_get_preference( $param_name, $plugin_var_name) != '' ) ? osc_get_preference( $param_name, $plugin_var_name ) : '';
    }
  }


  // If save button was pressed, update param
  if( Params::getParam( $update_param_name ) == 'done' ) {

    if(osc_get_preference( $param_name, $plugin_var_name ) == '') {
    osc_set_preference( $param_name, $val, $plugin_var_name, 'STRING');  
    } else {
    $dao_preference = new Preference();
    $dao_preference->update( array( "s_value" => $val ), array( "s_section" => $plugin_var_name, "s_name" => $param_name ));
    osc_reset_preferences();
    unset($dao_preference);
    }
  }

  return $val;
  }
}


// CHECK IF CACHE ENABLED
function trd_cache_enabled() {
  // Disable in backoffice
  if(defined('OC_ADMIN') && OC_ADMIN) {
  return false;
  }
  
  if(trd_param('enable_cache') == 1) {
  return true;
  }

  return false;
}


// GET LIFETIME OF CACHE
function trd_cache_ttl() {
  return OSC_CACHE_TTL;
}

// CHECK IF RUNNING ON DEMO
function trd_is_demo() {
  if(osc_logged_admin_username() == 'admin') {
  return false;
  } else if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) {
  return true;
  } else {
  return false;
  }
}


if(!function_exists('message_ok')) {
  function message_ok( $text ) {
  $final  = '<div class="flashmessage flashmessage-ok flashmessage-inline">';
  $final .= $text;
  $final .= '</div>';
  echo $final;
  }
}


if(!function_exists('message_error')) {
  function message_error( $text ) {
  $final  = '<div class="flashmessage flashmessage-error flashmessage-inline">';
  $final .= $text;
  $final .= '</div>';
  echo $final;
  }
}


if( !function_exists('osc_is_contact_page') ) {
  function osc_is_contact_page() {
  $location = Rewrite::newInstance()->get_location();
  $section = Rewrite::newInstance()->get_section();
  if( $location == 'contact' ) {
    return true ;
  }

  return false ;
  }
}


// COOKIES WORK
if(!function_exists('mb_set_cookie')) {
  function mb_set_cookie($name, $val) {
  Cookie::newInstance()->set_expires( 86400 * 30 );
  Cookie::newInstance()->push($name, $val);
  Cookie::newInstance()->set();
  }
}


if(!function_exists('mb_get_cookie')) {
  function mb_get_cookie($name) {
  return Cookie::newInstance()->get_value($name);
  }
}

if(!function_exists('mb_drop_cookie')) {
  function mb_drop_cookie($name) {
  Cookie::newInstance()->pop($name);
  }
}



// CATEGORIES WORK
function trd_cat_tree($list = array()) {
  if(!is_array($list) || empty($list)) {
  $list = Category::newInstance()->listAll();
  }

  $array = array();
  //$root = Category::newInstance()->findRootCategoriesEnabled();

  foreach($list as $c) {
  if($c['fk_i_parent_id'] <= 0) {
    $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
    $array[$c['pk_i_id']]['sub'] = trd_cat_sub($list, $c['pk_i_id']);
  }
  }

  return $array;
}

function trd_cat_sub($list, $parent_id) {
  $array = array();
  //$cats = Category::newInstance()->findSubcategories($id);

  if(count($list) > 0) {
  foreach($list as $c) {
    if($c['fk_i_parent_id'] == $parent_id) {
    $array[$c['pk_i_id']] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name']);
    $array[$c['pk_i_id']]['sub'] = trd_cat_sub($list, $c['pk_i_id']);
    }
  }
  }
    
  return $array;
}

function trd_cat_list($selected = array(), $categories = '', $level = 0) {
  if($categories == '' || $level == 0) {
  $categories = trd_cat_tree($categories);
  }

  if($level == 0) {
  echo '<option value="0" ' . (in_array(0, $selected) ? 'selected="selected"' : '') . '>' . __('All categories', 'translator_deepl') . '</option>';
  }
  
  foreach($categories as $c) {
  echo '<option value="' . $c['pk_i_id'] . '" ' . (in_array($c['pk_i_id'], $selected) ? 'selected="selected"' : '') . '>' . str_repeat('-', $level) . ($level > 0 ? ' ' : '') . $c['s_name'] . '</option>';

  if(@count($c['sub']) > 0) {
    trd_cat_list($selected, $c['sub'], $level + 1);
  }
  }
}


function trd_list_values_ol($values) {
 if(count($values) > 0 && is_array($values)) {
  foreach($values as $v) {
    ?>

    <li class="mb-val" id="val_<?php echo $v['pk_i_id']; ?>">
    <?php trd_div_value($v); ?>
    
    <ol>
      <?php 
      if(isset($v['values']) && count($v['values']) > 0) { 
        trd_list_values_ol($v['values']); 
      }
      ?>
    </ol>
    </li>
  <?php
  }
  }
}


// CATEGORIES WORK FLAT
function trd_cat_tree_flat($list = array()) {
  if(!is_array($list) || empty($list)) {
  $list = Category::newInstance()->listAll();
  }

  $array = array();
  $level = 0;
  //$root = Category::newInstance()->findRootCategoriesEnabled();

  foreach($list as $c) {
  if($c['fk_i_parent_id'] <= 0) {
    $array[] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name'], 'i_level' => $level);
    $array = array_merge($array, trd_cat_sub_flat($list, $c['pk_i_id'], $level + 1));
  }
  }

  return $array;
}

function trd_cat_sub_flat($list, $parent_id, $level = 0) {
  $array = array();
  //$cats = Category::newInstance()->findSubcategories($id);

  if(count($list) > 0) {
  foreach($list as $c) {
    if($c['fk_i_parent_id'] == $parent_id) {
    $array[] = array('pk_i_id' => $c['pk_i_id'], 's_name' => $c['s_name'], 'i_level' => $level);
    $array = array_merge($array, trd_cat_sub_flat($list, $c['pk_i_id'], $level + 1));
    }
  }
  }

  return $array;
}



// GENERATE PAGINATION
function trd_admin_paginate($file, $page_id, $per_page, $count_all, $class = '', $params = '') {
  $html = '';
  $page_id = (int)$page_id;
  $page_id = ($page_id <= 0 ? 1 : $page_id);
  $base_link = osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=' . $file . $params;

  if($per_page < $count_all) {
  $html .= '<div id="mb-pagination" class="' . $class . '">';
  $html .= '<div class="mb-pagination-wrap">';
  $html .= '<div>' . __('Page:', 'translator_deepl') . '</div>';

  $pages = ceil($count_all/$per_page); 
  $page_actual = ($page_id == '' ? 1 : $page_id);

  if($pages > 6) {

    // Too many pages to list them all
    if($page_id == 1) { 
    $ids = array(1,2,3, $pages);

    } else if ($page_id > 1 && $page_id < $pages) {
    $ids = array(1,$page_id-1, $page_id, $page_id+1, $pages);

    } else {
    $ids = array(1, $page_id-2, $page_id-1, $page_id);
    }

    $old = -1;
    $ids = array_unique(array_filter($ids));

    foreach($ids as $i) {
    $url = $base_link . '&pageId=' . $i;
    
    if($old <> -1 && $old <> $i - 1) {
      $html .= '<span>&middot;&middot;&middot;</span>';
    }

    $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="mb-active"' : '') . '>' . $i . '</a>';
    $old = $i;
    }

  } else {

    // List all pages
    for ($i = 1; $i <= $pages; $i++) {
    $url = $base_link . '&pageId=' . $i;
    $html .= '<a href="' . $url . '" ' . ($page_actual == $i ? 'class="mb-active"' : '') . '>' . $i . '</a>';
    }
  }

  $html .= '</div>';
  $html .= '</div>';
  }

  return $html;
}


if(!function_exists('mb_generate_rand_int')) {
  function mb_generate_rand_int($length = 18) {
  $characters = '0123456789';
  $charactersLength = strlen($characters);
  $randomString = '';

  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
  }
}


if(!function_exists('mb_generate_rand_string')) {
  function mb_generate_rand_string($length = 10) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';

  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
  }
}


if(!function_exists('osc_get_current_user_locations_native')) {
  function osc_get_current_user_locations_native() {
  return false;
  }
}

if(!function_exists('osc_location_native_name_selector')) {
  function osc_location_native_name_selector($array, $column = 's_name') {
  return @$array[$column];
  }
}

?>