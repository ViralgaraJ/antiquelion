<?php


// ADD SUPPORT LINK TO FOOTER
function pol_support_link() {
  $order_id = (int)pol_param('link_remover_order_id');

  if($order_id < 8000 || $order_id > 500000 || pol_param('remove_footer_link') == 0) {
    $session_mark = (isset($_SESSION['mb_support_link']) ? $_SESSION['mb_support_link'] : '');
    
    if($session_mark == '' || $session_mark == 'pol') {
      $_SESSION['mb_support_link'] = 'pol';
      ?>
        <style>
        .mb-support-link {display:inline-block;clear:both;width:100%;font-size:13px;line-height:16px;margin:8px 0;text-align:center;}
        .mb-support-link a {display:inline-block;}
        </style>
        
        <div class="mb-support-link pol-support-link" data-plugin="poll"><a href="https://osclass-classifieds.com/installation">Install Osclass in 2 minutes</a></div>
      <?php
    }
  }
}

osc_add_hook('footer', 'pol_support_link', 8);


// POLL HELPER FUNCTIONS
// POLL NAME
function pol_get_name($poll) {
  if(isset($poll['locales'])) {
    if(pol_field('s_name', $poll['locales']) <> '') {
      return pol_field('s_name', $poll['locales']);
    }
  }

  return (@$poll['s_name'] <> '' ? $poll['s_name'] : __('New poll', 'poll'));
}


// POLL DESCRIPTION
function pol_get_description($poll) {
  if(isset($poll['locales'])) {
    if(pol_field('s_description', $poll['locales']) <> '') {
      return pol_field('s_description', $poll['locales']);
    }
  }

  return (@$poll['s_description'] <> '' ? $poll['s_description'] : '');
}


// POLL SUCCESSFUL MESSAGE
function pol_get_successful($poll) {
  if(isset($poll['locales'])) {
    if(pol_field('s_successful', $poll['locales']) <> '') {
      return pol_field('s_successful', $poll['locales']);
    }
  }

  return (@$poll['s_successful'] <> '' ? $poll['s_successful'] : '');
}


// VALUE NAME
function pol_get_value_name($value) {
  if(isset($value['locales'])) {
    if(pol_field('s_name', $value['locales']) <> '') {
      return pol_field('s_name', $value['locales']);
    }
  }

  return (@$value['s_name'] <> '' ? $value['s_name'] : __('New value', 'poll'));
}


// VALUE DESCRIPTION
function pol_get_value_description($value) {
  if(isset($value['locales'])) {
    if(pol_field('s_description', $value['locales']) <> '') {
      return pol_field('s_description', $value['locales']);
    }
  }

  return (@$value['s_description'] <> '' ? $value['s_description'] : '');
}



// GET CORRECT LOCALE VALUE
function pol_field($column, $data, $locale = '') {
  if ($locale == '') {
    $locale = osc_current_user_locale();
  }
 
  $value = @$data[$locale][$column];

  if($value == '') {
    $value = @$data[osc_language()][$column];

    if($value == '') {
      $aLocales = osc_get_locales();
      foreach($aLocales as $locale) {
        $value = @$data[@$locale['pk_c_code']][$column];
        if($value != '') {
          break;
        }
      }
    }
  }

  return (string) $value;
}


// GET POLL RESULTS
function pol_get_results($poll_id = -1) {
  if($poll_id == -1) {
    $poll_id = ModelPOL::newInstance()->getActivePollId();
  }

  $poll = ModelPOL::newInstance()->getOverallResult($poll_id);

  return $poll;
}


// GET USER VOTES
function pol_get_user_votes() {
  $user_id = osc_logged_user_id();
  $cookie_id = mb_get_cookie('pol_user_id');
  $poll_id = ModelPOL::newInstance()->getActivePollId();

  $values = ModelPOL::newInstance()->getUserVote($poll_id, $user_id, $cookie_id);

  return array_filter(array_unique(array_column($values, 'fk_i_poll_value_id')));
}




// CHECK IF USER VOTED
function pol_user_voted() {
  $user_id = osc_logged_user_id();
  $cookie_id = mb_get_cookie('pol_user_id');
  $poll_id = ModelPOL::newInstance()->getActivePollId();

  $check = ModelPOL::newInstance()->checkUserVote($poll_id, $user_id, $cookie_id);

  return $check;  // true == voted
}



// SHOW POLL
function pol_show() {
  require_once 'form/poll.php';
}

osc_add_hook('footer', 'pol_show');


// UPDATE POLL VALUES POSITION - AJAX
function pol_val_position() {
  $order = json_decode(Params::getParam('list'), true);

  if(is_array($order) && count($order) > 0) {
    $i = 0;
    foreach($order as $o) {
      if($o['c'] > 0) {
        ModelPOL::newInstance()->updatePollValuePosition($o['c'], $i+1);
        $i++;
      }
    }
  }

  exit;
}

