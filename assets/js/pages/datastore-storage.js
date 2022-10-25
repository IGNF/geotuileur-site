import axios from 'axios';
import { niceBytes, Wait } from '../utils'
import flash from '../components/flash-messages'

let datastoreId = null;
const wait = new Wait({ iconClass: "icon-timer" });

$(function () {
    datastoreId = $('#datastoreId').data('datastore-id');

    $('.nice-bytes').each(function () {
        let niceB = niceBytes(parseInt($(this).text()));
        $(this).text(niceB);
        $(this).removeClass('hidden')
    });

    $('.btn-pyramid-unpublish').each(function () {
        $(this).on('click', function (e) {
            e.preventDefault();

            let url = Routing.generate('plage_pyramid_unpublish_ajax', {
                datastoreId: datastoreId,
                pyramidId: $(this).data('pyramid-id')
            })
            wait.show();
            axios.post(url)
                .then(() => {
                    $(this).closest('.row').remove();
                    flash.flashAdd(`La pyramide ${$(this).data('pyramid-id')} a été dépubliée avec succès`, 'success')
                }).catch(error => {
                    console.error(error.response);
                    flash.flashAdd(`La dépublication de la pyramide ${$(this).data('pyramid-id')} a échoué`, 'error')
                }).finally(() => {
                    wait.hide();
                })
        });
    });

    $('.btn-pyramid-delete-published').each(function () {
        $(this).on('click', function (e) {
            e.preventDefault();

            let url = Routing.generate('plage_pyramid_delete_published_ajax', {
                datastoreId: datastoreId,
                pyramidId: $(this).data('pyramid-id')
            })
            wait.show();
            axios.post(url)
                .then(() => {
                    $(this).closest('.row').remove();
                    flash.flashAdd(`La pyramide ${$(this).data('pyramid-id')} a été supprimée avec succès`, 'success')
                }).catch(error => {
                    console.error(error.response);
                    flash.flashAdd(`La suppression de la pyramide ${$(this).data('pyramid-id')} a échoué`, 'error')
                }).finally(() => {
                    wait.hide();
                })
        });
    });

    $('.btn-stored-data-delete').each(function () {
        $(this).on('click', function (e) {
            e.preventDefault();

            let url = Routing.generate('plage_stored_data_delete_ajax', {
                datastoreId: datastoreId,
                storedDataId: $(this).data('stored-data-id')
            })
            wait.show();
            axios.post(url)
                .then(() => {
                    $(this).closest('.row').remove();
                    flash.flashAdd(`La donnée ${$(this).data('stored-data-id')} a été supprimée avec succès`, 'success')
                }).catch(error => {
                    console.error(error.response);
                    flash.flashAdd(`La dépublication de la donnée ${$(this).data('stored-data-id')} a échoué`, 'error')
                }).finally(() => {
                    wait.hide();
                })
        });
    })

    $('.btn-upload-delete').each(function () {
        $(this).on('click', function (e) {
            e.preventDefault();

            let url = Routing.generate('plage_upload_delete_ajax', {
                datastoreId: datastoreId,
                uploadId: $(this).data('upload-id')
            })
            wait.show();
            axios.post(url)
                .then(() => {
                    $(this).closest('.row').remove();
                    flash.flashAdd(`La donnée déposée ${$(this).data('upload-id')} a été supprimée avec succès`, 'success')
                }).catch(error => {
                    console.error(error.response);
                    flash.flashAdd(`La suppression de la donnée déposée ${$(this).data('upload-id')} a échoué`, 'error')
                }).finally(() => {
                    wait.hide();
                })
        });
    });

    $('.btn-annexe-delete').each(function () {
        $(this).on('click', function (e) {
            e.preventDefault();

            let url = Routing.generate('plage_annexe_delete_ajax', {
                datastoreId: datastoreId,
                annexeId: $(this).data('annexe-id')
            })
            wait.show();
            axios.post(url)
                .then(() => {
                    $(this).closest('.row').remove();
                    flash.flashAdd(`Le fichier annexe ${$(this).data('annexe-id')} a été supprimé avec succès`, 'success')
                }).catch(error => {
                    console.error(error.response);
                    flash.flashAdd(`La suppression du fichier annexe ${$(this).data('annexe-id')} a échoué`, 'error')
                }).finally(() => {
                    wait.hide();
                })
        });
    });

    /**
     * AUTHENTIFICATION EXPIRATION
     */
    let onGoingRequest = false;
    let loginExpiredMsgShown = false;

    setInterval(async () => {
        if (onGoingRequest) return;

        onGoingRequest = true;
        let response = {
            data: { is_authenticated: true }
        }

        try {
            response = await axios.get(Routing.generate("plage_security_check_auth"))

        } catch (error) {
            console.error(error);
        } finally {
            onGoingRequest = false;
        }

        if (!response?.data?.is_authenticated) {
            if (!loginExpiredMsgShown) {
                const url = Routing.generate("plage_security_login", { 'side_login': true });
                let flashEl = flash.flashAdd(Translator.trans('login_expired'), 'error', true);

                flashEl.find(".btn-login").on('click', () => {
                    window.open(url, '_blank');
                    flashEl.remove();
                    loginExpiredMsgShown = false;
                });
                loginExpiredMsgShown = true
            }
        }

    }, 10000);
});
