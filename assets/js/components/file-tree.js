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
            html += `<strong><i class="fas fa-folder-open"></i> ${item.name}</strong>`;
            html += `${displayTree(item.children)}`;
            html += "</div>";
            html += "</div>";
        } else if (item.type == "file") {
            html += '<div class="row ml-1">';
            html += '<div class="col">';
            html += `<i class="far fa-file"></i> ${item.name}`;
            html += "</div>";
            html += "</div>";
        } else {
            console.log(item);
        }
    });

    return html;
}
