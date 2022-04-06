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

function flashClearAll() {
    flashDiv.empty();
}

function flashClear(flash) {
    flash.remove();
}

function flashAdd(message, type) {
    let divClass = "alert flash-message";

    switch (type) {
        case "error":
            divClass += " flash-message-danger";
            break;

        case "warning":
            divClass += " flash-message-warning";
            break;

        case "notice":
            divClass += " flash-message-info";
            break;

        case "success":
            divClass += " flash-message-success";
            break;

        default:
            divClass += " flash-message-danger";
            break;
    }

    const btnClose = $('<button class="flash-message-close-btn mx-2"><i class="icon-close"></i></button>')

    let flash = $("<div></div>").text(message).addClass(divClass);
    flash.append(btnClose);
    flashDiv.append(flash);

    btnClose.on('click', function () {
        $(this).closest('.flash-message').remove();
    })
}

module.exports = {
    flashAdd: flashAdd,
};
