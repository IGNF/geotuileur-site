var fn_table = function () {

    $('.wysiwyg table').removeAttr('border cellpadding cellspacing style')
        .wrap("<div class='table-responsive'></div>")
        .addClass('table table-bordered')
        .find('thead').addClass('thead-light');

};