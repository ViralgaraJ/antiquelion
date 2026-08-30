$(document).ready(function() {

  // CLOSE POLL
  $('body').on('click', '.pol-close, .pol-success', function(e){
    $('.pol-inside').fadeOut(100);

    // Save to cookies
    $.post(window.location.href, { pollClose: "1" }, function(response){ });
  });


  // SHOW POLL
  $('body').on('click', '.pol-header', function(e){
    $('.pol-inside').fadeToggle(100);

    // Save to cookies
    $.post(window.location.href, { pollOpen: "1" }, function(response){ });
  });



  // SUBMIT POLL
  $('body').on('click', '.pol-btn', function(e){
    e.preventDefault();
    
    $.ajax({
      url: $('form#pol-form').attr('action'),
      type: "POST",
      data: $('form#pol-form').find(':input[value!=""]').serialize(),
      success: function(response){
        console.log('Successful!');
        console.log(response);
      },
      error: function(response){
        console.log('Error!');
        console.log(response);
      }
    });

    // Show success box
    $('.pol-wrap').fadeOut(100, 'linear', function() {
      $('.pol-success').fadeIn(100);
    });
  });


  // ENSURE RADIO BUTTONS BEHAVES LIKE RADIO
  $('body').on('change', '.pol-t-radio input[type=radio]', function(){
    $(this).closest('.pol-t-radio').find('input[type=radio]:checked').not(this).prop('checked', false);
  });


  // ENABLE/DISABLE SUBMIT BUTTON
  $('body').on('change', '.pol-values input', function(){
    if($('.pol-values input:checked').length > 0) {
      $('.pol-values button').attr('disabled', false);
      $('.pol-empty').fadeOut(150);

    } else {
      $('.pol-values button').attr('disabled', true);
      $('.pol-values button').attr('disabled', 'disabled');
      $('.pol-empty').fadeIn(150);
    }
  });


  // CREATE TOOLTIPS
  if(typeof $.fn.Tipped !== 'undefined') { 
    Tipped.create('.pol-has-tooltip', { maxWidth: 200, radius: false, behavior: 'hide', position: 'topleft'});
  }
});



