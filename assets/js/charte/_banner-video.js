//
// Banner VIDEO cover
//

// Video paused when out of viewport
// See: http://morr.github.io/appear.html

// ON: Appear
var appearVideo = function (e, elt) {
    var video = $(elt).get(0);
    var $videoBtn = $(this).parents('.m-media-background').siblings('.m-media-background__btn');

    if (video.paused && $(elt).data('status') === "playing") {
        video.play();
        //
        $videoBtn.addClass('is-pause').removeClass('is-play').attr('title', 'Mettre en pause la vidéo en fond');
        $videoBtn.children('.icon').addClass('icon-pause').removeClass('icon-play');
        $videoBtn.children('.sr-only').html('Mettre en pause la vidéo en fond');
    }
};
// OFF: Disappear
var disappearVideo = function (e, elt) {
    var video = $(elt).get(0);
    var $videoBtn = $(this).parents('.m-media-background').siblings('.m-media-background__btn');

    if (!video.paused) {
        video.pause();
        //
        $videoBtn.addClass('is-play').removeClass('is-pause').attr('title', 'Lancer la vidéo en fond');
        $videoBtn.children('.icon').addClass('icon-play').removeClass('icon-pause');
        $videoBtn.children('.sr-only').html('Lancer la vidéo en fond');
    }

};

var fn_banner_video = function () {
    if ($('.m-media-background').length > 0) {

        // Adding class ".desktop" to activate video injection only on desktop browser
        $('.m-media-background').each(function () {
            $(this).find('video').appear();
            $(this).find('video').on('appear', appearVideo).on('disappear', disappearVideo);
        });

        // Video button play / pause
        var pauseButton = $(".m-media-background__btn");

        pauseButton.on('click', function () {
            var pausableVideo = $(this).siblings(".m-media-background").find("video");
            var pausableVideoElement = pausableVideo.get(0);
            var status = "pause";

            if (pausableVideoElement.paused) {
                status = 'play';

                $(this).addClass('is-pause').removeClass('is-play').attr('title', 'Mettre en pause la vidéo en fond');
                $(this).children('.icon').addClass('icon-pause').removeClass('icon-play');
                $(this).children('.sr-only').html('Mettre en pause la vidéo en fond');
            } else {
                $(this).addClass('is-play').removeClass('is-pause').attr('title', 'Lancer la vidéo en fond');
                $(this).children('.icon').addClass('icon-play').removeClass('icon-pause');
                $(this).children('.sr-only').html('Lancer la vidéo en fond');
            }

            pausableVideoElement[pausableVideoElement.paused ? 'play' : 'pause']();
            pausableVideo.data('status', pausableVideoElement.paused ? "pausing" : "playing");
        });

        // modernizr touchevent
        if (Modernizr.touchevents) {
            // touchevents supported
            // On simule un click sur le bouton play/pause pour lancer la video
            // car pas d'auto-play sur iOS
            pauseButton.trigger('click');
        }

        // prefers-reduced-motion
        // https://www.viget.com/articles/best-practices-for-background-videos/
        if (window.matchMedia('(prefers-reduced-motion)').matches) {
            $('.m-media-background video').each(function () {
                $(this).removeAttribute('autoplay');
                disappearVideo();
            });
        }

    }
};