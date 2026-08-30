<div id="gallery-view" class="white">
  <div class="block">
    <div class="wrap">

      <?php 
        // PREMIUM ITEMS
        if(zara_current('zc_cat_premium') == 1) { 

          osc_get_premiums(5);
          $c = 1;

          while(osc_has_premiums()) {
            zara_draw_item($c, 'gallery', true, 'premium-loop');
            $c++;
          }
        } 
      ?>


      <?php $c = 1; ?>
      <?php while( osc_has_items() ) { ?>
        <?php zara_draw_item($c, 'gallery'); ?>
        <?php $c++; ?>
      <?php } ?>

    </div>
  </div>
 
  <?php View::newInstance()->_erase('items') ; ?>
</div>