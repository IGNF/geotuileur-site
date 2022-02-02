$(function() {

    // update hash of parent when iframe hash changes
    function load(obj) {
        var innerWindow = obj.contentWindow;
        innerWindow.addEventListener('hashchange', function() {
            window.location.hash = innerWindow.location.hash;
        });
    }

    // create iframe to embed the doc page
    // doc page and markdown contents have been copied 
    // in public directory by webpack
    let hash = window.location.hash;
    let iframe = $('<iframe>')
        .attr({
            'name': 'doc-iframe',
            'src': './build/docs/index.html' + hash,
            'frameborder': 0,
            'style': 'height:calc(100vh - 7em); overflow:hidden;'
        })
        .on('load', function(){
            load(this);
        });

    $("#doc-container").append(iframe);

});