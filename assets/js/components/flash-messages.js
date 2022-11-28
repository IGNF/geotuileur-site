const $ = require("jquery");
global.$ = $;

const flashDiv = $("#flash-messages");

$(function () {
    // ajout listener sur les flash_messages ajouté au niveau de Symfony
    $('.flash-message-close-btn').each(function () {
        $(this).on('click', function () {
            $(this).closest('.flash-message').remove();
        });
    });
});

/**
 * @returns {JQuery<HTMLElement>}
 */
function flashAdd(message, type, isHtml = false, autohide = false) {
    let divClass = "flash-message";
    let iconClass = "icons-status";

    switch (type) {
        case "warning":
            divClass += " flash-message-warning";
            iconClass = "icons-status text-warning";
            break;

        case "notice":
        case "info":
            divClass += " flash-message-info";
            iconClass = "icons-status";
            break;

        case "success":
            divClass += " flash-message-success";
            iconClass = "icon-check-circle"
            break;

        case "error":
        case "danger":
        case "failure":
        case "fail":
        default:
            divClass += " flash-message-danger";
            iconClass = "icons-alert";
            break;
    }

    const btnClose = $('<button class="flash-message-close-btn" role="button" title="Fermer" aria-label="Fermer"><i class="icon-close"></i></button>')

    let flash = $('<div role="alert"></div>').addClass(divClass);
    let icon = $('<span></span>').addClass("flash-message-icon").addClass(iconClass);
    flash.append(icon);

    let content = $("<div></div>").addClass("flash-message-content")

    if (isHtml) {
        content.html(message)
    } else {
        content.text(message)
    }

    flash.append(content);
    flash.append(btnClose);
    flashDiv.append(flash);

    btnClose.on('click', function () {
        $(this).closest('.flash-message').remove();
    })

    if (autohide) {
        setTimeout(() => {
            flash.remove()
        }, 10000);
    }

    return flash;
}

module.exports = {
    flashAdd: flashAdd,
};
