<div id="sub-nav" class="item-nav">
  <div class="inside">
    <a href="#" class="active" data-type="detail"><span><?php _e('Details', 'starter'); ?></span></a>
    <a href="#" data-type="location"><span><?php _e('Location', 'starter'); ?></span></a>
    <a href="#" data-type="contact"><span><?php _e('Contact', 'starter'); ?></span></a>
    <a href="#" data-type="comments"><span><?php _e('Comments', 'starter'); ?></span></a>

    <?php
      $breadcrumb = osc_breadcrumb('<span class="bread-arrow">/</span>', false);
      $breadcrumb = str_replace('<span itemprop="title">' . osc_page_title() . '</span>', '<span itemprop="title">' . __('Home', 'starter') . '</span>', $breadcrumb);
    ?>

    <?php if( $breadcrumb != '') { ?>
      <div class="breadcrumb">
        <?php echo $breadcrumb; ?>
      </div>
    <?php } ?>
  </div>
</div>