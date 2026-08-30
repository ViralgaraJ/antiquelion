$(document).ready(function(){
 
  // SHOW POLL STATS
  $('body').on('change', 'select.pol-stat-id', function(e){
    e.preventDefault();

    window.location.replace($(this).attr('rel') + "&poll_id=" + $(this).val());
  });


  // ADD VALUE
  $('body').on('click', '.mb-val-footer a.add', function(e){
    e.preventDefault();
    var elem = $(this);
    var pollId = $(this).attr('data-poll-id');
     
    if(pollId > 0) {
      $(this).find('i').removeClass('fa-plus-circle').addClass('fa-cog fa-spin');

      $.ajax({
        url: pol_add_value_url + pollId,
        type: "GET",
        success: function(response){
          //console.log(response);
          elem.closest('.mb-field').find('ol.sortable').append(response);
          elem.find('i').removeClass('fa-cog fa-spin').addClass('fa-plus-circle');

          elem.closest('.mb-field').find('ol.sortable').animate({ scrollTop: elem.closest('.mb-field').find('ol.sortable li').last().offset().top }, 0);
          elem.closest('.mb-field').find('.mb-values .mb-val-empty').hide(0);

          pol_message(pol_message_ok, 'ok');
        },
        error: function(response) {
          pol_message(pol_message_error, 'error');
          console.log(response);
        }
      });
    }
  });



  // REMOVE VALUE
  $('body').on('click', '.mb-val .remove', function(e){
    e.preventDefault();
    var elem = $(this);
    var removeId = $(this).attr('data-id');
     
    if(removeId > 0) {
      $(this).find('i').removeClass('fa-times').addClass('fa-cog fa-spin');

      $.ajax({
        url: pol_remove_value_url + removeId,
        type: "GET",
        success: function(response){
          elem.closest('.mb-val').fadeOut(200, function() {
            if(elem.closest('.mb-field').find('.mb-values ol li').length <= 1) {
              elem.closest('.mb-field').find('.mb-values .mb-val-empty').show(0);
            }

            elem.closest('.mb-val').remove();
          });


          pol_message(pol_message_ok, 'ok');
        },
        error: function(response) {
          pol_message(pol_message_error, 'error');
          console.log(response);
        }
      });
    }
  });


  // REMOVE NEW VALUE CLASS
  $('body').on('click', '.mb-val-new', function(){
    $(this).removeClass('mb-val-new');
  });


  // HIDE MESSAGE
   $('body').on('click', '.mb-message-js', function(e){
     e.preventDefault();
     $('.mb-message-js > div').fadeOut(300, function() {
       $('.mb-message-js > div').remove();
     });
  });
   
  

  // SHOW-HIDE FIELDS
  $('body').on('click', '.mb-field .mb-top-line', function(e){
    e.preventDefault();
    $(this).closest('.mb-field').toggleClass('opened');
    $(this).closest('.mb-field').find('.mb-details, .mb-foot').fadeToggle(0);
    $(this).closest('.mb-field').find('.mb-top-line .show i').toggleClass('fa-angle-down fa-angle-up');
  });



  // CATEGORY MULTI SELECT
  $('body').on('change', '.mb-row-select-multiple select', function(e){
    $(this).closest('.mb-row-select-multiple').find('input[type="hidden"]').val($(this).val());
  });



  // ON LOCALE CHANGE RELOAD PAGE
  $('body').on('change', 'select.mb-select-locale', function(e){
    window.location.replace($(this).attr('rel') + "&polLocale=" + $(this).val());
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

function pol_message($html, $type = '') {
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
