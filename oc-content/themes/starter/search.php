<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="body-search">
<?php osc_current_web_theme_path('header.php') ; ?>

<div class="content list">
  <div id="main" class="search">

    <!-- TOP SEARCH TITLE -->
    <?php
      $search_cat_id = osc_search_category_id();
      $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : '';

      $def_view = osc_get_preference('def_view', 'starter_theme') == 0 ? 'gallery' : 'list';
      $show = Params::getParam('sShowAs') == '' ? $def_view : Params::getParam('sShowAs');
    ?>



    <!-- HELPERS FORS AJAX SEARCH -->
    <div id="ajax-help" style="display:none;">
      <input type="hidden" name="ajax-last-page-id" value="<?php echo ceil( osc_search_total_items() / osc_default_results_per_page_at_search() ); ?>" />

      <?php
        $search_cat_id = osc_search_category_id();
        $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : '';
      ?>
    </div>

    <div id="search-items" data-loading="<?php _e('Loading listings...', 'starter'); ?>">
      <?php osc_run_hook('search_items_top'); ?>
      <?php osc_run_hook('search_items_filter'); ?>
      
      <?php if(osc_count_items() == 0) { ?>
        <div class="list-empty round3" >
          <img src="<?php echo osc_current_web_theme_url('images/search-empty.png'); ?>"/>
          <div>
            <span><?php _e('Whooops, no listing match search criteria...', 'starter'); ?></span>
          </div>
        </div>
      <?php } else { ?>
        <?php echo starter_banner('search_top'); ?>

        <div class="white <?php echo $show; ?>">
          <?php require('search_gallery.php') ; ?>
        </div>
      <?php } ?>
      
      <?php osc_run_hook('search_items_bottom'); ?>

      <div class="paginate">
        <?php echo osc_search_pagination(); ?>
      </div>

      <?php echo starter_banner('search_bottom'); ?>
    </div>
  </div>



  <div id="sidebar" class="noselect">
    <div id="sidebar-search">
      <div class="side-cat">
        <?php
          $search_params = starter_search_params();
          $category = Category::newInstance()->findByPrimaryKey($search_cat_id);

          if($search_cat_id <= 0) {
            $parent = false;
            $categories = Category::newInstance()->findRootCategoriesEnabled();
            $children = false;
          } else {
            $parent = Category::newInstance()->findByPrimaryKey($search_cat_id);
            $categories = Category::newInstance()->findSubcategoriesEnabled($search_cat_id);

            if(count($categories) <= 0) {
              $parent = Category::newInstance()->findByPrimaryKey($parent['fk_i_parent_id']);
             // $categories = Category::newInstance()->findSubcategoriesEnabled($parent['pk_i_id']);
            }
          }          
        ?>


        <h3>
          <span><?php _e('Categories', 'starter'); ?></span>

          <?php if($search_cat_id > 0) { ?>
            <?php $search_params['sCategory'] = @$parent['fk_i_parent_id']; ?>
            <a href="<?php echo osc_search_url($search_params); ?>" class="gotop"><?php _e('parent', 'starter'); ?></a>
          <?php } ?>
        </h3>

        <div class="inside<?php if($search_cat_id <= 0) { ?> root<?php } ?>">
          <?php if($parent) { ?>
            <?php $search_params['sCategory'] = $parent['pk_i_id']; ?>
            <a href="<?php echo osc_search_url($search_params); ?>" class="parent">
              <i class="fa <?php echo starter_get_cat_icon( $parent['pk_i_id'], true ); ?>"></i>
              <span class="name"><?php echo $parent['s_name']; ?></span>
            </a>
          <?php } ?>

          <?php foreach($categories as $c) { ?>
            <?php $search_params['sCategory'] = $c['pk_i_id']; ?>

            <a href="<?php echo osc_search_url($search_params); ?>" class="child<?php if($c['pk_i_id'] == $search_cat_id) { ?> active<?php } ?>">
              <i class="fa <?php echo starter_get_cat_icon( $c['pk_i_id'], true ); ?>"></i>
              <span class="name"><?php echo $c['s_name']; ?></span>
            </a>

          <?php } ?>

        </div>
      </div> 

      <form action="<?php echo osc_base_url(true); ?>" method="get" onsubmit="" class="search-side-form nocsrf">
        <input type="hidden" name="page" value="search" />
        <input type="hidden" name="ajaxRun" value="" />
        <input type="hidden" name="cookieAction" id="cookieAction" value="" />
        <input type="hidden" name="sCategory" value="<?php echo osc_esc_html(Params::getParam('sCategory')); ?>" />
        <input type="hidden" name="sOrder" value="<?php echo osc_search_order(); ?>" />
        <input type="hidden" name="iOrderType" value="<?php $allowedTypesForSorting = Search::getAllowedTypesForSorting() ; echo isset($allowedTypesForSorting[osc_search_order_type()]) ? $allowedTypesForSorting[osc_search_order_type()] : ''; ?>" />
        <input type="hidden" name="sCompany" class="sCompany" id="sCompany" value="<?php echo osc_esc_html(Params::getParam('sCompany')); ?>" />
        <input type="hidden" name="sCountry" id="sCountry" value="<?php echo osc_esc_html(Params::getParam('sCountry')); ?>"/>
        <input type="hidden" name="sRegion" id="sRegion" value="<?php echo osc_esc_html(Params::getParam('sRegion')); ?>"/>
        <input type="hidden" name="sCity" id="sCity" value="<?php echo osc_esc_html(Params::getParam('sCity')); ?>"/>
        <input type="hidden" name="iPage" id="iPage" value=""/>
        <input type="hidden" name="sShowAs" id="sShowAs" value="<?php echo $show; ?>"/>

        <?php foreach(osc_search_user() as $userId) { ?>
          <input type="hidden" name="sUser[]" value="<?php echo $userId; ?>" />
        <?php } ?>


        <div class="wrap">
          <?php osc_run_hook('search_sidebar_pre'); ?>
          
          <h3 class="head">
            <span><?php _e('Search', 'starter'); ?></span>
            <a href="#" class="show-advanced"><?php _e('advanced', 'starter'); ?></a>
          </h3>

          <div class="search-wrap">
            <?php osc_run_hook('search_sidebar_top'); ?>
            
            <fieldset class="box location">
              <div class="row">
                <h4><?php _e('Keyword', 'starter') ; ?></h4>                            
                <div class="input-box">
                  <i class="fa fa-pencil"></i>
                  <input type="text" name="sPattern" id="query" value="<?php echo osc_esc_html(osc_search_pattern()); ?>" placeholder="<?php echo osc_esc_html(__('Search...', 'starter')); ?>" />
                </div>
              </div>

              <div class="row">
                <h4><?php _e('Location', 'starter') ; ?></h4>                            

                <div class="box">
                  <div class="modern-location-trigger" onclick="openLocationModal()">
                    <?php
                      $term_val = starter_get_term(Params::getParam('term'), Params::getParam('sCountry'), Params::getParam('sRegion'), Params::getParam('sCity'));
                      if (empty($term_val) || $term_val == 'All Sri Lanka') {
                        $term_val = __('Worldwide', 'starter');
                      }
                    ?>
                    <span class="selected-location-text"><?php echo osc_esc_html($term_val); ?></span>
                    <i class="fa fa-caret-down"></i>
                  </div>
                  <input type="hidden" name="term" id="term" value="<?php echo osc_esc_html($term_val); ?>" />
                </div>
              </div>

              <div class="row">
                <h4><?php _e('Transaction', 'starter') ; ?></h4>                            
                <div class="select-box">
                  <?php echo starter_simple_transaction(); ?>
                </div>
              </div>

            </fieldset>

            
            <div class="filters-advanced">
              <fieldset>
                <div class="row">
                  <h4><?php _e('Condition', 'starter') ; ?></h4>                            
                  <div class="select-box">
                    <?php echo starter_simple_condition(); ?>
                  </div>
                </div>

                <div class="row">
                  <h4><?php _e('Period', 'starter') ; ?></h4>                            
                  <div class="select-box">
                    <?php echo starter_simple_period(); ?>
                  </div>
                </div>

              </fieldset>

              <?php if( starter_check_category_price($search_cat_id) ) { ?>
                <fieldset class="price-box">
                  <div class="row price">
                    <h4><?php _e('Price', 'starter'); ?>:</h4>
                    <div class="price-row">
                      <input type="text" id="priceMin" name="sPriceMin" value="<?php echo osc_esc_html(Params::getParam('sPriceMin')); ?>" size="6" maxlength="6" />
                      <div class="price-del"><i class="fa fa-arrows-h"></i></div>
                      <input type="text" id="priceMax" name="sPriceMax" value="<?php echo osc_esc_html(Params::getParam('sPriceMax')); ?>" size="6" maxlength="6" />
                    </div>
                  </div>
                </fieldset>
              <?php } ?>


              <?php if( osc_images_enabled_at_items() ) { ?>
                <fieldset>
                  <div class="row checkboxes">
                    <div class="input-box-check">
                      <input type="checkbox" name="bPic" id="withPicture" value="1" <?php echo (osc_search_has_pic() ? 'checked="checked"' : ''); ?> />
                      <label for="withPicture" class="with-pic-label"><?php _e('Only items with picture', 'starter'); ?></label>
                    </div>
                  </div>
                </fieldset>
              <?php } ?>


              <div class="sidebar-hooks">
                <?php 
                  GLOBAL $search_hooks;

                  ob_start(); // SAVE HTML

                  if(osc_search_category_id()) { 
                    osc_run_hook('search_form', osc_search_category_id());
                  } else { 
                    osc_run_hook('search_form');
                  }

                  //echo $search_hooks;
                  $search_hooks = ob_get_contents();   // CAPTURE HTML OF SIDEBAR HOOKS FOR FOOTER (MOBILE VIEW)
                  ob_end_clean();
                  echo $search_hooks;

                ?>
              </div>

            </div>


            <div class="button-wrap">
              <button type="submit" class="btn btn-primary" id="search-button"><?php _e('Search', 'starter') ; ?></button>
              <a href="<?php echo osc_search_url(array('page' => 'search'));?>" class="clear-search clear-cookie btn btn-secondary"><?php _e('Reset', 'starter'); ?></a>
            </div>
          </div>
        </div>
        
        <?php osc_run_hook('search_sidebar_bottom'); ?>
      </form>

      <?php osc_run_hook('search_sidebar_after'); ?>
      <?php osc_alert_form(); ?>
    </div>

    <div class="clear"></div>


    <?php echo starter_banner('search_sidebar'); ?>

  </div>
</div>

<?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>