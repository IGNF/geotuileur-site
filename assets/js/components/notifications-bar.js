const $ = require("jquery");
global.$ = $;

$(function () {
    $('.notifications-bar-close-btn').each(function () {
        $(this).on('click', function () {
            $(this).closest('.notifications-bar').remove();
            $(".body-wrapper").removeClass("with-notifications-bar");
        });
    });
});
