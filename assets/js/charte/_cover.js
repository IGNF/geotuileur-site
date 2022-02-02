//
// COVER IMG
//
var fn_cover = function () {

    $('.cover').each(function (i, el) {
        var $el = $(el);
        var $img = $el.find('img').eq(0);

        function setBackgroundImage(url) {
            if (!url) {
                return;
            }
            $el.css('background-image', 'url(' + url + ')');
        }
        setBackgroundImage($img.attr('src'));
    });

};