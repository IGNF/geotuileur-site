const $ = require("jquery");
global.$ = $;

$(function () {
    const tree = $("#file-tree").data("tree");
    $("#file-tree").append(displayTree(tree));
});

function displayTree(tree) {
    var html = "";

    tree.map((item) => {
        if (item.type == "directory") {
            html += '<div class="row ml-1">';
            html += '<div class="col">';
            html += `<strong><span class="icons-folder"></span> ${item.name}</strong>`;
            html += `${displayTree(item.children)}`;
            html += "</div>";
            html += "</div>";
        } else if (item.type == "file") {
            html += '<div class="row ml-1">';
            html += '<div class="col">';
            html += `<span class="icons-file"></span> ${item.name}`;
            html += "</div>";
            html += "</div>";
        } else {
            console.warn(item);
        }
    });

    return html;
}
