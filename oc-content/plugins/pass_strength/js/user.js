$(document).ready(function() {
  var selector = 'form[name="register"] input[name="s_password"]';
  
  var inp = $(selector);
  // var box = inp.closest('div');
  var box = inp.parent();

  var percent = pss_pass_strength(inp.val());
  box.append(pss_color_box());
  
  // On keypress calculate pass strength
  $('body').on('keyup', selector, function() {
    var percent = pss_pass_strength($(this).val());
    pss_update_color_box(percent);
  });
  
  
  // On click show pass strength bar
  $('body').on('click', selector, function() {
    box.find('.pss-pass-box').fadeIn(300);
  });
});


// Create color box. Enter percent as value 0 - 100
function pss_color_box(percent = 0) {
  percent = parseInt(percent);
  var label = pss_pass_strength_label(percent);
  var dataClass = pss_pass_strength_class(percent);
  
  // var html = '<div class="pss-pass-box" style="display:none;" data-class="' + dataClass + '"><div class="pss-pass-val">' + label + '</div><div class="pss-pass-bar" style="width:' + percent + '%"></div></div>';
  var html = '<div class="pss-pass-box" style="display:none;" data-class="' + dataClass + '"><div class="pss-pass-val">' + label + '</div><div class="pss-pass-bar"></div></div>';
  
  return html;
}


// Update color box
function pss_update_color_box(percent = 0) {
  percent = parseInt(percent);
  var label = pss_pass_strength_label(percent);
  var dataClass = pss_pass_strength_class(percent);

  $('.pss-pass-box').attr('data-class', dataClass);
  $('.pss-pass-box .pss-pass-val').text(label);
  //$('.pss-pass-box .pss-pass-bar').css('width', percent + '%');
}


// Recalculate password strength
function pss_pass_strength(password) {
  if(typeof password !== 'string' || password.length === 0) {
    return 0; // Return 0 for invalid input
  }

  let strength = 0;

  // Criteria for password strength
  const lengthBonus = Math.min(password.length, 25) * 2.5; // Max 20 chars for bonus
  const hasLowercase = /[a-z]/.test(password) ? 10 : 0;
  const hasUppercase = /[A-Z]/.test(password) ? 15 : 0;
  const hasNumbers = /[0-9]/.test(password) ? 15 : 0;
  const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password) ? 15 : 0;

  // Check for diversity in characters
  const uniqueChars = new Set(password).size;
  const diversityBonus = Math.min(uniqueChars, 10) * 1.5; // Max 10 unique chars for bonus

  // Deduct points for weak characteristics
  const repeatPenalty = /(.)\1{2,}/.test(password) ? -10 : 0; // Penalty for repeated characters
  const commonWords = ["password", "123456", "qwerty", "letmein"];
  const commonWordPenalty = commonWords.some(word => password.toLowerCase().includes(word)) ? -20 : 0;

  // Calculate total strength
  strength += lengthBonus + hasLowercase + hasUppercase + hasNumbers + hasSpecial + diversityBonus;
  strength += repeatPenalty + commonWordPenalty;

  if(password.length <= 5) {
    strength = strength/3;
    
  } else if (password.length <= 7) {
    strength = strength/2;
    
  } else if (password.length <= 9) {
    strength = strength*2/3;
  }
  
  // Ensure strength is within 0 to 100
  return Math.max(0, Math.min(100, strength));
}


// Get password strength label
function pss_pass_strength_label(percent) {
  var dataClass = pss_pass_strength_class(percent);
  
  if(pssLabels !== undefined && pssLabels[dataClass] !== undefined) {
    return pssLabels[dataClass];
  }
  
  return dataClass; 
}


// Get password strength label
function pss_pass_strength_class(percent) {
  if(percent <= 20) {
    return 'veryweak'; 
    
  } else if (percent <= 40) {
    return 'weak'; 
    
  } else if (percent <= 60) {
    return 'fair'; 
    
  } else if (percent <= 80) {
    return 'strong'; 

  } else {
    return 'verystrong'; 
  }
}