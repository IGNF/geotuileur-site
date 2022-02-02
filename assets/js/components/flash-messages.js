const $ = require("jquery");
global.$ = $;

const flashDiv = $("#flash-messages");

$(function () {
    let flashChildren = flashDiv.children();
    for (let i = 0; i < flashChildren.length; i++) {
        setClearTimer(flashChildren[i]);
    }
});

function flashClearAll() {
    flashDiv.empty();
}

function flashClear(flash) {
    flash.remove();
}

function setClearTimer(flash) {
    setTimeout(flashClear, 10000, flash);
}

function flashAdd(message, type) {
    let divClass = "alert";

    switch (type) {
        case "error":
            divClass += " alert-danger";
            break;

        case "warning":
            divClass += " alert-warning";
            break;

        case "notice":
            divClass += " alert-info";
            break;

        case "success":
            divClass += " alert-success";
            break;

        default:
            divClass += " alert-danger";
            break;
    }

    let flash = $("<div></div>").text(message).addClass(divClass);
    flashDiv.append(flash);
    setClearTimer(flash);
}

module.exports = {
    flashAdd: flashAdd,
};
