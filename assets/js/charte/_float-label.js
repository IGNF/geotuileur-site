//
// FLOAT LABEL
// src: http://jsfiddle.net/RyanWalters/z9ymd852/
//
var fn_float_label = function () {

    $('.float-target-parent .form-control').on('focus blur', function (e) {
        $(this).parents('.float-target-parent').toggleClass('focused', (e.type === 'focus' || this.value != ''));
    });
  
    $('.float-target-parent .form-control').each(function () {
        if($(this).val() != "" || $(this).attr('placeholder')) {
            $(this).parents('.float-target-parent').addClass('focused');
        }
    });
  
  };

  export default {
      fn_float_label
  }