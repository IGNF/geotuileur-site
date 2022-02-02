//
// Launch youtube player on click
//
// see: https://www.labnol.org/internet/light-youtube-embeds/27941/?_ga=2.182400295.1672380290.1540981837-952603589.1540981837

function createIframe() {
    var button = this;
    var buttonParent = button.closest(".o-youtube-player");
    var buttonDisclaimer = buttonParent.getElementsByClassName("o-youtube-player__disclaimer")[0];

    console.log(buttonDisclaimer)

    var iframe = document.createElement("iframe");
    var embed = "https://www.youtube-nocookie.com/embed/ID?autoplay=1";
    iframe.setAttribute("src", embed.replace("ID", buttonParent.dataset.id));
    iframe.setAttribute("title", buttonParent.dataset.iframeTitle);

    button.closest(".o-youtube-player__content").appendChild(iframe);
    button.closest(".o-youtube-player__content").removeChild(button);
    buttonParent.removeChild(buttonDisclaimer);
}

document.addEventListener("DOMContentLoaded", function() {
    var n,
        v = document.getElementsByClassName("o-youtube-player");

    for (n = 0; n < v.length; n++) {
        v[n].querySelector('.o-youtube-player__btn').onclick = createIframe;
    }
});