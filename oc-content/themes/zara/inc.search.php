<?php
  $sQuery = __('Search in', 'zara') . ' ' . osc_total_active_items() . ' ' .  __('listings', 'zara');
  $show_top_search = zara_current('zc_top_search');

  if(osc_count_countries() > 1) { $show_country = true; } else { $show_country = false; }
?>

<div class="scroller">
  <form action="<?php echo osc_base_url(true); ?>" method="get" name="topSearch" class="search nocsrf" >
    <?php if( ($show_top_search == 1 && osc_is_home_page()) || !osc_is_home_page() ) { ?>
      <?php if($show_country) { ?><input type="hidden" name="sCountry<?php echo radius_installed(); ?>" id="sCountry" value="<?php echo Params::getParam('sCountry' . radius_installed());?>" /><?php } ?>
      <input type="hidden" name="sRegion<?php echo radius_installed(); ?>" id="sRegion" value="<?php echo Params::getParam('sRegion' . radius_installed());?>" />
      <input type="hidden" name="sCity<?php echo radius_installed(); ?>" id="sCity" value="<?php echo Params::getParam('sCity' . radius_installed());?>" />
      <input type="hidden" name="page" value="search" />
      <input type="hidden" name="cookie-action" id="cookie-action" value="" />
      <input type="hidden" name="sCompany" class="sCompany" id="sCompany" value="<?php echo Params::getParam('sCompany');?>" />
      <input type="hidden" name="sShowAs" class="sShowAs" id="sShowAs" value="<?php echo Params::getParam('sShowAs');?>" />

      <fieldset class="main">
        <input type="text" name="sPattern"  id="query" placeholder="<?php echo $sQuery; ?>" value="<?php if(Params::getParam('sPattern') <> '') { echo Params::getParam('sPattern'); } ?>" />
        <?php  if ( osc_count_categories() ) { ?>
          <?php mb_categories_select('sCategory', Params::getParam('sCategory'), __('Select a category', 'zara')); ?>
        <?php  } ?> 

        <button id="top-search" type="submit"><span>&nbsp;</span></button>
        <div class="clear-cookie" title="<?php _e('Clear search form', 'zara'); ?>"><i class="fa fa-trash-o not767"></i><span class="is767 resp"><?php _e('clear all', 'zara'); ?></span></div>
      </fieldset>
    <?php } ?>


    <a class="h-pub" href="<?php echo osc_item_post_url(); ?>">
      <span class="first"></span>
      <span class="second"><span class="resp is1200"><?php _e('Publish', 'zara'); ?></span><span class="not-resp is767 not1200"><?php _e('Publish listing', 'zara'); ?></span></span>
    </a>     
              
    <div id="search-example"></div>
  </form>  
</div>

<script>
$('.clear-cookie').click(function(){
  // Clear all search parameters
  $.ajax({
    url: "<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/ajax.php?clearCookieAll=done",
    type: "GET",
    success: function(response){
      //alert(response);
    }
  });

  $('#sCategory').val('');
  $('#uniform-sCategory span').text('<?php echo osc_esc_js(__('Select a category', 'zara')); ?>');
  $('#query').val('');
  $('#priceMin').val('');
  $('#priceMax').val('');
  $('#cookie-action').val('done');

  $('#Locator').attr('rel', '');
  $('input[name=sCountry<?php echo radius_installed(); ?>]').val('');
  $('input[name=sRegion<?php echo radius_installed(); ?>]').val('');
  $('input[name=sCity<?php echo radius_installed(); ?>]').val('');
  $('#uniform-Locator span').text('<?php _e('Location', 'zara'); ?>');

  $('.h-my-loc .font').hide(150);
  $('.h-my-loc .font').text('<?php echo osc_esc_js(__('Location not saved', 'zara')); ?>');
  $('.h-my-loc .font').delay(150).show(150);
});

$('.clear-cookie-location').click(function(){
  $.ajax({
    url: "<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/ajax.php?clearCookieLocation=done",
    type: "GET",
    success: function(response){
      //alert(response);
    }
  });

  $('#Locator').attr('rel', '');
  $('input[name=sCountry<?php echo radius_installed(); ?>]').val('');
  $('input[name=sRegion<?php echo radius_installed(); ?>]').val('');
  $('input[name=sCity<?php echo radius_installed(); ?>]').val('');
  $('#uniform-Locator span').text('<?php echo osc_esc_js(__('Location', 'zara')); ?>');

  $('.h-my-loc .font').hide(150);
  $('.h-my-loc .font').text('<?php echo osc_esc_js(__('Location not saved', 'zara')); ?>');
  $('.h-my-loc .font').delay(150).show(150);
});

$('#sCategory').change(function(){
  $('#cookie-action').val('done');
});

// DO NOT FADE WHEN RESPONSIVE
if($(document).width() > 767) {
  var time = 200;
  var delay = 500;
} else {
  var time = 0;
  var delay = 0;
}


$('#loc-list li').click(function(){
  var sQuery = '<?php echo osc_esc_js( $sQuery ); ?>';
  var isreg = $(this).attr('rel');
  if(!isreg.indexOf("--")) { 
    $('#sCity').val(isreg.substring(2, isreg.length));
  } else if(!isreg.indexOf("//")) { 
    $('#sRegion').val(isreg.substring(2, isreg.length));
    $('#sCity').val('');
  } else {
    <?php if($show_country) { ?>$('#sCountry').val(isreg);<?php } ?>
    $('#sRegion').val('');
    $('#sCity').val('');
  }

  if($('input[name=sPattern]').val() == sQuery) {
    $('input[name=sPattern]').val('');
  }

  $('#loc-box').stop(true, true).fadeOut(time);

  $(this).attr('rel', '');
  $('#cookie-action').val('done');
  $('.search').submit();
});


// Category click list action
$('#inc-cat-list li').click(function(){
  var sQuery = '<?php echo osc_esc_js( $sQuery ); ?>';
  $('input[name="sCategory"]').val($(this).attr('rel'));

  if($('input[name="sPattern"]').val() == sQuery) {
    $('input[name="sPattern"]').val('');
  }

  $('#inc-cat-box').stop(true, true).fadeOut(time);

  $(this).attr('rel', '');
  $('#cookie-action').val('done');
  $('form[name="topSearch"]').submit();
});

// Remove &nbsp; and - from location name in span
$(document).ready(function(){
  var loc_text = $('#uniform-Locator span').text().trim();
  loc_text = loc_text.replace('- ','');
  $('#uniform-Locator span').text(loc_text);
});

//document.getElementById("sCategory").onchange = function(){this.form.submit();};
$("#sCategory").change(function(){
  $('.search').submit();
});

$(".search").submit(function(){
  $('#Locator').attr('rel', '');
});  
</script>