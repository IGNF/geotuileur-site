import { removeDiacritics } from '../utils'
import KeywordsManager from '../components/keywords';

var keywordsManager = new KeywordsManager();;


$(function () {
    const emptyValue = '__________';

    // Filtrage des caracteres (pas de caracteres speciaux)
    $('#publish_pyramid_name').on('keypress', function (e) {
        let key = String.fromCharCode(!e.charCode ? e.which : e.charCode);
        if (! /[A-Za-z0-9_\-\.]/i.test(key)) {
            e.preventDefault();
            return false;
        }
        return true;
    });

    $('#publish_pyramid_name').on('input', function () {
        let tmsUrl = $(this).data('tms-url');

        let val = $(this).val();
        let nice = emptyValue;
        if (val) {
            nice = removeDiacritics(val).replace(/ /g, "_");
        }
        // Fix: url in datastore endpoints does not contain version and trailing slash (?!)
        let value = `${tmsUrl}/1.0.0/${nice}/{z}/{x}/{y}.pbf`;
        $('#publish_pyramid_address_preview').val(value);
    });

    // Uniquement dans le cas de la modification d'une publication
    let keywords = $('#keywords').attr('data-initial-keywords');   // Retourne une chaine contrairement a $('#keywords').data('initial-keywords') => Array
    if (keywords) {
        $('#keywords').val(keywords);    
    }

    keywordsManager.initialize()
        .then(result => {
            if ('ERROR' == result.status) {
                // TODO Flash message
            }
        });

    // Mise a jour du champ cache lors de la soumission du formulaire
    $('form[name="publish_pyramid"]').on('submit', () => {
        let keywords = keywordsManager.keywords;
        $('#publish_pyramid_keywords').val(keywords);

        return true;
    });
});
