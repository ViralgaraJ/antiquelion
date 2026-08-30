<?php
  $poll = ModelPOL::newInstance()->getActivePoll();
  $open = pol_param('open');

  if(isset($poll['s_pages'])) {
    $pages = explode(',', $poll['s_pages']);
  } else {
    $pages = array();
  }

  $location = Rewrite::newInstance()->get_location();


  if(mb_get_cookie('pol_close') == 2) {
    $open = 1;
  }

  if(pol_user_voted() || mb_get_cookie('pol_close') == 1) {
    $open = 0;
  }
?>

<?php if(pol_param('logged_only') == 1 && osc_is_web_user_logged_in() || pol_param('logged_only') == 0) { ?>
  <?php if(isset($poll['pk_i_id']) && $poll['b_active'] == 1 && count($poll['values']) > 0) { ?>
    <?php if(in_array($location, $pages) || in_array('all', $pages) || empty($pages)) { ?>
      <?php $position = explode('-', $poll['s_position']); ?>

      <div class="pol-body pol-ps-v-<?php echo strtolower($position[0]); ?> pol-ps-h-<?php echo strtolower($position[1]); ?>">
        <div class="pol-header" <?php if(pol_param('color') <> '') { ?>style="background:<?php echo pol_param('color'); ?>;"<?php } ?>>
          <span><?php _e('Survey', 'poll'); ?></span>
        </div>

        <div class="pol-inside" style="<?php if($open == 1) { ?>display:block;<?php } else { ?>display:none;<?php } ?>">
          <?php if(!pol_user_voted()) { ?>
            <div class="pol-wrap">
              <div class="pol-close"><img src="<?php echo osc_base_url(); ?>oc-content/plugins/poll/img/close.png"/></div>
              <div class="pol-name"><?php echo pol_get_name($poll); ?></div>
              <div class="pol-desc"><?php echo pol_get_description($poll); ?></div>

              <?php 
                if($poll['s_type'] == 'RADIO-HORIZONTAL') {
                  $type = 'radio';
                  $name = 'radio';
                  $subtype = 'horizontal';
                } else if($poll['s_type'] == 'RADIO-VERTICAL') {
                  $type = 'radio';
                  $name = 'radio';
                  $subtype = 'vertical';
                } else if($poll['s_type'] == 'CHECKBOX') {
                  $type = 'checkbox';
                  $name = 'checkbox';
                  $subtype = 'vertical';
                } else if($poll['s_type'] == 'STAR') {
                  $type = 'radio';
                  $name = 'star';
                  $subtype = 'vertical';
                } 
              ?>

              <div class="pol-values pol-t-<?php echo $type; ?> pol-st-<?php echo $subtype; ?> pol-nm-<?php echo $name; ?> pol-c-<?php echo count($poll['values']); ?>">
                <form id="pol-form" name="pol-form" method="POST" action="<?php echo osc_base_url(); ?>">
                  <input type="hidden" name="pollSubmit" value="1" />
                  <input type="hidden" name="pollId" value="<?php echo $poll['pk_i_id']; ?>" />
                  <input type="hidden" name="userId" value="<?php echo osc_logged_user_id(); ?>" />
                  <input type="hidden" name="cookieId" value="<?php echo mb_get_cookie('pol_user_id'); ?>" />

                  <div class="pol-values-box">
                    <?php $i = 1; ?>
                    <?php foreach($poll['values'] as $v) { ?>
                      <div class="pol-value pol-input-box pol-i-<?php echo $i; ?> pol-has-tooltip" title="<?php echo osc_esc_html(($name == 'star' ? pol_get_value_name($v) . (pol_get_value_description($v) <> '' ? ': ' : '') : '')  . pol_get_value_description($v)); ?>" <?php if($subtype == 'horizontal' || $name == 'star') {?>style="width:<?php echo (round(100/count($poll['values']), 2)-0.1); ?>%"<?php } ?>>
                        <input type="<?php echo $type; ?>" name="pol-val_<?php echo $v['pk_i_id']; ?>" id="pol-val_<?php echo $v['pk_i_id']; ?>" value="1" />
                        <label for="pol-val_<?php echo $v['pk_i_id']; ?>"><span><?php echo pol_get_value_name($v); ?></span></label>
                      </div>

                      <?php $i++; ?>
                    <?php } ?>
                  </div>

                  <div class="pol-buttons">
                    <button class="pol-btn" type="submit" disabled><?php _e('Submit', 'poll'); ?></button>
                    <div class="pol-empty"><?php _e('You must select value', 'poll'); ?></div>
                  </div>

                  <?php if(isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'],'mb-themes') !== false || strpos($_SERVER['HTTP_HOST'],'abprofitrade') !== false)) { ?>
                    <div class="pol-ref">
                      <a href="https://osclasspoint.com/osclass-plugins/rating-and-review/poll-survey-and-feedback-plugin_i90">Osclass Poll Plugin</a>
                    </div>
                  <?php } ?>
                </form>
              </div>
            </div>

            <div class="pol-success">
              <div class="pol-close"><img src="<?php echo osc_base_url(); ?>oc-content/plugins/poll/img/close.png"/></div>

              <div class="pol-icon">
                <img src="<?php echo osc_base_url(); ?>oc-content/plugins/poll/img/success.gif"/>
              </div>

              <div class="pol-msg">
                <?php echo (pol_get_successful($poll) <> '' ? pol_get_successful($poll) : __('Thanks for your vote!', 'poll')); ?>
              </div>
            </div>
   
          <?php } else { ?>
            <div class="pol-voted">
              <?php
                $result = pol_get_results(); 
                $total_votes = $result['votes'];
                $values = $result['values'];

                $my_votes = pol_get_user_votes();
              ?>

              <div class="pol-close"><img src="<?php echo osc_base_url(); ?>oc-content/plugins/poll/img/close.png"/></div>
              <div class="pol-name"><?php _e('Results', 'poll'); ?></div>
              <div class="pol-desc"><?php echo pol_get_description($result); ?></div>

              <div class="pol-results">
                <?php if(count($values) > 0) { ?>
                  <?php foreach($values as $v) { ?>
                    <?php 
                      $perc = round($v['votes']/$total_votes*100, 2); 
                      $tp = round($v['votes']/$total_votes/2*10, 0); 
                    ?>

                    <div class="pol-res">
                      <strong title="<?php echo osc_esc_html(pol_get_value_description($v)); ?>" class="pol-has-tooltip"><?php echo pol_get_value_name($v); ?></strong>
                      <div class="pol-bar-wrap">
                        <div class="pol-bar pol-bar-t-<?php echo $tp; ?>" style="width:<?php echo $perc; ?>%;"><span><?php echo $v['votes']; ?></span></div>

                        <?php if(in_array($v['pk_i_id'], $my_votes)) { ?>
                          <i class="fa fa-check pol-has-tooltip" title="<?php echo osc_esc_html(__('Your vote', 'poll')); ?>"></i>
                        <?php } ?>
                      </div>
                    </div>
                  <?php } ?>
                <?php } ?>
              </div>
            </div>
          <?php } ?>

        </div>
      </div>
    <?php } ?>
  <?php } ?>
<?php } ?>