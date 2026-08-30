<?php
  // Create menu
  $title = __('Configure', 'attributes');
  pol_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  $color = mb_param_update('color', 'plugin_action', 'value', 'plugin-poll');
  $open = mb_param_update('open', 'plugin_action', 'check', 'plugin-poll');
  $logged_only = mb_param_update('logged_only', 'plugin_action', 'check', 'plugin-poll');

  $link_remover_order_id = mb_param_update('link_remover_order_id', 'plugin_action', 'value', 'plugin-poll');
  $remove_footer_link = mb_param_update('remove_footer_link', 'plugin_action', 'check', 'plugin-poll');


  // UPDATE SETTINGS
  if(Params::getParam('plugin_action') == 'done') {
    message_ok( __('Settings were successfully saved', 'poll') );
  }


  // UPDATE POLL AND VALUES
  if(Params::getParam('plugin_action') == 'poll') {
    $poll_id = Params::getParam('poll_id');

    if($poll_id > 0) {
      $params = Params::getParamsAsArray();

      ModelPOL::newInstance()->updatePoll($params);
      
      if(Params::getParam('b_active') == 1 || Params::getParam('b_active') == 'on') {
        ModelPOL::newInstance()->disableOtherPolls($poll_id);
      }


      if(count($params) > 0) {
        $updated_ids = array();

        foreach($params as $p => $d) { 
          $value = explode('-', $p);

          if(@$value[0] == 'val' && !in_array(@$value[1], $updated_ids)) {
            $data = array();

            $id = @$value[1];

            $data['pk_i_id'] = $id;
            $data['s_name'] = @$params['val-' . $id . '-s_name'];
            $data['s_description'] = @$params['val-' . $id . '-s_description'];
            $data['fk_c_locale_code'] = $params['fk_c_locale_code'];
            
            ModelPOL::newInstance()->updatePollValue($data);

            $updated_ids[] = $id;
          }
        }
      }

      message_ok( __('Poll successfully updated', 'poll') . ' (' . pol_get_locale() . ')');

      ?>
        <script>
          $(document).ready(function(){ 
            $('.mb-field[data-id="<?php echo $poll_id; ?>"] .mb-top-line').click(); 
            $('html, body').animate({ scrollTop: $('.mb-field[data-id="<?php echo $poll_id; ?>"]').offset().top - 60 }, 0);
          });         
        </script>
      <?php
    }
  }


  // REMOVE POLL
  if(Params::getParam('remove') == '1' && !pol_is_demo()) {
    if(Params::getParam('pollId') > 0) {
      ModelPOL::newInstance()->removePoll(Params::getParam('pollId'));
      message_ok( __('Poll removed successfully', 'poll') );
    }
  }



  // ADD ATTRIBUTE
  if(Params::getParam('new') == '1') {
    ModelPOL::newInstance()->insertPoll();
    message_ok( __('Poll created successfully', 'poll') );
  }

?>



