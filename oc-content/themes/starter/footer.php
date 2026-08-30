    <?php
      osc_show_widgets('footer');
      $sQuery = __('Search in', 'starter') . ' ' . osc_total_active_items() . ' ' .  __('listings', 'starter');
    ?>
  </div>
</div>


<?php osc_run_hook('footer') ; ?>

<?php if ( starter_is_demo() ) { ?>
  <div id="piracy" class="noselect" title="Click to hide this box">This theme is ownership of MB Themes and can be bought only on <a href="https://osclasspoint.com/osclass-themes/general/starter-osclass-theme_i86">OsclassPoint.com</a>. When bought on other site, there is no support and updates provided. Do not support stealers, support developer!</div>
  <script>$(document).ready(function(){ $('#piracy').click(function(){ $(this).fadeOut(200); }); });</script>
<?php } ?>


<a class="mobile-post is767" href="<?php echo osc_item_post_url(); ?>"><i class="fa fa-plus"></i></a>

<?php if(osc_is_search_page()) { ?>
  <?php osc_get_latest_searches() ?>
  <?php if(osc_count_latest_searches() > 0) { ?>
    <div id="latest-search">
      <div class="inside">
        <span><?php _e('Recent Searches', 'starter'); ?>:</span>

        <?php while( osc_has_latest_searches() ) { ?>
          <a href="<?php echo osc_search_url(array('page' => 'search', 'sPattern' => osc_latest_search_text())); ?>"><?php echo osc_latest_search_text(); ?></a>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
<?php } ?>

<?php osc_run_hook('footer_pre'); ?>

<!-- FOOTER -->
<div id="footer">
  <div class="inside">
    <?php osc_run_hook('footer_top'); ?>
    
    <div class="left">
      <?php $pages = Page::newInstance()->listAll($indelible = 0, $b_link = 1, $locale = null, $start = null, $limit = 10); ?>

      <?php foreach($pages as $p) { ?>
        <?php View::newInstance()->_exportVariableToView('page', $p); ?>

        <a href="<?php echo osc_static_page_url(); ?>" title="<?php echo osc_esc_html(osc_static_page_title()); ?>"><?php echo ucfirst(osc_static_page_title());?></a>
      <?php } ?>


      <?php
        $order_id = (int)osc_get_preference('link_remover_order_id', 'starter_theme');

        if($order_id < 8000 || $order_id > 500000 || osc_get_preference('remove_footer_link', 'starter_theme') == 0) {
          $session_mark = (isset($_SESSION['mb_support_link']) ? $_SESSION['mb_support_link'] : '');
          
          if($session_mark == '' || $session_mark == 'str') {
            $_SESSION['mb_support_link'] = 'str';
            ?>
              <span class="mb-support-link str-support-link" data-plugin="starter"><a href="https://osclass-classifieds.com/">Classifieds Ads Software</a></span>
            <?php
          }
        }
      ?>
      
      <?php osc_run_hook('footer_links'); ?>

      <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact', 'starter'); ?></a>
    </div>

    <div class="right share">
      <?php
        osc_reset_resources();

        if(osc_is_ad_page()) {
          $share_url = osc_item_url();
        } else {
          $share_url = osc_base_url();
        }

        $share_url = urlencode($share_url);
      ?>

      <?php if(osc_is_ad_page()) { ?>
        <span class="whatsapp"><a href="whatsapp://send?text=<?php echo $share_url; ?>" data-action="share/whatsapp/share"><i class="fa fa-whatsapp"></i></a></span>
      <?php } ?>

      <span class="facebook"><a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" title="<?php echo osc_esc_html(__('Share us on Facebook', 'starter')); ?>" target="_blank"><i class="fa fa-facebook"></i></a></span>
      <span class="pinterest"><a href="https://pinterest.com/pin/create/button/?url=<?php echo $share_url; ?>&media=<?php echo osc_base_url(); ?>oc-content/themes/<?php echo osc_current_web_theme(); ?>/images/logo.jpg&description=" title="<?php echo osc_esc_html(__('Share us on Pinterest', 'starter')); ?>" target="_blank"><i class="fa fa-pinterest"></i></a></span>
      <span class="twitter"><a href="https://twitter.com/home?status=<?php echo $share_url; ?>%20-%20<?php _e('your', 'starter'); ?>%20<?php _e('classifieds', 'starter'); ?>" title="<?php echo osc_esc_html(__('Tweet us', 'starter')); ?>" target="_blank"><i class="fa fa-twitter"></i></a></span>
      <span class="google-plus"><a href="https://plus.google.com/share?url=<?php echo $share_url; ?>" title="<?php echo osc_esc_html(__('Share us on Google+', 'starter')); ?>" target="_blank"><i class="fa fa-google-plus"></i></a></span>
      <span class="linkedin"><a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $share_url; ?>&title=<?php echo osc_esc_html(__('My', 'starter')); ?>%20<?php echo osc_esc_html(__('classifieds', 'starter')); ?>&summary=&source=" title="<?php echo osc_esc_html(__('Share us on LinkedIn', 'starter')); ?>" target="_blank"><i class="fa fa-linkedin"></i></a></span>
    </div>

    <div class="locales is767">
      <?php 
        $current_locale = mb_get_current_user_locale();
        osc_goto_first_locale(); 
      ?>

      <?php while(osc_has_web_enabled_locales()) { ?>
        <a href="<?php echo osc_change_language_url(osc_locale_code()); ?>" class="<?php if(osc_locale_code() == $current_locale['pk_c_code']) { ?>active<?php } ?>">

          <?php
            $loc = strtoupper(substr(osc_locale_code(), 0, 2));
            $ext = substr(osc_locale_code(), -2);

            if($loc <> $ext) {
              $loc .= ' (' . $ext . ')';
            }

            echo $loc;
          ?>

        </a>
      <?php } ?>
    </div>
  </div>
</div>

<?php osc_run_hook('footer_after'); ?>


<script>
  $(document).ready(function(){

    // JAVASCRIPT AJAX LOADER FOR LOCATIONS 
    var termClicked = false;
    var currentCountry = "<?php echo starter_ajax_country(); ?>";
    var currentRegion = "<?php echo starter_ajax_region(); ?>";
    var currentCity = "<?php echo starter_ajax_city(); ?>";


    // On first click initiate loading
    $('body').on('click', '#location-picker .term', function() {
      if( !termClicked ) {
        $(this).keyup();
      }

      termClicked = true;
    });


    // Create delay
    var delay = (function(){
      var timer = 0;
      return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
      };
    })();


    //$(document).ajaxStart(function() { 
      //$("#location-picker, .location-picker").addClass('searching');
    //});

    $(document).ajaxSend(function(evt, request, settings) {
      var url = settings.url;

      if (url.indexOf("ajaxLoc") >= 0) {
        $("#location-picker, .location-picker").addClass('searching');
      }
    });

    $(document).ajaxStop(function() {
      $("#location-picker, .location-picker").removeClass('searching');
    });



    $('body').on('keyup', '#location-picker .term', function(e) {
      delay(function(){
        var min_length = 3;
        var elem = $(e.target);
        var term = encodeURIComponent(elem.val());

        // If comma entered, remove characters after comma including
        if(term.indexOf(',') > 1) {
          term = term.substr(0, term.indexOf(','));
        }

        // If comma entered, remove characters after - including (because city is shown in format City - Region)
        if(term.indexOf(' - ') > 1) {
          term = term.substr(0, term.indexOf(' - '));
        }

        var block = elem.closest("#location-picker");
        var shower = elem.closest("#location-picker").find(".shower");

        shower.html('');

        if(term != '' && term.length >= min_length) {
          // Combined ajax for country, region & city
          $.ajax({
            type: "POST",
            url: baseAjaxUrl + "&ajaxLoc=1&term=" + term,
            dataType: 'json',
            success: function(data) {
              var length = data.length;
              var result = '';
              var result_first = '';
              var countCountry = 0;
              var countRegion = 0;
              var countCity = 0;


              if(shower.find('.service.min-char').length <= 0) {
                for(key in data) {

                  // Prepare location IDs
                  var id = '';
                  var country_code = '';
                  if( data[key].country_code ) {
                    country_code = data[key].country_code;
                    id = country_code;
                  }

                  var region_id = '';
                  if( data[key].region_id ) {
                    region_id = data[key].region_id;
                    id = region_id;
                  }

                  var city_id = '';
                  if( data[key].city_id ) {
                    city_id = data[key].city_id;
                    id = city_id;
                  }
                    

                  // Count cities, regions & countries
                  if (data[key].type == 'city') {
                    countCity = countCity + 1;
                  } else if (data[key].type == 'region') {
                    countRegion = countRegion + 1;
                  } else if (data[key].type == 'country') {
                    countCountry = countCountry + 1;
                  }


                  // Find currently selected element
                  var selectedClass = '';
                  if( 
                    data[key].type == 'country' && parseInt(currentCountry) == parseInt(data[key].country_code) 
                    || data[key].type == 'region' && parseInt(currentRegion) == parseInt(data[key].region_id) 
                    || data[key].type == 'city' && parseInt(currentCity) == parseInt(data[key].city_id) 
                  ) { 
                    selectedClass = ' selected'; 
                  }


                  // For cities, get region name
                  var nameTop = '';
                  if(data[key].name_top ) {
                    nameTop = ' <span>' + data[key].name_top + '</span>';
                  }


                  if(data[key].type != 'city_more') {

                    // When classic city, region or country in loop and same does not already exists
                    if(shower.find('div[data-code="' + data[key].type + data[key].id + '"]').length <= 0) {
                      result += '<div class="option ' + data[key].type + selectedClass + '" data-country="' + country_code + '" data-region="' + region_id + '" data-city="' + city_id + '" data-code="' + data[key].type + id + '" id="' + id + '">' + data[key].name + nameTop + '</div>';
                    }

                  } else {

                    // When city counter and there is more than 12 cities for search
                    if(shower.find('.more-city').length <= 0) {
                      if( parseInt(data[key].name) > 0 ) {
                        result += '<div class="option service more-pick more-city city">... ' + (data[key].name) + ' <?php echo osc_esc_js(__('more cities, specify your location', 'starter')); ?></div>';
                      }
                    }
                  }
                }


                // No city, region or country found
                if( countCountry == 0 && shower.find('.empty-country').length <= 0 && shower.find('.service.min-char').length <= 0) {
                  shower.find('.option.country').remove();
                  result_first += '<div class="option service empty-pick empty-country country"><?php echo osc_esc_js(__('No country match to your criteria', 'starter')); ?></div>';
                }

                if( countRegion == 0 && shower.find('.empty-region').length <= 0 && shower.find('.service.min-char').length <= 0) {
                  shower.find('.option.region').remove();
                  result_first += '<div class="option service empty-pick empty-region region"><?php echo osc_esc_js(__('No region match to your criteria', 'starter')); ?></div>';
                }

                if( countCity == 0 && shower.find('.empty-city').length <= 0 && shower.find('.service.min-char').length <= 0) {
                  shower.find('.option.city').remove();
                  result_first += '<div class="option service empty-pick empty-city city"><?php echo osc_esc_js(__('No city match to your criteria', 'starter')); ?></div>';
                }

              }

              shower.html(result_first + result);
            }
          });

        } else {
          // Term is not length enough
          shower.html('<div class="option service min-char"><?php echo osc_esc_js(__('Enter at least', 'starter')); ?> ' + (min_length - term.length) + ' <?php echo osc_esc_js(__('more letter(s)', 'starter')); ?></div>');
        }
      }, 500 );
    });




    <?php if(osc_get_preference('item_ajax', 'starter_theme') == 1) { ?>
      // JAVASCRIPT AJAX LOADER FOR ITEMS AUTOCOMPLETE
      var patternClicked = false;

      // On first click initiate loading
      $('body').on('click', '#item-picker .pattern', function() {
        if( !patternClicked ) {
          $(this).keyup();
        }

        patternClicked = true;
      });


      // Create delay
      var delay2 = (function(){
        var timer2 = 0;
        return function(callback, ms){
          clearTimeout (timer2);
          timer2 = setTimeout(callback, ms);
        };
      })();


      //$(document).ajaxStart(function() { 
        //$("#item-picker, .item-picker").addClass('searching');
      //});

      $(document).ajaxSend(function(evt, request, settings) {
        var url = settings.url;

        if (url.indexOf("ajaxItem") >= 0) {
          $("#item-picker, .item-picker").addClass('searching');
        }
      });

      $(document).ajaxStop(function() {
        $("#item-picker, .item-picker").removeClass('searching');
      });


      $('body').on('keyup', '#item-picker .pattern', function(e) {
        delay(function(){
          var min_length = 3;
          var elem = $(e.target);
          var pattern = elem.val();

          var block = elem.closest("#item-picker");
          var shower = elem.closest("#item-picker").find(".shower");

          shower.html('');

          if(pattern != '' && pattern.length >= min_length) {
            // Combined ajax for country, region & city
            $.ajax({
              type: "POST",
              url: baseAjaxUrl + "&ajaxItem=1&pattern=" + pattern,
              dataType: 'json',
              success: function(data) { 
                var length = data.length;
                var result = '';

                if(shower.find('.service.min-char').length <= 0) {
                  for(key in data) {
                  
                    // When item already is not in shower
                    if(shower.find('div[data-item-id="' + data[key].pk_i_id + '"]').length <= 0) {
                      result += '<a class="option" data-item-id="' + data[key].pk_i_id + '" href="' + data[key].item_url + '" title="<?php echo osc_esc_js(__('Click to open listing', 'starter')); ?>">'
                      result += '<div class="left"><img src="' + data[key].image_url + '"/></div>';
                      result += '<div class="right">';
                      result += '<div class="top">' + data[key].s_title + '</div>';
                      result += '<div class="bottom">' + data[key].i_price + '</div>';
                      result += '</div>';
                      result += '</a>';
                    }
                  }


                  // No city, region or country found
                  if( length <= 0) {
                    shower.find('.option').remove();
                    result = '<div class="option service empty-pick"><?php echo osc_esc_js(__('No listing match to your criteria', 'starter')); ?></div>';
                  }
                }

                shower.html(result);
              }
            });

          } else {
            // Term is not length enough
            shower.html('<div class="option service min-char"><?php echo osc_esc_js(__('Enter at least', 'starter')); ?> ' + (min_length - pattern.length) + ' <?php echo osc_esc_js(__('more letter(s)', 'starter')); ?></div>');
          }
        }, 500 );
      });
    <?php } ?>


    // PLACEHOLDERS
    $('#yourName, #authorName, #comment_form #authorName, #s_name').attr('placeholder', '<?php echo osc_esc_js(__('Your name', 'starter')); ?>');
    $('#yourEmail, #authorEmail, #comment_form #authorEmail, #s_email').attr('placeholder', '<?php echo osc_esc_js(__('Email address', 'starter')); ?>');
    $('#login #email').attr('placeholder', '<?php echo osc_esc_js(__('Email address', 'starter')); ?>');
    $('#login #password').attr('placeholder', '<?php echo osc_esc_js(__('Password', 'starter')); ?>');
    $('#register #s_password').attr('placeholder', '<?php echo osc_esc_js(__('At least 6 characters', 'starter')); ?>');
    $('#register #s_password2').attr('placeholder', '<?php echo osc_esc_js(__('Repeat password', 'starter')); ?>');
    $('#phoneNumber').attr('placeholder', '<?php echo osc_esc_js(__('Phone number', 'starter')); ?>');
    $('#s_phone_mobile').attr('placeholder', '<?php echo osc_esc_js(__('Mobile phone', 'starter')); ?>');
    $('#s_phone_land').attr('placeholder', '<?php echo osc_esc_js(__('Land phone', 'starter')); ?>');
    $('#message, #body, #body').attr('placeholder', '<?php echo osc_esc_js(__('I want to ask...', 'starter')); ?>');
    $('#sendfriend #message').attr('placeholder', '<?php echo osc_esc_js(__('I want to share this item...', 'starter')); ?>');
    $('#comment_form #body').attr('placeholder', '<?php echo osc_esc_js(__('I want to ask/share...', 'starter')); ?>');
    $('#title, #comment_form #title').attr('placeholder', '<?php echo osc_esc_js(__('Short title...', 'starter')); ?>');
    $('#subject').attr('placeholder', '<?php echo osc_esc_js(__('Message subject...', 'starter')); ?>');
    $('#friendName').attr('placeholder', '<?php echo osc_esc_js(__('Friend\'s name or nick...', 'starter')); ?>');
    $('#friendEmail').attr('placeholder', '<?php echo osc_esc_js(__('Friend\'s email...', 'starter')); ?>');
    $('input[name="sPattern"]').attr('placeholder', '<?php echo osc_esc_js(__('Search...', 'starter')); ?>');
    $('#priceMin').attr('placeholder', '<?php echo osc_esc_js(__('min', 'starter')); ?>');
    $('#priceMax').attr('placeholder', '<?php echo osc_esc_js(__('max', 'starter')); ?>');
    $('.add_item input[name^="title"]').attr('placeholder', '<?php echo osc_esc_js(__('Short title for listing...', 'starter')); ?>');
    $('.add_item textarea[name^="description"]').attr('placeholder', '<?php echo osc_esc_js(__('Detail description of listing...', 'starter')); ?>');
    $('.add_item #contactName').attr('placeholder', '<?php echo osc_esc_js(__('Your name', 'starter')); ?>');
    $('.add_item #cityArea').attr('placeholder', '<?php echo osc_esc_js(__('City area', 'starter')); ?>');
    $('.add_item #sPhone').attr('placeholder', '<?php echo osc_esc_js(__('Mobile phone', 'starter')); ?>');
    $('.add_item #zip').attr('placeholder', '<?php echo osc_esc_js(__('Zip code', 'starter')); ?>');
    $('.add_item #contactEmail').attr('placeholder', '<?php echo osc_esc_js(__('Email address', 'starter')); ?>');
    $('.add_item #address').attr('placeholder', '<?php echo osc_esc_js(__('Address', 'starter')); ?>');
    $('textarea[id^="s_info"]').attr('placeholder', '<?php echo osc_esc_js(__('Information about you or your company...', 'starter')); ?>');
    $('.modify_profile #cityArea').attr('placeholder', '<?php echo osc_esc_js(__('City area', 'starter')); ?>');
    $('.modify_profile #address').attr('placeholder', '<?php echo osc_esc_js(__('Address', 'starter')); ?>');
    $('.modify_profile #zip').attr('placeholder', '<?php echo osc_esc_js(__('Zip code', 'starter')); ?>');
    $('.modify_profile #s_website').attr('placeholder', '<?php echo osc_esc_js(__('Website URL', 'starter')); ?>');
    $('.modify_profile #new_email').attr('placeholder', '<?php echo osc_esc_js(__('New contact email', 'starter')); ?>');
    $('.modify_profile #password').attr('placeholder', '<?php echo osc_esc_js(__('Your current password', 'starter')); ?>');
    $('.modify_profile #new_password').attr('placeholder', '<?php echo osc_esc_js(__('Your new password', 'starter')); ?>');
    $('.modify_profile #new_password2').attr('placeholder', '<?php echo osc_esc_js(__('Repeat new password', 'starter')); ?>');

  });
</script>	