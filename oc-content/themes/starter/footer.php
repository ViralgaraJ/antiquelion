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



<?php osc_run_hook('footer_pre'); ?>

<!-- FOOTER -->
<?php
  $al_share_url = urlencode(osc_is_ad_page() ? osc_item_url() : osc_base_url());
  $al_share_title = rawurlencode(osc_is_ad_page() ? osc_item_title() : osc_page_title());
  $al_pages = Page::newInstance()->listAll($indelible = 0, $b_link = 1, $locale = null, $start = null, $limit = 10);
?>
<footer id="al-footer">
  <?php osc_run_hook('footer_top'); ?>
  <div class="al-wrap al-footer-grid">
    <div class="al-footer-brand">
      <a class="al-footer-logo" href="<?php echo osc_base_url(); ?>"><?php echo logo_header(); ?></a>
      <p><?php _e('A marketplace for authentic antiques and collectibles. Buy directly from sellers or bid on live auctions, with prices in your own currency.', 'starter'); ?></p>
      <div class="al-share" aria-label="<?php echo osc_esc_html(__('Share', 'starter')); ?>">
        <?php if (osc_is_ad_page()) { ?>
          <a href="https://wa.me/?text=<?php echo $al_share_title; ?>%20<?php echo $al_share_url; ?>" target="_blank" rel="noopener" aria-label="<?php echo osc_esc_html(__('Share on WhatsApp', 'starter')); ?>"><i class="fa fa-whatsapp" aria-hidden="true"></i></a>
        <?php } ?>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $al_share_url; ?>" target="_blank" rel="noopener" aria-label="<?php echo osc_esc_html(__('Share on Facebook', 'starter')); ?>"><i class="fa fa-facebook" aria-hidden="true"></i></a>
        <a href="https://pinterest.com/pin/create/button/?url=<?php echo $al_share_url; ?>&amp;description=<?php echo $al_share_title; ?>" target="_blank" rel="noopener" aria-label="<?php echo osc_esc_html(__('Share on Pinterest', 'starter')); ?>"><i class="fa fa-pinterest" aria-hidden="true"></i></a>
        <a href="https://x.com/intent/post?url=<?php echo $al_share_url; ?>&amp;text=<?php echo $al_share_title; ?>" target="_blank" rel="noopener" aria-label="<?php echo osc_esc_html(__('Share on X', 'starter')); ?>"><svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="currentColor" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $al_share_url; ?>" target="_blank" rel="noopener" aria-label="<?php echo osc_esc_html(__('Share on LinkedIn', 'starter')); ?>"><i class="fa fa-linkedin" aria-hidden="true"></i></a>
      </div>
    </div>

    <nav class="al-footer-col" aria-label="<?php echo osc_esc_html(__('Marketplace', 'starter')); ?>">
      <h2><?php _e('Marketplace', 'starter'); ?></h2>
      <a href="<?php echo osc_search_url(array('page' => 'search', 'sAuction' => 1)); ?>"><?php _e('Live auctions', 'starter'); ?></a>
      <a href="<?php echo osc_search_url(array('page' => 'search')); ?>"><?php _e('All listings', 'starter'); ?></a>
      <?php foreach (array_slice(osc_get_categories(), 0, 4) as $c) { ?>
        <a href="<?php echo osc_search_url(array('page' => 'search', 'sCategory' => $c['pk_i_id'])); ?>"><?php echo osc_esc_html($c['s_name']); ?></a>
      <?php } ?>
    </nav>

    <nav class="al-footer-col" aria-label="<?php echo osc_esc_html(__('Sell', 'starter')); ?>">
      <h2><?php _e('Sell', 'starter'); ?></h2>
      <a href="<?php echo osc_item_post_url(); ?>"><?php _e('Sell an item', 'starter'); ?></a>
      <?php if (osc_is_web_user_logged_in()) { ?>
        <a href="<?php echo osc_user_dashboard_url(); ?>"><?php _e('My account', 'starter'); ?></a>
        <a href="<?php echo osc_user_list_items_url(); ?>"><?php _e('My listings', 'starter'); ?></a>
        <a href="<?php echo osc_user_alerts_url(); ?>"><?php _e('Saved searches', 'starter'); ?></a>
      <?php } else { ?>
        <a href="<?php echo osc_user_login_url(); ?>"><?php _e('Sign in', 'starter'); ?></a>
        <a href="<?php echo osc_register_account_url(); ?>"><?php _e('Create an account', 'starter'); ?></a>
      <?php } ?>
    </nav>

    <nav class="al-footer-col" aria-label="<?php echo osc_esc_html(__('Help', 'starter')); ?>">
      <h2><?php _e('Help', 'starter'); ?></h2>
      <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact us', 'starter'); ?></a>
      <?php foreach ($al_pages as $p) { View::newInstance()->_exportVariableToView('page', $p); ?>
        <a href="<?php echo osc_static_page_url(); ?>"><?php echo osc_esc_html(ucfirst(osc_static_page_title())); ?></a>
      <?php } ?>
      <?php osc_run_hook('footer_links'); ?>
    </nav>
  </div>

  <div class="al-footer-bottom">
    <div class="al-wrap">
      <span>&copy; <?php echo date('Y'); ?> <?php echo osc_esc_html(osc_page_title()); ?></span>
      <span class="al-footer-credits">
        <a href="https://www.exchangerate-api.com" target="_blank" rel="noopener"><?php _e('Rates by Exchange Rate API', 'starter'); ?></a>
        <a href="https://db-ip.com" target="_blank" rel="noopener"><?php _e('IP geolocation by DB-IP', 'starter'); ?></a>
        <?php
          $order_id = (int)osc_get_preference('link_remover_order_id', 'starter_theme');
          if ($order_id < 8000 || $order_id > 500000 || osc_get_preference('remove_footer_link', 'starter_theme') == 0) {
            $session_mark = (isset($_SESSION['mb_support_link']) ? $_SESSION['mb_support_link'] : '');
            if ($session_mark == '' || $session_mark == 'str') {
              $_SESSION['mb_support_link'] = 'str';
              ?><span class="mb-support-link str-support-link" data-plugin="starter"><a href="https://osclass-classifieds.com/">Classifieds Ads Software</a></span><?php
            }
          }
        ?>
      </span>
    </div>
  </div>
</footer>

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