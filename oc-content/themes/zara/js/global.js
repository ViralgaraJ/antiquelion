$(document).ready(function(){

  // INITIALIZE IS TOUCH DEVICE
  if(is_touch_device()) {
    //$('body').addClass('is_touch');
  }


  // LAZY LOAD PLUGIN
  if(zaraLazy == "1" && zaraMasonry == "0" ) {
    $("img.lazy").lazyload({
      effect : "fadeIn"
    });
  }


  // MASONRY - CREATE GRID WHEN IMAGES HAS DIFFERENT SIZE (height)
  if(zaraMasonry == "1") {
    var $grid = $('.white .block').masonry({
      itemSelector: '.simple-prod'
    });

    $grid.imagesLoaded().progress( function() {
      $grid.masonry('layout');
    });
  }


  // STICKY SIDEBAR
  if (($(window).width()+scrollCompensate()) >= 768) {
    if (zaraStick == "1") {
      $('#side-right').stick_in_parent({
        parent: $("#listing"), offset_top: 0
      }).on("sticky_kit:stick", function(e) {
        // Do something on stick
      });
    }
  }


  // STICKY SEARCH SIDEBAR
  if (($(window).width()+scrollCompensate()) >= 768) {
    if (zaraSearchStick == "1") {
      $('.list #sidebar').stick_in_parent({
        parent: $(".content"), offset_top: 0
      }).on("sticky_kit:stick", function(e) {
        // Do something on stick
      });
    }
  }


  // SET COLOR OF SEARCH BUTTON AFTER IMAGE IS LOADED
  var image = new Image();
  image.onload = function () {
    $('.scroller button#top-search span').css('opacity', '1');
    $('#h-search span').css('opacity', '1');
  }

  //image.src = "/oc-content/themes/zara/images/search-sprite.png";
  image.src = zaraSearchImg;



  // HEADER FUNCTIONALITY
  $('#uniform-Locator, .top-my, #uniform-sCategory').hover(function(){
    $(this).css('z-index', 99999);
  }, function(){
    $(this).css('z-index', 9);
  });


  idTabsMultiLine();


  // -------------------------------------------------------
  // SCRIPTS FOR RESPONSIVE DESIGN: 0 - 767px
  // -------------------------------------------------------

  if (($(window).width() + scrollCompensate()) <= 767) {
    var close_btn = true;

    $('.list #sidebar').remove();


    if($('#s-box .sidebar-hooks').length) {
      if($('#s-box .sidebar-hooks').html().trim() == '') {
        $('#s-box .s-hooks').remove();
      }
    }


    // ITEM BX-SLIDER
    if(zaraBxSlider == "1") {
      $('.item-bxslider').bxSlider({
        minSlides: 1,
        maxSlides: 1,
        moveSlides: 1,
        slideWidth: $(window).outerWidth(),
        infiniteLoop: false,
        slideMargin: 0,
        pager: true,
        touchEnabled: false,
        onSlideBefore: function($elem, oldIndex, newIndex){
          $('#photo-count .p-from').text(newIndex + 1);
          $('#photo-count .p-to').text(newIndex + 2);
        }
      });
    }


    // SHOW HIDE FUNCTIONALITY
    $('body').on('click', '.sc-click', function(e){
      e.preventDefault();

      if(!$(this).hasClass('opened') || !$(this).hasClass('closed')) {
        $(this).addClass('closed');
      }

      $(this).toggleClass('opened closed');

      $(this).parent().find('.sc-block').slideToggle(300);

      if($(this).parent('#location').length) {
        google.maps.event.trigger(map, 'resize');
      }
    });

  } 




  // -------------------------------------------------------
  // SCRIPTS FOR RESPONSIVE DESIGN: 768 - 1200px
  // -------------------------------------------------------

  if (($(window).width() + scrollCompensate()) >= 768 && ($(window).width() + scrollCompensate()) <= 1200) {
    var close_btn = false;
    top_cat_move();


    // ITEM BX-SLIDER
    if(zaraBxSlider == "1") {
      $('.item-bxslider').bxSlider({
        minSlides: 1,
        maxSlides: 1,
        moveSlides: 1,
        slideWidth: $(window).outerWidth(),
        infiniteLoop: false,
        slideMargin: 0,
        pager: true,
        touchEnabled: false,
        onSlideBefore: function($elem, oldIndex, newIndex){
          $('#photo-count .p-from').text(newIndex + 1);
          $('#photo-count .p-to').text(newIndex + 2);
        }
      });
    }
  }




  // -------------------------------------------------------
  // SCRIPTS FOR RESPONSIVE DESIGN: 1201px or more
  // -------------------------------------------------------

  if (($(window).width() + scrollCompensate()) > 1200) {
    $('#s-box').remove();
    var close_btn = false;
    top_cat_move();


    // ITEM BX-SLIDER
    if(zaraBxSlider == "1") {
      $('.item-bxslider').bxSlider({
        minSlides: parseInt(zaraBxSliderSlides),
        maxSlides: parseInt(zaraBxSliderSlides),
        moveSlides: 1,
        slideWidth: $('#listing #pictures').outerWidth()/parseInt(zaraBxSliderSlides),
        infiniteLoop: false,
        slideMargin: 15,
        pagerCustom: '#item-bx-pager',
        touchEnabled: false,
        onSlideBefore: function($elem, oldIndex, newIndex){
          $('#photo-count .p-from').text(newIndex + 1);
          $('#photo-count .p-to').text(newIndex + 2);
        }
      });
    } 
  }




  // SHOW HIDE HEADER RESPONSIVE SEARCH
  $('#h-search, #rs-cover, .search-filter-head').on('click', function(e){
    e.preventDefault();

    if(!$(this).hasClass('opened') || !$(this).hasClass('closed')) {
      $(this).addClass('closed');
    }

    $(this).toggleClass('opened closed');

    if($('#s-box').hasClass('closed')) {
      $('#s-box').css('width', '70%');
      $('#s-box').animate({right: '0px'}, 500);
    } else {
      $('#s-box').animate({right: '-70%'}, 500, function() { $(this).css('width', '0'); });
    }

    $('#s-box').toggleClass('opened closed');

    $('#rs-cover').fadeToggle(200);
    $('body').toggleClass('no-scroll');
    $('html').toggleClass('no-scroll');
  });



  // SHOW HIDE HEADER RESPONSIVE OPTIONS
  $('#h-options, #ro-cover').on('click', function(e){
    e.preventDefault();

    if(!$(this).hasClass('opened') || !$(this).hasClass('closed')) {
      $(this).addClass('closed');
    }

    $(this).toggleClass('opened closed');


    if($('#o-box').hasClass('closed')) {
      $('#o-box').css('width', '70%');
      $('#o-box').animate({left: '0px'}, 500);
    } else {
      $('#o-box').animate({left: '-70%'}, 500, function() { $(this).css('width', '0'); });
    }

    $('#o-box').toggleClass('opened closed');

    $('#ro-cover').fadeToggle(200);

    $('body').toggleClass('no-scroll');
    $('html').toggleClass('no-scroll');
  });



  // FANCYBOX - USER CHANGE EMAIL
  $(document).on('click', '#user-change-email', function(e){
    e.preventDefault();
    var url = this.href;

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   260,
          'wrapCSS':  'user-change-email-func',
          'closeBtn' : close_btn,
          'type':     'iframe',
          'href':     url
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url,
          baseClass: 'user-change-email-func',
          iframe: {
            css: {
              width : '640px',
              height : '260px',
              padding: 0
            }
          }
        });
      }
    }
  });



  // FANCYBOX - USER CHANGE PASSWORD
  $(document).on('click', '#user-change-password', function(e){
    e.preventDefault();
    var url = this.href;

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   340,
          'wrapCSS':  'user-change-pass-func',
          'closeBtn' : close_btn,
          'type':     'iframe',
          'href':     url
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url,
          baseClass: 'user-change-pass-func',
          iframe: {
            css: {
              width : '640px',
              height : '340px',
              padding: 0
            }
          }
        });
      }
    }
  });



  // FANCYBOX - SEND TO FRIEND FUNCTIONALITY
  $(document).on('click', '#send-friend', function(e){
    e.preventDefault();
    var url = this.href;
    var anchor = '';

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    if (url.indexOf('?') != -1) {
      url += '&';
    } else {
      url += '?';
    }

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   400,
          'wrapCSS':  'send-friend-func',
          'closeBtn' : close_btn,
          'type':     'iframe',
          'href':     url + 'content_type=send_friend' + anchor
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url + 'content_type=send_friend' + anchor,
          baseClass: 'send-friend-func',
          iframe: {
            css: {
              width : '640px',
              height : '400px',
              padding: 0
            }
          }
        });
      }
    }
  });



  // FANCYBOX - POST COMMENT FORM
  $(document).on('click', '.add-com', function(e){
    e.preventDefault();
    var url = this.href;
    var anchor = '';

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    if (url.indexOf('?') != -1) {
      url += '&';
    } else {
      url += '?';
    }

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   460,
          'wrapCSS':  'add-com-func',
          'closeBtn' : close_btn,
          'type':     'iframe',
          'href':     url + 'content_type=add_comment' + anchor
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url + 'content_type=add_comment' + anchor,
          baseClass: 'add-com-func',
          iframe: {
            css: {
              width : '640px',
              height : '460px',
              padding: 0
            }
          }
        });
      }
    }
  });



  // FANCYBOX - PUBLIC PROFILE CONTACT SELLER FORM
  $(document).on('click', '#home-loc-open', function(e){
    e.preventDefault();
    var url = this.href;
    var anchor = '';
    var user_id = $(this).attr('rel');

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    if (url.indexOf('?') != -1) {
      url += '&';
    } else {
      url += '?';
    }

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    400,
          'height':   370,
          'wrapCSS':  'location-select-func',
          'closeBtn' : true,
          'type':     'iframe',
          'href':     url + 'content_type=location_select' + anchor
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url + 'content_type=location_select' + anchor,
          baseClass: 'location-select-func',
          iframe: {
            css: {
              width : '400px',
              height : '370px',
              padding: 0
            }
          }
        });
      }
    }
  });



  // FANCYBOX - PUBLIC PROFILE CONTACT SELLER FORM
  $(document).on('click', '#pub-contact', function(e){
    e.preventDefault();
    var url = this.href;
    var anchor = '';
    var user_id = $(this).attr('rel');

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    if (url.indexOf('?') != -1) {
      url += '&';
    } else {
      url += '?';
    }

    
    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   460,
          'wrapCSS':  'pub-contact-func',
          'closeBtn' : close_btn,
          'type':     'iframe',
          'href':     url + 'content_type=pub_contact&user_id=' + user_id + anchor
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url + 'content_type=pub_contact&user_id=' + user_id + anchor,
          baseClass: 'pub-contact-func',
          iframe: {
            css: {
              width : '640px',
              height : '460px',
              padding: 0
            }
          }
        });
      }
    }
    
  });


  // FANCYBOX - SUBCATEGORY OPEN ON HOMEPAGE
  $(document).on('click', '.open-home-cat', function(e){
    e.preventDefault();

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   320,
          'autoSize': false,
          'autoDimensions': false,
          'wrapCSS':  'home-cat',
          'content':  '<div class="cat-tab">' + $('#ct' + $(this).attr('rel')).html() + '</div>'
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'inline',
          smallBtn: false,
          src: '<div style="width:640px;height:320px;padding:0;"><div class="home-cat"><div class="cat-tab">' + $('#ct' + $(this).attr('rel')).html() + '</div></div></div>'
        });
      }
    }
  });



  // FANCYBOX - UPATE PROFILE PICTURE
  $(document).on('click', '#pict-update', function(e){
    e.preventDefault();

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   460,
          'autoSize': false,
          'autoDimensions': false,
          'closeBtn' : close_btn,
          'wrapCSS':  'pict-func',
          'content':  $('#show-update-picture-content').html()
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'inline',
          smallBtn: false,
          src: '<div style="width:640px;height:460px;padding:0;"><div class="pict-func">' + $('#show-update-picture-content').html() + '</div></div>'
        });
      }
    }
  });



  // FANCYBOX - SHOW USER EMAIL FUNCTIONALITY
  $(document).on('click', '.email-show', function(e){
    e.preventDefault();

    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   240,
          'autoSize': false,
          'autoDimensions': false,
          'closeBtn' : close_btn,
          'wrapCSS':  'email-func',
          'content':  $('#show-email-form-content').html()
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'inline',
          smallBtn: false,
          src: '<div style="width:640px;height:240px;padding:0;"><div class="email-func">' + $('#show-email-form-content').html() + '</div></div>'
        });
      }
    }
  });



  // FANCYBOX - CONTACT SELLER FUNCTIONALITY
  $(document).on('click', '.c-seller', function(e){
    e.preventDefault();
    var url = this.href;
    var anchor = '';
    var user_id = $(this).attr('rel');

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    if (url.indexOf('?') != -1) {
      url += '&';
    } else {
      url += '?';
    }


    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':  0,
          'width':    640,
          'height':   460,
          'wrapCSS':  'item-contact-func',
          'closeBtn' : close_btn,
          'type':     'iframe',
          'href':     url + 'content_type=item_contact&user_id=' + user_id + anchor
        });
      }
    } else { 
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url + 'content_type=item_contact&user_id=' + user_id + anchor,
          baseClass: 'item-contact-func',
          iframe: {
            css: {
              width : '640px',
              height : '460px',
              padding: 0
            }
          }
        });
      }
    }
  });


  // FANCYBOX - CLOSE BUTTON
  $(document).on('click', '.fw-close-button', function(e){
    e.preventDefault();

    if(jqueryVersion == '1') {
      parent.$.fancybox.close();
    } else {
      parent.$.fancybox.close();
    }
  });



  // FANCYBOX - QUICK VIEW FUNCTIONALITY FOR LISTINGS
  $(document).on('click', '.orange-but.open-image:not(.disabled)', function(e){
    e.preventDefault();
    var url = this.href;
    var anchor = '';

    if (url.indexOf('#') != -1) {
      anchor = url.substring(url.indexOf('#'), url.length);
      url = url.substring(0, url.indexOf('#'));
    }

    if (url.indexOf('?') != -1) {
      url += '&';
    } else {
      url += '?';
    }

    
    if(jqueryVersion == '1') {
      if (!!$.prototype.fancybox) {
        $.fancybox({
          'padding':   0,
          'width':     dimNormalWidth,
          'height':    dimNormalHeight,
          'scrolling': 'no',
          'wrapCSS':   'quick-view',
          'type':      'iframe',
          'href':      url + 'content_only=1' + anchor
        });
      }
    } else { 
      dimNormalWidthX = dimNormalWidth.toString() + 'px';
      dimNormalHeightX = dimNormalHeight.toString() + 'px';
      
      if (!!$.prototype.fancybox) {
        $.fancybox.open({
          toolbar : true,
          type: 'iframe',
          src: url + 'content_only=1' + anchor,
          baseClass: 'quick-view',
          iframe: {
            css: {
              width : dimNormalWidthX,
              height : dimNormalHeightX,
              padding: 0
            },
            attr: {
              scrolling: "no"
            }
          }
        });
      }
    }
  });


  // Handle no pictures
  $(document).on('click', '.orange-but.open-image.disabled', function(e){
    e.preventDefault();
    return false;
  });



  if (($(window).width() + scrollCompensate()) > 767) {

    // NICE TOOLTIPS FOR ITEM BUTTONS
    $(function() {
      $('#item-buttons').tooltip({
        position: { my: "center top-58", at: "center" }
      });
    });


    // NICE TOOLTIPS FOR HOME PAGE CATEGORIES
    $(function() {
      $('.open-home-cat').tooltip({
        position: { my: "center top-95", at: "center" }
      });
    });
  
  }



  // WHEN ANY LOCATION IS CHOOSEN, REFRESH COOKIES
  $('#countryId, #regionId, #cityId, #sRegion-side, #sCity-side').change(function(){
    $('.search #sCountry, .search #sRegion, .search #sCity').val('');
    $('#cookie-action-side').val('done');
  });



  // RECAPTCHA WIDTH FIX
  var wi = $('#recaptcha_image').width();
  $('#recaptcha_image, #recaptcha_image img').css('max-width', wi + 'px');




  // SEARCH LIST IMG CLICK - CHANGE SOURCE
  $('.small-img').click(function(){
    // Without fade effect
    //$(this).parent().siblings('.big-img').find('img').attr('src', $(this).find('img').prop('src'));

    // With fade effect
    var small_img_wrapper = $(this).parent();
    var small_img = $(this).find('img');
    var small_img_src = $(this).find('img').prop('src');
    var big_img = $(this).parent().siblings('.big-img').find('img');

    big_img.fadeOut(200, function() { 
      big_img.attr('src', small_img_src);
    }).fadeIn(200);

    small_img_wrapper.find('.small-img').removeClass('selected');
    $(this).addClass('selected');
  });



  // --------------------------------------------------------
  // ADDITIONAL SCRIPTS FOR RESPONSIVE DESIGN: 0 - 1200px
  // --------------------------------------------------------

  if (($(window).width() + scrollCompensate()) <= 1200 || $('body.is_touch').length) {

    // SEARCH PAGE - SORT FUNCTIONALITY
    $('#search-sort .sort-title').click(function(){
      $(this).stop( true, true ).toggleClass('hovered');
      $(this).find('#sort-wrap').stop(true,true).fadeToggle(0);
      $('#search-sort').toggleClass('opened');
    });

    $(document).click(function(event) { 
      if(!$(event.target).closest('#search-sort .sort-title').length) {
        if($('#search-sort .sort-title #sort-wrap').is(":visible")) {
          $('#search-sort .sort-title #sort-wrap').hide();
          $('#search-sort .sort-title').stop( true, true ).toggleClass('hovered');
          $('#search-sort').toggleClass('opened');
        }
      }        
    });



    // ITEM PAGE - REPORT BUTTON
    $('#report').click(function(){
      $(this).stop( true, true ).toggleClass('hovered');
      $(this).find('.cont-wrap').stop(true,true).fadeToggle(0);
      $(this).toggleClass('opened');
    });

    $(document).click(function(event) { 
      if(!$(event.target).closest('#report').length) {
        if($('#report .cont-wrap').is(":visible")) {
          $('#report .cont-wrap').hide();
          $('#report').stop( true, true ).toggleClass('hovered');
          $('#report').toggleClass('opened');
        }
      }        
    });



    // MB TOOL BOX FUNCTIONALITY
    $('#lang-open-box, .top-info').click(function(){
      $(this).stop( true, true ).toggleClass('hovered');
      $(this).find('.mb-tool-wrap').stop(true,true).fadeToggle(0);
      $(this).toggleClass('opened');
    });

    $(document).click(function(event) { 
      if(!$(event.target).closest('#lang-open-box').length) {
        if($('#lang-open-box .mb-tool-wrap').is(":visible")) {
          $('#lang-open-box .mb-tool-wrap').hide();
          $('#lang-open-box').stop( true, true ).toggleClass('hovered');
          $('#lang-open-box').toggleClass('opened');
        }
      }        
    });

    $(document).click(function(event) { 
      if(!$(event.target).closest('.top-info').length) {
        if($('.top-info .mb-tool-wrap').is(":visible")) {
          $('.top-info .mb-tool-wrap').hide();
          $('.top-info').stop( true, true ).toggleClass('hovered');
          $('.top-info').toggleClass('opened');
        }
      }        
    });


    
    // USER LINKS - HEADER
    $('.top-my').click(function(){
      $(this).stop( true, true ).toggleClass('hovered');
      $(this).find('.my-wrap').stop(true,true).fadeToggle(0);
      $(this).toggleClass('opened');
    });

    $(document).click(function(event) { 
      if(!$(event.target).closest('.top-my').length) {
        if($('.top-my .my-wrap').is(":visible")) {
          $('.top-my .my-wrap').hide();
          $('.top-my').stop( true, true ).toggleClass('hovered');
          $('.top-my').toggleClass('opened');
        }
      }        
    });



    // HEADER FUNCTIONALITY
    $('#uniform-sCategory').click(function(){
      $(this).stop( true, true ).toggleClass('hovered');
      $(this).find('#inc-cat-box').stop(true,true).fadeToggle(0);
      $(this).find('#inc-cat-list').css('overflow-y', 'scroll');
      $(this).toggleClass('opened');
    });

    $(document).click(function(event) { 
      if(!$(event.target).closest('#uniform-sCategory').length) {
        if($('#uniform-sCategory #inc-cat-box').is(":visible")) {
          $('#uniform-sCategory #inc-cat-box').hide();
          $('#uniform-sCategory').stop( true, true ).toggleClass('hovered');
          $('#uniform-sCategory').toggleClass('opened');
        }
      }        
    });

  } 



  // --------------------------------------------------------
  // ADDITIONAL SCRIPTS FOR RESPONSIVE DESIGN: 1201px or more
  // --------------------------------------------------------

  if (($(window).width() + scrollCompensate()) > 1200) {

    // HEADER FUNCTIONALITY
    $('#uniform-sCategory').hover(function() {
      $(this).find('#inc-cat-box').stop(true, true).fadeIn(time);
      $(this).find('#inc-cat-list').css('overflow-y', 'scroll');
    }, function() {
      $(this).find('#inc-cat-box').stop(true, true).delay(delay).fadeOut(time);
    });



    // OPTION BUTTON IN HEADER
    $('.top-my').hover(function(){
      $(this).stop( true, true ).addClass('hovered');
      $(this).find('.my-wrap').stop(true,true).fadeIn(200);
      $(this).find('.my-wrap').css('overflow', 'visible');
      $(this).css('z-index', '99999');
      $(this).addClass('hovered');

    }, function(){
      $(this).find('.my-wrap').stop( true, true ).delay(700).fadeOut(200);
      $(this).find('.my-wrap').css('overflow', 'visible');
      $(this).css('z-index', '9999');

      $(this).delay(700).queue(function() { $(this).removeClass('hovered'); $(this).dequeue(); });
    });



    // SORTING FUNCTIONALITY
    $('#search-sort .sort-title').hover(function(){
      $(this).stop( true, true ).addClass('hovered');
      $(this).find('#sort-wrap').stop(true,true).fadeIn(200);
      $(this).find('#sort-wrap').css('overflow', 'visible');
      $(this).css('z-index', '99999');
    }, function(){
      $(this).find('#sort-wrap').stop( true, true ).delay(700).fadeOut(200);
      $(this).find('#sort-wrap').css('overflow', 'visible');
      $(this).css('z-index', '9999');

      $(this).delay(700).queue(function() { $(this).removeClass('hovered'); $(this).dequeue(); });
    });



    // MB TOOL BOX FUNCTIONALITY
    $('#lang-open-box, .top-info').hover(function(){
      $(this).stop( true, true ).addClass('hovered');
      $(this).find('.mb-tool-wrap').stop(true,true).fadeIn(200);
      $(this).find('.mb-tool-wrap').css('overflow', 'visible');
      $(this).css('z-index', '99999');
    }, function(){
      $(this).find('.mb-tool-wrap').stop( true, true ).delay(700).fadeOut(200);
      $(this).find('.mb-tool-wrap').css('overflow', 'visible');
      $(this).css('z-index', '9998');

      $(this).delay(700).queue(function() { $(this).removeClass('hovered'); $(this).dequeue(); });
    });



    // ITEM PAGE - REPORT BUTTON
    $('#report').hover(function(){
      $(this).stop( true, true ).addClass('hovered');
      $(this).find('.cont-wrap').stop(true,true).fadeIn(200);
      $(this).find('.cont-wrap').css('overflow', 'visible');
      $(this).css('z-index', '99999');
      $(this).addClass('hovered');

    }, function(){
      $(this).find('.cont-wrap').stop( true, true ).delay(700).fadeOut(200);
      $(this).find('.cont-wrap').css('overflow', 'visible');
      $(this).css('z-index', '9999');

      $(this).delay(700).queue(function() { $(this).removeClass('hovered'); $(this).dequeue(); });
    });

  }




  // --------------------------------------------------------
  // ADDITIONAL SCRIPTS FOR RESPONSIVE DESIGN: 0 - 767px
  // --------------------------------------------------------

  if (($(window).width() + scrollCompensate()) <= 767) {

    // HEADER OPTIONS BUTTON
    $('.top-my').hover(function(){
      $(this).stop( true, true ).addClass('hovered');
      $(this).find('.my-wrap').stop(true,true).fadeIn(0);
      $(this).find('.my-wrap').css('overflow', 'visible');
      $(this).css('z-index', '99999');
      $(this).addClass('hovered');

    }, function(){
      $(this).find('.my-wrap').stop( true, true ).delay(0).fadeOut(0);
      $(this).find('.my-wrap').css('overflow', 'visible');
      $(this).css('z-index', '9999');

      $(this).delay(0).queue(function() { $(this).removeClass('hovered'); $(this).dequeue(); });
    });



    // ON MOBILE DEVICES WHEN CLICK ON HOMEPAGE CATEGORY, SCROLL TO SUBCATEGORY LIST
    $("#home-cat .top li a").click(function(event) {
      if(event.originalEvent !== undefined) {
        $('html, body').animate({
          scrollTop: $('.cat-box').offset().top
        }, 1000);
      }
    });
  }



  // FANCY BOX FUNCTIONALITY FOR IMAGES ON ITEM PAGE
  if(jqueryVersion == '1') {
    $("a[rel=image_group]").fancybox({
      'padding': 0,
      'openEffect' : 'fade',
      'closeEffect' : 'fade',
      'nextEffect' : 'fade',
      'prevEffect' : 'fade',
      'loop' : false,
      'margin': [20, 20, 55, 20],
      'helpers' : { title : {type : 'inside'} }
    });
  }

  // SEARCH PAGE - FILTER BY ALL / COMPANY / PERSONAL
  $('.user-company-change div').click(function() {
    if($(this).hasClass('all')) {
      $('input#sCompany').val('');
    }

    if($(this).hasClass('individual')) {
      $('input#sCompany').val(0);
    }

    if($(this).hasClass('company')) {
      $('input#sCompany').val(1);
    }

    $('input#cookie-action').val('done');
    $('form.search').submit();
  });



  // RICHEDIT PLUGIN FIX FOR RESPONSIVE DESIGN AND ONE MORE FIX
  if($('.mceLayout').length) {
    $('.mceLayout').css('width', '100%');
  }

  $('a.MCtooltip').click(function(){
    return false;
  });

  $('a.MCtooltip').contents().filter(function(){ return this.nodeType === 3; }).remove();



  $("#tip_close2").click(function(){
    $("#flashmessage").slideUp(200);
  });



  // ADD DELIMITER TO H3 TAG IN SEARCH SIDEBAR, BECAUSE H3 IS ABSOLUTE
  $('#sidebar-search fieldset h3').before('<div class="h3-side-absolute"></div>');


  // PAGINATION FONTAWESOME FIX FOR NEXT, LAST, PREV AND FIRST
  $('.searchPaginationNext').html('<i class="fa fa-angle-right"></i>');
  $('.searchPaginationLast').html('<i class="fa fa-angle-double-right"></i>');
  $('.searchPaginationPrev').html('<i class="fa fa-angle-left"></i>');
  $('.searchPaginationFirst').html('<i class="fa fa-angle-double-left"></i>');


  // USER MENU HIGHLIGHT ACTIVE
  var url = window.location.toString();

  $('.user_account #sidebar li a').each(function(){
    var myHref= $(this).attr('href');
    if( url == myHref) {
      $(this).parent('li').addClass('active');
    }
  });


  if(typeof addQuery != 'undefined') {
    // FOOTER INPUT FUNCTIONALITY - PLACEHOLDER REPLACEMENT
    if($('input[name="add_title"], input[name="alert_email"]').val() == addQuery) {
      $('input[name="add_title"], input[name="alert_email"]').css({'color': '#ccc', 'font-style': 'italic'});
    }
    $('input[name="add_title"], input[name="alert_email"]').click(function(){
      if($('input[name="add_title"], input[name="alert_email"]').val() == addQuery) {
        $('input[name="add_title"], input[name="alert_email"]').val('');
        $('input[name="add_title"], input[name="alert_email"]').css({'color': '', 'font-style': ''});
      }
    });
    $('input[name="add_title"], input[name="alert_email"]').blur(function(){
      if($('input[name="add_title"], input[name="alert_email"]').val() == '') {
        $('input[name="add_title"], input[name="alert_email"]').val(addQuery);
        $('input[name="add_title"], input[name="alert_email"]').css({'color': '#ccc', 'font-style': 'italic'});
      }
    });
  }


  // ADD - EDIT LISTING - ALLOW ONLY DECIMALS IN PRICE INPUT
  //$('.add_item input#price').keypress(function(event) {
  //  if ((event.which != 46 || $(this).val().indexOf('.') != -1) && (event.which < 48 || event.which > 57)) {
  //    event.preventDefault();
  // }
  //});


  // TOP CATEGORIES ARROWS
  function top_cat_move() {
    var pos = 0;
    var prev = $('.left-arrow');
    var next = $('.right-arrow');
    var scroller = $('.top-cat-ul-wrap .ul-box');

    var scroller_width = scroller.width();
    var ul_width = $('#top-cat ul').width();
    var elem_order = Math.max($('.cat-highlight').index('#top-cat ul li') - 2, 0);

    if(scroller_width < ul_width) {
      // RESCALE CATEGORY BOXES
      var elem_show = Math.floor(scroller_width / 130);
      var elem_count = $('#top-cat ul li').length;
      var elem_width = scroller_width / elem_show;
      var ul_width = elem_width * elem_count;

      $('#top-cat ul').css('width', ul_width + 'px');
      $('#top-cat ul li').css('width', (elem_width - 10) + 'px');  // substract margin 5 + 5px
 
      next.fadeIn(200);

      scroller.stop(true, false).animate({scrollLeft: elem_order * elem_width}, 0, 'swing', function(){
        // SHOW LEFT ARROW
        if(parseInt(scroller.scrollLeft()) + scroller_width < ul_width + 10) {
          prev.fadeIn(200);
        }

        // HIDE RIGHT ARROW
        if(parseInt(scroller.scrollLeft()) + scroller_width >= ul_width - 10) {
          next.fadeOut(200);
        }
      });
    }


    // NEXT BUTTON
    next.click(function() {
      var pos = scroller.scrollLeft() + elem_width*2;
      scroller.stop(true, false).animate({scrollLeft: pos}, 300, 'swing', function(){

        // SHOW LEFT ARROW
        if(parseInt(scroller.scrollLeft()) + scroller_width < ul_width + 10) {
          prev.fadeIn(200);
        }

        // HIDE RIGHT ARROW
        if(parseInt(scroller.scrollLeft()) + scroller_width >= ul_width - 10) {
          next.fadeOut(200);
        }
      });
    });


    // PREVIOUS BUTTON
    prev.click(function() { 
      var pos = scroller.scrollLeft() - elem_width*2;
      scroller.stop(true, false).animate({scrollLeft: pos}, 300, 'swing', function(){
 
        // SHOW RIGHT ARROW
        if(parseInt(scroller.scrollLeft()) + scroller_width < ul_width + 10) {
          next.fadeIn(200);
        }

        // HIDE LEFT ARROW
        if(parseInt(scroller.scrollLeft()) <= 10) {
          prev.fadeOut(200);
        }
      });
    });
  }



  // CHECK IF IS TOUCH DEVICE
  function is_touch_device() {
    return true == ("ontouchstart" in window || window.DocumentTouch && document instanceof DocumentTouch);
  }


  
  // IDTABS - MULTI LINE FIX
  function idTabsMultiLine(){
    if( $('ul.tabbernav').height() > 55 ) {
      setTimeout(function(){ 
        $('ul.tabbernav').addClass('multi-line');
      }, 500);

      setTimeout(function(){ 
        $('ul.tabbernav').addClass('multi-line');
      }, 3000);
    }
  }



  // CALCULATE SCROLL WIDTH
  function scrollCompensate() {
    var inner = document.createElement('p');
    inner.style.width = "100%";
    inner.style.height = "200px";

    var outer = document.createElement('div');
    outer.style.position = "absolute";
    outer.style.top = "0px";
    outer.style.left = "0px";
    outer.style.visibility = "hidden";
    outer.style.width = "200px";
    outer.style.height = "150px";
    outer.style.overflow = "hidden";
    outer.appendChild(inner);

    document.body.appendChild(outer);
    var w1 = inner.offsetWidth;
    outer.style.overflow = 'scroll';
    var w2 = inner.offsetWidth;
    if (w1 == w2) w2 = outer.clientWidth;

    document.body.removeChild(outer);

    return (w1 - w2);
  }



  // SCROLL TOP PLUGIN
  if($("#back-top-left").length) {
    $("#back-top-left").hide();
    $(function () {
      $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
          $('#back-top-left').fadeIn();
        } else {
          $('#back-top-left').fadeOut();
        }
      });
      $('#back-top-left a').click(function () {
        $('body,html').animate({
          scrollTop: 0
        }, 800);
        return false;
      });
    });
  }

  if($("#back-top-right").length) {
    $("#back-top-right").hide();
    $(function () {
      $(window).scroll(function () {
        if ($(this).scrollTop() > 100) {
          $('#back-top-right').fadeIn();
        } else {
          $('#back-top-right').fadeOut();
        }
      });
      $('#back-top-right a').click(function () {
        $('body,html').animate({
          scrollTop: 0
        }, 800);
        return false;
      });
    });
  }
});	