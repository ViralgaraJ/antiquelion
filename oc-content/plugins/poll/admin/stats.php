<?php
  // Create menu
  $title = __('Configure', 'attributes');
  pol_menu($title);


  // GET & UPDATE PARAMETERS
  // $variable = mb_param_update( 'param_name', 'form_name', 'input_type', 'plugin_var_name' );
  // input_type: check or value

  $polls = ModelPOL::newInstance()->getPolls();
  $active_id = ModelPOL::newInstance()->getActivePollId();

  if(Params::getParam('poll_id') > 0) {
    $active_id = Params::getParam('poll_id');
  }

  if($active_id > 0) {
    $result = pol_get_results($active_id); 
    $total_votes = ($result['votes'] <= 0 ? 1 : $result['votes']);
    $values = $result['values'];
  }
?>



<div class="mb-body">

  <div class="mb-message-js"></div>


  <!-- STATS SECTION -->
  <div class="mb-box">
    <div class="mb-head"><i class="fa fa-bar-chart"></i> <?php _e('Statistics', 'poll'); ?></div>

    <div class="mb-inside mb-stats">
      <div class="mb-row mb-sel-pol">
        <label for="poll_id"><span><?php _e('Select Poll', 'poll'); ?></span></label> 
        <select name="poll_id" id="poll_id" class="pol-stat-id" rel="<?php echo osc_admin_base_url(true); ?>?page=plugins&action=renderplugin&file=poll/admin/stats.php">
          <?php if(count($polls) > 0) { ?>
            <?php foreach($polls as $p) { ?>
              <option value="<?php echo $p['pk_i_id']; ?>" <?php if($p['pk_i_id'] == $active_id) { ?>selected="selected"<?php } ?>><?php echo ($p['s_name'] <> '' ? $p['s_name'] : (__('New poll', 'poll')) . ' #' . $p['pk_i_id']); ?></option>
            <?php } ?>
          <?php } ?>
        </select>
      </div>

      <div class="mb-row">
        <?php if($active_id > 0) { ?>

          <div class="mb-question"><strong><?php _e('Question:', 'poll'); ?></strong><span><?php echo $result['s_description']; ?></span></div>


          <div class="mb-results">
            <?php if(count($values) > 0) { ?>
              <?php foreach($values as $v) { ?>
                <?php 
                  $perc = round($v['votes']/$total_votes*100, 2); 
                  $tp = round($v['votes']/$total_votes/2*10, 0); 
                ?>

                <div class="mb-res">
                  <strong title="<?php echo osc_esc_html($v['s_description']); ?>" class="mb-has-tooltip-user"><?php echo $v['s_name']; ?></strong>
                  <div class="mb-bar-wrap">
                    <div class="mb-bar mb-bar-t-<?php echo $tp; ?>" style="width:<?php echo $perc; ?>%;"><span><?php echo $v['votes']; ?></span></div>
                  </div>
                </div>
              <?php } ?>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>



</div>


<?php echo pol_footer(); ?>