$(function () {
    $('.notifications-bar-close-btn').each(function () {
        $(this).on('click', function () {
            $(this).closest('.notifications-bar').remove();
        });
    });
});
