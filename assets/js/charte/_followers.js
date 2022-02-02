// Mise à jour du nomnbre de followers à partir du followers.json public
// Ajouté pour l'Espace collaboratif (pas présent dans les js de la charte)
var fn_update_followers = function () {

    var url = $("#block-portalsocialnetworksblock").data("url");
    
    if (!url) {
        return;
    }

    $.ajax({
        url: url,
        type: "GET",
        async: false,
        data: null
    })
    .done(function(results) {
        $("#facebook-followers").text(results.facebook);
        $("#twitter-followers").text(results.twitter);
        $("#linkedin-followers").text(results.linkedin);
        $("#instagram-followers").text(results.instagram);
    }).fail(function() {
        // Rien de particulier à faire, on laisse les nombres d'abonnés par défaut
    });
};

export default {
    fn_update_followers
}