osc_add_hook('ajax_admin_pol_val_position', 'pol_val_position');



// REMOVE POLL VALUE - AJAX
function pol_remove_value() {
  $id = Params::getParam('id');

  ModelPOL::newInstance()->removePollValue($id);
  exit;
}

osc_add_hook('ajax_admin_pol_remove_value', 'pol_remove_value');


// ADD POLL VALUE - AJAX
function pol_add_value() {
  $poll_id = Params::getParam('pollId');

  if($poll_id > 0) {
    $value = array();
    $value['pk_i_id'] = ModelPOL::newInstance()->insertPollValue($poll_id);

    echo '<li class="mb-val mb-val-new" id="val_' . $value['pk_i_id'] . '">';
    pol_div_value($value);
    echo '</li>';
  }

  exit;
}

osc_add_hook('ajax_admin_pol_add_value', 'pol_add_value');



// OSCLASS PAGES
function pol_pages() {
  return array(
    array('all', __('All pages', 'poll')),
    array('home', __('Home page', 'poll')),
    array('search', __('Search page', 'poll')),
    array('item', __('Item pages', 'poll')),
    array('user', __('User pages', 'poll')),
    array('custom', __('Custom pages', 'poll'))
  );
}


// OC-ADMIN -> LIST VALUES
function pol_list_values_ol($values) {
 if(count($values) > 0 && is_array($values)) {
    foreach($values as $v) {
      ?>

      <li class="mb-val" id="val_<?php echo $v['pk_i_id']; ?>">
        <?php pol_div_value($v); ?>
      </li>
    <?php
    }
  }
}


// OC-ADMIN -> LIST VALUE
function pol_div_value($value) {
?>
  <div>
    <i class="fa fa-arrows move"></i>
    <input name="val-<?php echo @$value['pk_i_id']; ?>-s_name" type="text" class="val-field val-name" value="<?php echo @$value['s_name']; ?>" placeholder="<?php echo osc_esc_html(__('Name', 'poll')); ?>" />
    <input name="val-<?php echo @$value['pk_i_id']; ?>-s_description" type="text" class="val-field val-description" value="<?php echo @$value['s_description']; ?>" placeholder="<?php echo osc_esc_html(__('Description', 'poll')); ?>" />

    <?php if(pol_is_demo()) { ?>
      <a href="#" class="remove mb-disabled" style="opacity:0.5;cursor:not-allowed;" title="This is demo site, you cannot remove value"><i class="fa fa-times"></i></a>
    <?php } else { ?>
      <a href="#" data-id="<?php echo @$value['pk_i_id']; ?>" class="remove" title="<?php echo osc_esc_html(__('Remove', 'poll')); ?>"><i class="fa fa-times"></i></a>
    <?php } ?>
  </div>
<?php
}



// GET CURRENT OR DEFAULT ADMIN LOCALE
function pol_get_locale() {
  $locales = OSCLocale::newInstance()->listAllEnabled();

  if(Params::getParam('polLocale') <> '') {
    $current = Params::getParam('polLocale');
  } else {
    $current = (osc_current_user_locale() <> '' ? osc_current_user_locale() : osc_current_admin_locale());
    $current_exists = false;

    // check if current locale exist in front-office
    foreach( $locales as $l ) {
      if($current == $l['pk_c_code']) {
        $current_exists = true;
      }
    }

    if( !$current_exists ) {
      $i = 0;
      foreach( $locales as $l ) {
        if( $i==0 ) {
          $current = $l['pk_c_code'];
        }

        $i++;
      }
    }
  }

  return $current;
}


// CREATE LOCALE SELECT BOX
function pol_locale_box( $file ) {
  $html = '';
  $locales = OSCLocale::newInstance()->listAllEnabled();
  $current = pol_get_locale();

  $html .= '<select rel="' . osc_admin_base_url(true) . '?page=plugins&action=renderplugin&file=poll/admin/' . $file . '" class="mb-select mb-select-locale" id="polLocale" name="polLocale">';

  foreach( $locales as $l ) {
    $html .= '<option value="' . $l['pk_c_code'] . '" ' . ($current == $l['pk_c_code'] ? 'selected="selected"' : '') . '>' . $l['s_name'] . '</option>';
  }
 
  $html .= '</select>';
  return $html;
}



// CORE FUNCTIONS
function pol_param($name) {
  return osc_get_preference($name, 'plugin-poll');
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


// GENERATE RANDOM INT
function pol_generate_rand_int($length = 18) {
  $characters = '0123456789';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
}



// CHECK IF RUNNING ON DEMO
function pol_is_demo() {
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

?>