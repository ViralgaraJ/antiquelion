<?php $search_params = zara_search_params(); ?>
<?php $search_params['sPriceMin'] = ''; ?>
<?php $search_params['sPriceMax'] = ''; ?>

<?php
  // CURRENT CATEGORY
  $search_cat_id = osc_search_category_id();
  $search_cat_id = isset($search_cat_id[0]) ? $search_cat_id[0] : 0;
  $search_cat_full = Category::newInstance()->findByPrimaryKey($search_cat_id);

  // ROOT CATEGORY
  $root_cat_id = Category::newInstance()->findRootCategory($search_cat_id);
  $root_cat_id = (isset($root_cat_id['pk_i_id']) ? $root_cat_id['pk_i_id'] : null);
   
  // HIERARCHY OF SEARCH CATEGORY
  $hierarchy = Category::newInstance()->toRootTree($search_cat_id);

  // SUBCATEGORIES OF SEARCH CATEGORY
  $subcats = Category::newInstance()->findSubcategories($search_cat_id);

  if(empty($subcats)) {
    $is_subcat = false;
    $subcats = Category::newInstance()->findSubcategories(isset($search_cat_full['fk_i_parent_id']) ? $search_cat_full['fk_i_parent_id'] : null);
  } else {
    $is_subcat = true;
  }
?>

