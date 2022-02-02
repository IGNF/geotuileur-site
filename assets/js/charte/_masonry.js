var fn_masonry = function () {

    var $grid = $('.o-socialwall').masonry({
        itemSelector: '.o-socialwall__item',
        columnWidth: '.o-socialwall__item',
        percentPosition: true
    });

    // layout Masonry after each image loads
    $grid.imagesLoaded().progress( function() {
        $grid.masonry('layout');
    });

};