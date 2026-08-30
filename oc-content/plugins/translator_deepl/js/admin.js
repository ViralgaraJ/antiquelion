$(document).ready(function(){
  
  // AUTO-SUBMIT SEARCH
  $('body').on('change', '#mb-search-table input[type="text"], #mb-search-table select', function(e){
    $(this).closest('form').submit();
  });


  // CHANGE PROPERTY VALUES
  $('body').on('click', '#mb-prop-val .mb-val-types a', function(e){
    if(!$(this).hasClass('mb-active')) {
      var id = $(this).attr('data-id');
      
      $(this).siblings('a').removeClass('mb-active');
      $(this).addClass('mb-active');
      
      $(this).closest('.mb-val-types').siblings('.mb-val-list, .mb-val-foot').removeClass('mb-active');
      $(this).closest('.mb-val-types').siblings('.mb-val-list[data-id="' + id + '"], .mb-val-foot[data-id="' + id + '"]').addClass('mb-active');
    }
  });
  
  
  // SWITCH LOCALE
  $('body').on('click', '.mb-switch-locale', function(e){
    e.preventDefault();
    var locale = $(this).attr('data-locale');
    
    $(this).closest('form').find('input[name="capLocale"]').val(locale);
    
    $(this).parent().find('.mb-switch-section').removeClass('active');
    $(this).addClass('active');
    
    $(this).closest('#mb-dbox').find('.mb-switch-locale').removeClass('active');
    $(this).closest('#mb-dbox').find('.mb-switch-locale[data-locale="' + locale + '"]').addClass('active');
    
    $(this).closest('#mb-dbox').find('.mb-locale-wrap').removeClass('active');
    $(this).closest('#mb-dbox').find('.mb-locale-wrap[data-locale="' + locale + '"]').addClass('active');
  });

 
 
   // COLOR PICKER
  $('body').on('change', '.mb-color-box input[type="text"]', function() {
    $(this).closest('.mb-color-box').find('input[type="color"]').val($(this).val());
  });


  $('body').on('change', '.mb-color-box input[type="color"]', function() {
    $(this).closest('.mb-color-box').find('input[type="text"]').val($(this).val());
  });


  // CATEGORY MULTI SELECT
  $('body').on('change', '.mb-row-select-multiple select', function(e){
    $(this).closest('.mb-row-select-multiple').find('input[type="hidden"]').val($(this).val());
  });


  // ON LOCALE CHANGE RELOAD PAGE
  $('body').on('change', 'select.mb-select-locale', function(e){
    window.location.replace($(this).attr('rel') + "&capLocale=" + $(this).val());
  });


  // HELP TOPICS
  $('#mb-help > .mb-inside > .mb-row.mb-help > div').each(function(){
    var cl = $(this).attr('class');
    $('label.' + cl + ' span').addClass('mb-has-tooltip').prop('title', $(this).text());
  });

  $('.mb-row label').click(function() {
    var cl = $(this).attr('class');
    var pos = $('#mb-help > .mb-inside > .mb-row.mb-help > div.' + cl).offset().top - $('.navbar').outerHeight() - 12;;
    $('html, body').animate({
      scrollTop: pos
    }, 1400, function(){
      $('#mb-help > .mb-inside > .mb-row.mb-help > div.' + cl).addClass('mb-help-highlight');
    });

    return false;
  });


  // ON-CLICK ANY ELEMENT REMOVE HIGHLIGHT
  $('body, body *').click(function(){
    $('.mb-help-highlight').removeClass('mb-help-highlight');
  });


  // GENERATE TOOLTIPS
  Tipped.create('.mb-has-tooltip', { maxWidth: 200, radius: false });
  Tipped.create('.mb-has-tooltip-user', { maxWidth: 350, radius: false, size: 'medium' });
  Tipped.create('.mb-has-tooltip-light', { maxWidth: 200, radius: false, size: 'medium' });


  // CHECKBOX & RADIO SWITCH
  $.fn.bootstrapSwitch.defaults.size = 'small';
  $.fn.bootstrapSwitch.defaults.labelWidth = '0px';
  $.fn.bootstrapSwitch.defaults.handleWidth = '50px';

  $(".element-slide").bootstrapSwitch();



  // MARK ALL
  $('input.mb_mark_all').click(function(){
    if ($(this).is(':checked')) {
      $('input[name^="' + $(this).val() + '"]').prop( "checked", true );
    } else {
      $('input[name^="' + $(this).val() + '"]').prop( "checked", false );
    }
  });


});


var timeoutHandle;

function cap_message($html, $type = '') {
  window.clearTimeout(timeoutHandle);

  $('.mb-message-js').fadeOut(0);
  $('.mb-message-js').attr('class', '').addClass('mb-message-js').addClass($type);
  $('.mb-message-js').fadeIn(200).html('<div>' + $html + '</div>');

  var timeoutHandle = setTimeout(function(){
    $('.mb-message-js > div').fadeOut(300, function() {
      $('.mb-message-js > div').remove();
    });
  }, 10000);
}