<div class="top-cat-head sc-click resp is767<?php if(osc_is_home_page()) { ?> home<?php } ?>"><?php _e('Categories', 'zara'); ?></div>
<div class="top-cat-wrap sc-block<?php if(osc_is_home_page()) { ?> home<?php } ?>">
  <div id="top-cat">
    <div class="cat-inside">
      <h1><?php _e('Browse Categories', 'zara'); ?></h1>
      <div class="top-cat-ul-wrap">
        <div class="left-arrow arrows tr1 noselect"><i class="fa fa-angle-left tr1"></i></div>

        <div class="ul-box">
          <ul <?php if(osc_is_search_page()) { ?>class="ul-search"<?php } ?> style="width:<?php echo osc_count_categories()*130; ?>px">
            <?php $i = 1; ?>
            <?php $category_icons = array(1 => 'fa-gavel', 2 => 'fa-car', 3 => 'fa-book', 4 => 'fa-home', 5 => 'fa-wrench', 6 => 'fa-music', 7 => 'fa-heart', 8 => 'fa-briefcase', 999 => 'fa-soccer-ball-o'); ?>

            <?php while ( osc_has_categories() ) { ?>
              <?php $search_params['sCategory'] = osc_category_id(); ?>
              <?php 
                if($root_cat_id <> '' and $root_cat_id <> 0) {
                  if($root_cat_id <> osc_category_id()) { 
                    $cat_class = 'cat-gray';
                  } else {
                    $cat_class = 'cat-highlight';
                  }
                } else {
                  $cat_class = '';
                }
              ?>

              <li <?php if($cat_class <> '') { echo 'class="' . $cat_class . '"'; } ?>>

                <?php ob_start(); // SAVE HTML OF ACTIVE CATEGORY ?>

                <a 
                  rel="<?php echo osc_category_id(); ?>" 
                  <?php if(osc_is_home_page()) { ?>href="#ct<?php echo osc_category_id(); ?>"<?php } else { ?>href="<?php echo osc_search_url($search_params); ?>"<?php } ?>
                  <?php if(osc_is_home_page()) { ?>class="open-home-cat"<?php } ?>
                  <?php if(osc_is_home_page()) { ?>title="<?php _e('Show subcategories of', 'zara'); ?> <?php echo osc_category_name(); ?>"<?php } ?>
                >
                  <div class="img<?php if(osc_category_field('s_color') == '') { ?> no-color<?php } ?>">
                    <span <?php if(osc_category_field('s_color') <> '') { ?>style="background:<?php echo osc_category_field('s_color'); ?>;"<?php } ?>></span>

                    <?php if(osc_get_preference('cat_icons', 'zara_theme') == 1) { ?>
                      <?php 
                        if(osc_category_field('s_icon') <> '') {
                          $icon = osc_category_field('s_icon');
                        } else {
                          if(isset($category_icons[osc_category_id()]) && $category_icons[osc_category_id()] <> '') {
                            $icon = $category_icons[osc_category_id()];
                          } else {
                            $icon = $category_icons[999];
                          }
                        }
                      ?>
                       
                      <i class="fa <?php echo $icon; ?>" <?php if(osc_category_field('s_color') <> '') { ?>style="color:<?php echo osc_category_field('s_color'); ?>;"<?php } ?>></i>
                    <?php } else { ?>
                      <img src="<?php echo osc_current_web_theme_url();?>images/small_cat/<?php echo osc_category_id();?>.png" />
                    <?php } ?>
                  </div>

                  <div class="name"><?php echo osc_category_name(); ?></div>
                </a>

                <?php $contents = ob_get_contents(); // GET HTML OF ACTIVE CATEGORY ?>
                <?php ob_end_flush(); ?>
              </li>

              <?php if($cat_class == 'cat-highlight') { ?>
                <?php $h_contents = $contents; ?>
              <?php } ?>

              <?php $i++; ?>
            <?php } ?>

            <?php if(isset($h_contents) && $h_contents <> '') { ?>
              <li class="cat-highlight resp is767">
                <?php echo $h_contents; ?>
              </li>
            <?php } ?>

          </ul>
        </div>

        <div class="right-arrow arrows tr1 noselect"><i class="fa fa-angle-right tr1"></i></div>
      </div>
    </div>
  </div>

  <div id="top-subcat">
    <div class="subcat-inside">

      <!-- HOME PAGE SUBCATEGORIES LIST -->
      <?php if(osc_is_home_page()){ ?>
        <div>
          <?php osc_goto_first_category(); ?>
          <?php $search_params = zara_search_params(); ?>
          <?php $search_params['sPriceMin'] = ''; ?>
          <?php $search_params['sPriceMax'] = ''; ?>

          <div id="home-cat" class="home-cat">
            <?php osc_goto_first_category(); ?>
            <?php while( osc_has_categories() ) { ?>
              <?php $search_params['sCategory'] = osc_category_id(); ?>

              <div id="ct<?php echo osc_category_id(); ?>" class="cat-tab">
                <?php $cat_id = osc_category_id(); ?>
                <div class="head">
                  <a href="<?php echo osc_search_url($search_params); ?>"><h2><?php echo osc_category_name(); ?></h2></a>

                  <span>
                    <?php if(osc_category_total_items() == '' or osc_category_total_items() == 0) { ?>
                       <?php _e('there are no listings yet', 'zara'); ?>
                    <?php } else { ?>
                      <?php _e('browse in', 'zara'); ?> <?php echo osc_category_total_items(); ?> <?php _e('listings', 'zara'); ?>
                    <?php } ?>
                  </span>

                  <div class="add"><a class="round2" href="<?php echo osc_item_post_url_in_category(); ?>"><i class="fa fa-plus"></i><?php _e('Add listing', 'zara'); ?></a></div>
                </div>
                <div class="left">
                  <a href="<?php echo osc_search_url($search_params); ?>" title="<?php echo osc_esc_html(__('Go to category', 'zara')); ?>">
                    <?php if(file_exists(osc_base_path() . 'oc-content/themes/' . osc_current_web_theme() . '/images/large_cat/' . osc_category_id() . '.jpg')) { ?>
                      <img src="<?php echo osc_current_web_theme_url();?>images/large_cat/<?php echo osc_category_id();?>.jpg" />
                    <?php } ?>
                  </a>
                </div>

                <div class="middle">

                  <?php $c = 0; ?>
                  <?php while(osc_has_subcategories()) { ?>
                    <?php $search_params['sCategory'] = osc_category_id(); ?>
             
                    <a <?php if($c >= 18) { ?>class="over-limit"<?php } ?> href="<?php echo osc_search_url($search_params); ?>">
                      <?php echo osc_category_name(); ?>
                    </a>

                    <?php $c++; ?>
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
      <?php } ?>


      <! -- SEARCH PAGE SUBCATEGORIES LIST -->
      <?php if(osc_is_search_page() && $search_cat_id <> 0 && $search_cat_id <> ''){ ?>
        <div>
          <?php osc_goto_first_category(); ?>
          <?php $search_params = zara_search_params(); ?>
          <?php $search_params['sPriceMin'] = ''; ?>
          <?php $search_params['sPriceMax'] = ''; ?>

          <?php if(!Category::newInstance()->isRoot($search_cat_id)) { ?>
            <div class="cat-navigation">
              <?php
                foreach($hierarchy as $h) {
                  if($h['pk_i_id'] <> $search_cat_id or !Category::newInstance()->isRoot($h['pk_i_id'])) {
                    $search_params['sCategory'] = $h['pk_i_id'];

                    if($h['pk_i_id'] <> $search_cat_id or $is_subcat) {
                      echo '<a href="' . osc_search_url($search_params) . '">' . $h['s_name'] . '</a>';
                    }
                  }
                }
              ?>
            </div>
          <?php } ?>

          <div id="search-cat" class="search-cat">
            <div class="cat-tab">
              <?php $cat_id = osc_category_id(); ?>

              <?php if(!empty($subcats)) { ?>
                <?php foreach($subcats as $s) { ?>
                  <?php $search_params['sCategory'] = $s['pk_i_id']; ?>

                  <div class="link-wrap">
                    <a href="<?php echo osc_search_url($search_params); ?>" <?php echo ($s['pk_i_id'] == $search_cat_id ? 'class="bold"' : ''); ?>>
                      <?php echo $s['s_name'] . ' <strong>' . $s['i_num_items'] . '</strong>'; ?>
                    </a>
                  </div>
                <?php } ?>
              <?php } ?>
            </div>
          </div>
        </div>
      <?php } ?>

    </div>
  </div>
</div>

<?php if(zara_current('zc_location') == 1 && osc_is_home_page()) { ?>
  <?php
    $loc_text = '';
    $current_loc = array(Params::getParam('sCountry'), Params::getParam('sRegion'), Params::getParam('sCity'));
    $current_loc = array_filter($current_loc);

    if(!empty($current_loc)) {
      if(count($current_loc) == 3) {
        $current_loc_text = $current_loc[2] . ' ' . __('in', 'zara') . ' ' . $current_loc[1] . ', ' . $current_loc[0];
      } else {
        $current_loc_text = implode(', ', $current_loc);
      }
    }

    if(isset($current_loc_text) && $current_loc_text <> '') {
      $loc_text = $current_loc_text;
    } else {
      $loc_text = __('Select location', 'zara');
    }
  ?>

  <div id="location-def" class="noselect">
    <a href="<?php echo osc_item_send_friend_url(); ?>" id="home-loc-open" class="l-button tr1">
      <span class="l-img">
        <i class="fa fa-map-marker"></i>
      </span>

      <span class="l-text tr1">
        <?php echo $loc_text; ?>
      </span>
    </a>
  </div>
<?php } ?>