const $ = require("jquery");
global.$ = $;

const flashDiv = $("#flash-messages");

$(function () {
    $('.flash-message-close-btn').each(function () {
        $(this).on('click', function () {
            $(this).closest('.flash-message').remove();
        });
    });
});

function flashAdd(message, type) {
    let divClass = "flash-message";
    let iconClass = "icons-status"; 

    switch (type) {
        case "error":
            divClass += " flash-message-danger";
            iconClass = "icons-alert";
            break;

        case "warning":
            divClass += " flash-message-warning";
            break;

        case "notice":
            divClass += " flash-message-info";
            break;

        case "success":
            divClass += " flash-message-success";
            iconClass = "icon-check-circle"
            break;

        default:
            divClass += " flash-message-danger";
            iconClass = "icons-alert";
            break;
    }

    const btnClose = $('<button class="flash-message-close-btn" role="button" title="Fermer" aria-label="Fermer"><i class="icon-close"></i></button>')

    let flash = $('<div role="alert"></div>').addClass(divClass);
    let icon = $('<span></span>').addClass("flash-message-icon").addClass(iconClass);
    flash.append(icon);
    let content = $("<div></div>").addClass("flash-message-content").text(message)
    flash.append(content);
    flash.append(btnClose);
    flashDiv.append(flash);

    btnClose.on('click', function () {
        $(this).closest('.flash-message').remove();
    })
}

module.exports = {
    flashAdd: flashAdd,
};
