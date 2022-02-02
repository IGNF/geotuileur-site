require ('overlayscrollbars/js/jquery.overlayScrollbars.js');

var fn_overlayScrollbars = function () {

    $("#menuLeftDesktop").overlayScrollbars({
        scrollbars : {
            autoHide: "leave"
        }
    });

};

export default {
    fn_overlayScrollbars
}