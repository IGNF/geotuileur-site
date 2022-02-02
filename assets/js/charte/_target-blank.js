var fn_target_blank = function () {

    // add icon external link to target="_blank"
    // add (nouvelle fenêtre) in title
    var iconExt = '<span class="icon-external-link" aria-hidden="true"></span>';
    var targetBlank = $('[target="_blank"]:not(.btn-icon--header)');

    targetBlank.each(function(index) {
        $(this).addClass('external-link').append(iconExt);
        if ($(this).attr("title"))
        {
            var title = $(this).attr("title").replace(/\s+/g, " ").trim();
            $(this).attr("title", function(){ return title + " (Nouvelle fenêtre)" });
        } else {
            $(this).attr("title", function(){ return $(this).text().replace(/\s+/g, ' ').trim() + " (Nouvelle fenêtre)" });
        }
    });

};

export default {
    fn_target_blank
};