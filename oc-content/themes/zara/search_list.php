<div id="list-view">

  <?php 
    // PREMIUM ITEMS
    if(zara_current('zc_cat_premium') == 1) { 
      osc_get_premiums(4);
      $c = 1;

      while(osc_has_premiums()) {
        zara_draw_item($c, 'list', true, 'premium-loop');
        $c++;
      }
    } 
  ?>



  <?php $c = 1; ?>
  <?php while(osc_has_items()) { ?>

    <?php zara_draw_item($c, 'list'); ?>


    <?php if($c == 3) { ?>
      <div class="list-prod list-ad">
        <?php echo zara_banner('search_list'); ?>
      </div>
    <?php } ?>

    <?php $c++; ?>
  <?php } ?>
</div>


