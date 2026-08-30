<?php $search_params = starter_search_params(); ?>
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
  $subcats = Category::newInstance()->findSubcategoriesEnabled($search_cat_id);

  if(empty($subcats)) {
    $is_subcat = false;
    $subcats = Category::newInstance()->findSubcategoriesEnabled(isset($search_cat_full['fk_i_parent_id']) ? $search_cat_full['fk_i_parent_id'] : null);
  } else {
    $is_subcat = true;
  }
?>


<div id="sub-nav">
  <div class="inside">
    <?php while(osc_has_categories()) { ?>
      <?php $search_params['sCategory'] = osc_category_id(); ?>
      <?php 
        if($root_cat_id <> '' and $root_cat_id <> 0) {
          if($root_cat_id <> osc_category_id()) { 
            $cat_class = '';
          } else {
            $cat_class = ' active';
          }
        } else {
          $cat_class = '';
        }

        $color = starter_get_cat_color(osc_category_id());
      ?>
      <a 
        id="cat-link"
        rel="<?php echo osc_category_id(); ?>" 
        <?php if(osc_is_home_page()) { ?>href="#ct<?php echo osc_category_id(); ?>"<?php } else { ?>href="<?php echo osc_search_url($search_params); ?>"<?php } ?>
        class="<?php if(osc_is_home_page()) { ?>open-home-cat<?php } ?><?php echo $cat_class; ?>"
      >
        <span><?php echo osc_category_name(); ?></span>
      </a>
    <?php } ?>


    <?php if(osc_is_search_page()) { ?>
      <div class="control">

        <div class="sort-it">
          <div class="sort-title">
            <div class="title-keep noselect">
              <?php $orders = osc_list_orders(); ?>
              <?php $current_order = osc_search_order(); ?>

              <?php foreach($orders as $label => $params) { ?>
                <?php $orderType = ($params['iOrderType'] == 'asc') ? '0' : '1'; ?>
                <?php if(osc_search_order() == $params['sOrder'] && osc_search_order_type() == $orderType) { ?>
                  <span><?php echo $label; ?></span>
                <?php } ?>
              <?php } ?>
            </div>

            <div id="sort-wrap">
              <div class="sort-content">
                <?php $i = 0; ?>
                <?php foreach($orders as $label => $params) { ?>
                  <?php $orderType = ($params['iOrderType'] == 'asc') ? '0' : '1'; ?>
                  <?php if(osc_search_order() == $params['sOrder'] && osc_search_order_type() == $orderType) { ?>
                    <a class="current" href="<?php echo osc_update_search_url($params) ; ?>"><span><?php echo $label; ?></span></a>
                  <?php } else { ?>
                    <a href="<?php echo osc_update_search_url($params) ; ?>"><span><?php echo $label; ?></span></a>
                  <?php } ?>
                  <?php $i++; ?>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>


        <div class="list-grid">
          <?php $def_view = osc_get_preference('def_view', 'starter_theme') == 0 ? 'gallery' : 'list'; ?>
          <?php $show = Params::getParam('sShowAs') == '' ? $def_view : Params::getParam('sShowAs'); ?>
          <a href="#" title="<?php echo osc_esc_html(__('Switch to list view', 'starter')); ?>" class="lg<?php echo ($show == 'list' ? ' active' : ''); ?>" data-view="list"><i class="fa fa-list-ul"></i></a>
          <a href="#" title="<?php echo osc_esc_html(__('Switch to grid view', 'starter')); ?>" class="lg<?php echo ($show == 'gallery' ? ' active' : ''); ?>" data-view="gallery"><i class="fa fa-th-large"></i></a>
        </div>
      </div>
    <?php } ?>
  </div>
</div>


<div id="top-subcat">
  <div class="subcat-inside">
    <div>
      <?php osc_goto_first_category(); ?>
      <?php $search_params = starter_search_params(); ?>
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

              <div class="add"><a href="<?php echo osc_item_post_url_in_category(); ?>"><?php _e('Add listing', 'starter'); ?></a></div>
            </div>

            <div class="middle">
              <?php while(osc_has_subcategories()) { ?>
                <?php $search_params['sCategory'] = osc_category_id(); ?>
         
                <a href="<?php echo osc_search_url($search_params); ?>">
                  <span>
                    <span class="icon"><?php echo starter_get_cat_icon( osc_category_id()); ?></span>
                    <span class="name"><?php echo osc_category_name(); ?></span>
                  </span>
                </a>
              <?php } ?>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>