<div class="mb-body">

  <div class="mb-message-js"></div>


  <!-- POLL SECTION -->
  <div class="mb-box">
    <div class="mb-head">
      <i class="fa fa-list"></i> <?php _e('Polls & Feedback forms', 'poll'); ?>
      <?php echo pol_locale_box('configure.php'); ?>
    </div>

    <div class="mb-inside mb-polls">
      <div class="mb-notes">
        <div class="mb-line"><?php _e('Plugin generates backlink link added to footer.', 'poll'); ?></div>
        <div class="mb-line"><?php echo sprintf(__('You can use plugin for free with link in footer, or purchase "%s" for this product and disable link.', 'poll'), '<a target="_blank" href="https://osclasspoint.com/services/customer-services/remove-free-product-footer-link-i221">Link remover service</a>'); ?></div>
        <div class="mb-line" style="font-weight:bold;"><?php _e('Removal of link without link remover service purchased violates product license!', 'poll'); ?></div>
      </div>

      <div class="mb-row">
        <label for="link_remover_order_id" class=""><span><?php _e('OsclassPoint Order Id', 'poll'); ?></span></label> 
        <input name="link_remover_order_id" size="20" type="text" value="<?php echo $link_remover_order_id; ?>" />

        <div class="mb-explain"><?php _e('Enter valid OsclassPoint order ID that contains "Link removal service" product.', 'poll'); ?></div>
      </div>
      
      
      <div class="mb-row <?php if($link_remover_order_id < 8000 || $link_remover_order_id > 500000) { ?>mb-disabled<?php } ?>">
        <label for="remove_footer_link" class=""><span><?php _e('Remove Backlink from Footer', 'poll'); ?></span></label> 
        <input name="remove_footer_link" type="checkbox" class="element-slide" <?php if($link_remover_order_id < 8000 || $link_remover_order_id > 100000) { ?>disabled<?php } ?> <?php echo ($remove_footer_link == 1 ? 'checked' : ''); ?> />

        <div class="mb-explain">
          <div class="mb-link"><?php _e('When checked, footer backlink will be removed from footer. It require valid OsclassPoint order ID.', 'poll'); ?></div>
          <div class="mb-link"><?php _e('This plugin license allows to remove link just in case "Link remover service" was purchased for it.', 'poll'); ?></div>
          <div class="mb-link"><?php _e('By selecting this option to remove the link, you confirm that you have purchased the license removal service and that your saved order includes this service.', 'poll'); ?></div>
        </div>
      </div>
      
      <hr/>
      
      
      <?php $polls = ModelPOL::newInstance()->getPolls(); ?>

      <div id="mb-poll">
        <?php if(count($polls) > 0) { ?>
          <?php foreach($polls as $p) { ?>

            <form name="promo_form" id="pol_<?php echo $p['pk_i_id']; ?>" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
              <input type="hidden" name="page" value="plugins" />
              <input type="hidden" name="action" value="renderplugin" />
              <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
              <input type="hidden" name="plugin_action" value="poll" />
              <input type="hidden" name="poll_id" value="<?php echo $p['pk_i_id']; ?>" />
              <input type="hidden" name="pk_i_id" value="<?php echo $p['pk_i_id']; ?>" />
              <input type="hidden" name="fk_c_locale_code" value="<?php echo pol_get_locale(); ?>" />
              <input type="hidden" name="polLocale" value="<?php echo Params::getParam('polLocale'); ?>" />


              <div class="mb-field" data-id="<?php echo $p['pk_i_id']; ?>">
                <div class="mb-top-line">
                  <span class="name">
                    <?php echo ($p['s_name'] <> '' ? $p['s_name'] : (__('New poll', 'poll')) . ' #' . $p['pk_i_id']); ?>
                    <?php if($p['b_active'] == 1) { ?>
                      <i class="fa fa-check-circle enabled"></i>
                    <?php } else { ?>
                      <i class="fa fa-times-circle disabled"></i>
                    <?php } ?>
                  </span>
                  <span class="type"><?php echo ucfirst($p['s_type']); ?>&nbsp;</span>
                  <span class="count"><?php echo count($p['values']) . ' ' . __('values', 'poll'); ?></span>
                  <span class="show"><i class="fa fa-angle-down"></i></span>
                </div>

                <div class="mb-details">
                  <div class="mb-setup">
                    <div class="mb-line">
                      <label for="pol_id" class="h1"><span class="mb-has-tooltip"><?php _e('Poll Id', 'poll'); ?></span></label>
                      <input name="pol_id" type="text" class="poll-field poll-id" disabled="disabled" value="<?php echo $p['pk_i_id']; ?>" />
                    </div>

                    <div class="mb-line">
                      <label for="b_active" class="h7"><span class="mb-has-tooltip"><?php _e('Active', 'poll'); ?></span></label>
                      <input name="b_active" type="checkbox" class="element-slide poll-field poll-required" <?php echo ($p['b_active'] == 1 ? 'checked' : ''); ?> />
                    </div>

                    <div class="mb-line">
                      <label for="s_name" class="h2"><span class="mb-has-tooltip"><?php _e('Poll Name', 'poll'); ?></span></label>
                      <input name="s_name" size="40" type="text" class="poll-field poll-name" value="<?php echo $p['s_name']; ?>" />
                    </div>

                    <div class="mb-line">
                      <label for="s_description" class="h3"><span class="mb-has-tooltip"><?php _e('Description', 'poll'); ?></span></label>
                      <textarea name="s_description" type="text" class="poll-field poll-description"><?php echo $p['s_description']; ?></textarea>
                    </div>

                    <div class="mb-line">
                      <label for="s_successful" class="h4"><span class="mb-has-tooltip"><?php _e('Successful message', 'poll'); ?></span></label>
                      <textarea name="s_successful" type="text" class="poll-field poll-successful"><?php echo $p['s_successful']; ?></textarea>
                    </div>

                    <div class="mb-line">
                      <label for="s_type" class="h5"><span class="mb-has-tooltip"><?php _e('Type', 'poll'); ?></span></label>
                      <select name="s_type" class="pol-field pol-type">
                        <option value="RADIO-VERTICAL" <?php echo ($p['s_type'] == 'RADIO-VERTICAL' ? 'selected="selected"' : ''); ?>><?php _e('Radio Vertical', 'poll'); ?></option>
                        <option value="RADIO-HORIZONTAL" <?php echo ($p['s_type'] == 'RADIO-HORIZONTAL' ? 'selected="selected"' : ''); ?>><?php _e('Radio Horizontal', 'poll'); ?></option>
                        <option value="CHECKBOX" <?php echo ($p['s_type'] == 'CHECKBOX' ? 'selected="selected"' : ''); ?>><?php _e('Checkbox', 'poll'); ?></option>
                        <option value="STAR" <?php echo ($p['s_type'] == 'STAR' ? 'selected="selected"' : ''); ?>><?php _e('Stars', 'poll'); ?></option>
                      </select>
                    </div>


                    <div class="mb-line mb-row-select-multiple">
                      <label for="pages_multiple" class="h6"><span class="mb-has-tooltip"><?php _e('Allowed Pages', 'poll'); ?></span></label> 

                      <input type="hidden" name="s_pages" id="s_pages" value="<?php echo $p['s_pages']; ?>"/>
                      <select id="pages_multiple" name="pages_multiple" multiple>
                        <?php foreach(pol_pages() as $pp) { ?>
                          <option value="<?php echo $pp[0]; ?>" <?php if(in_array($pp[0], explode(',', $p['s_pages']))) { ?>selected="selected"<?php } ?>><?php echo $pp[1]; ?></option>
                        <?php } ?>
                      </select>

                      <div class="mb-explain"><?php _e('If not page is selected, poll will be shown on all pages.', 'poll'); ?></div>
                    </div>

                    <div class="mb-line">
                      <label for="s_position" class="h6"><span class="mb-has-tooltip"><?php _e('Position', 'poll'); ?></span></label>
                      <select name="s_position" class="poll-field poll-type">
                        <option value="TOP-LEFT" <?php echo ($p['s_position'] == 'TOP-LEFT' ? 'selected="selected"' : ''); ?>><?php _e('Top Left', 'poll'); ?></option>
                        <option value="TOP-RIGHT" <?php echo ($p['s_position'] == 'TOP-RIGHT' ? 'selected="selected"' : ''); ?>><?php _e('Top Right', 'poll'); ?></option>
                        <option value="BOTTOM-LEFT" <?php echo ($p['s_position'] == 'BOTTOM-LEFT' ? 'selected="selected"' : ''); ?>><?php _e('Bottom Left', 'poll'); ?></option>
                        <option value="BOTTOM-RIGHT" <?php echo ($p['s_position'] == 'BOTTOM-RIGHT' ? 'selected="selected"' : ''); ?>><?php _e('Bottom Right', 'poll'); ?></option>
                      </select>
                    </div>
                  </div>

                  
                  <div class="mb-values">
                    <div class="mb-val-title"><?php _e('Poll values', 'poll'); ?></div>
                    <div class="mb-val-empty" <?php if(count($p['values']) > 0) { ?>style="display:none;"<?php } ?>><?php _e('No values added yet', 'poll'); ?></div>

                    <ol class="sortable">
                      <?php pol_list_values_ol($p['values']); ?>
                    </ol>

                    <div class="mb-val-footer">
                      <a href="#" class="add" data-poll-id="<?php echo $p['pk_i_id']; ?>"><i class="fa fa-plus-circle"></i><?php _e('Add new value', 'poll'); ?></a>
                    </div>
                  </div>
                </div>

                <div class="mb-foot">
                  <?php if(pol_is_demo()) { ?>
                    <a href="#" class="mb-button remove mb-disabled" onclick="return false;" disabled style="opacity:0.5;cursor:not-allowed" title="This is demo site, you cannot remove poll"><?php _e('Delete poll', 'poll');?></a>
                  <?php } else { ?>
                    <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=poll/admin/configure.php&remove=1&pollId=<?php echo $p['pk_i_id']; ?>" class="mb-button remove" onclick="return confirm('<?php echo osc_esc_js(__('Are you sure you want to remove this poll? Action cannot be undone', 'poll')); ?>')"><?php _e('Delete poll', 'poll');?></a>
                  <?php } ?>

                  <button type="submit" class="mb-button"><?php _e('Update poll', 'poll');?></button>
                </div>
              </div>
            </form>

          <?php } ?>
        <?php } else { ?>
          <div class="mb-no-poll"><?php _e('You have not created any poll yet', 'poll'); ?></div>
        <?php } ?>

        <a href="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=poll/admin/configure.php&new=1" class="mb-add-poll"><i class="fa fa-plus-circle"></i><?php _e('Add new poll', 'poll'); ?></a>

      </div>
    </div>
  </div>



  <!-- CONFIGURE SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Configure', 'poll'); ?></div>

    <div class="mb-inside mb-minify">
      <form name="promo_form" action="<?php echo osc_admin_base_url(true); ?>" method="POST" enctype="multipart/form-data" >
        <input type="hidden" name="page" value="plugins" />
        <input type="hidden" name="action" value="renderplugin" />
        <input type="hidden" name="file" value="<?php echo osc_plugin_folder(__FILE__); ?>configure.php" />
        <input type="hidden" name="plugin_action" value="done" />

        <div class="mb-row">
          <label for="open" class="h7"><span><?php _e('Open Poll by Default', 'poll'); ?></span></label> 
          <input name="open" id="open" type="checkbox" class="element-slide" <?php echo ($open == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, poll will be opened when user land on your page. Note that if user close poll, it will not be automatically opened next time for this user.', 'poll'); ?></div>
        </div>

        <div class="mb-row">
          <label for="logged_only" class="h8"><span><?php _e('Only Logged Users', 'poll'); ?></span></label> 
          <input name="logged_only" id="logged_only" type="checkbox" class="element-slide" <?php echo ($logged_only == 1 ? 'checked' : ''); ?> />
          
          <div class="mb-explain"><?php _e('When enabled, only logged-in users will see poll', 'poll'); ?></div>
        </div>

        <div class="mb-row">
          <label for="color" class="h9"><span><?php _e('Label color', 'poll'); ?></span></label> 
          <input name="color" id="color" type="text" class="" value="<?php echo $color; ?>" placeholder="#111111" />
          
          <div class="mb-explain"><?php _e('Enter HEX code of color that will be used on label in front', 'poll'); ?></div>
        </div>


        <div class="mb-row">&nbsp;</div>

        <div class="mb-foot">
          <?php if(pol_is_demo()) { ?>
            <a class="mb-button mb-has-tooltip disabled" onclick="return false;" style="cursor:not-allowed;opacity:0.5;" title="<?php echo osc_esc_html(__('This is demo site', 'poll')); ?>"><?php _e('Save', 'poll');?></a>
          <?php } else { ?>
            <button type="submit" class="mb-button"><?php _e('Save', 'poll');?></button>
          <?php } ?>
        </div>
      </form>
    </div>
  </div>



  <!-- PLUGIN INTEGRATION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-wrench"></i> <?php _e('Plugin Setup', 'poll'); ?></div>

    <div class="mb-inside">

      <div class="mb-row"><?php _e('No theme modification are required to use all functions of plugin.', 'poll'); ?></div>
    </div>
  </div>


</div>


<script type="text/javascript">
  var pol_remove_value_url = "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=runhook&hook=pol_remove_value&id=";
  var pol_add_value_url = "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=runhook&hook=pol_add_value&pollId=";
  var pol_val_position_url = "<?php echo osc_admin_base_url(true); ?>?page=ajax&action=runhook&hook=pol_val_position";


  var pol_message_ok = "<?php echo osc_esc_html(__('Success!', 'poll')); ?>";
  var pol_message_wait = "<?php echo osc_esc_html(__('Updating, please wait...', 'poll')); ?>";
  var pol_message_error = "<?php echo osc_esc_html(__('Error!', 'poll')); ?>";


  var val_list = '';

  // SORTABLE VALUES
  $(document).ready(function(){
    $('ol.sortable').nestedSortable({
      forcePlaceholderSize: true,
      handle: 'div',
      helper: 'clone',
      items: 'li',
      opacity: .8,
      placeholder: 'placeholder',
      revert: 100,
      tabSize: 5,
      tolerance: 'intersect',
      toleranceElement: '> div',
      maxLevels: 8,
      isTree: true,
      startCollapsed: false,
      start: function(event, ui) {
        val_list = $(this).nestedSortable('serialize');
        ui.placeholder.height(ui.item.find('>div').innerHeight() - 2);
      },
      stop: function (event, ui) {
        var c_val_list = $(this).nestedSortable('serialize');
        var c_array_list = $(this).nestedSortable('toArray');

        var c_array_list = c_array_list.reduce(function(total, current, index) {
          total[index] = {'c' : current.item_id};
          return total;
        }, {});


        pol_message(pol_message_wait, 'info');

        if(val_list != c_val_list) {
          $.ajax({
            url: pol_val_position_url,
            type: "POST",
            data: {'list' : JSON.stringify(c_array_list)},
            success: function(response){
              //console.log(response);
              pol_message(pol_message_ok, 'ok');
            },
            error: function(response) {
              pol_message(pol_message_error, 'error');
              console.log(response);
            }
          });
        }
      }
    });

  });
</script>


<?php echo pol_footer(); ?>