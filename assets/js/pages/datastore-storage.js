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
                    if ($(this).hasClass('stored-data-section')) {
                        $(this).remove();
                    } else {
                        $(this).closest('.row').remove();
                    }

                    flash.flashAdd(`La pyramide ${$(this).data('pyramid-id')} a été dépubliée avec succès`, 'success')

                    updateStorageUsage('offerings')
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

                    const storedDataStorageType = $(this).data('storage-type')
                    updateStorageUsage('stored_data', storedDataStorageType);
                    updateStorageUsage('offerings')
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

                    const storedDataStorageType = $(this).data('storage-type')
                    updateStorageUsage('stored_data', storedDataStorageType);
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

                    updateStorageUsage('uploads');
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

async function getDatastore() {
    let url = Routing.generate('plage_datastore_get_datastore_ajax', { datastoreId: datastoreId })
    let response = null;
    try {
        response = (await axios.get(url)).data
    } catch (error) {
        console.error(error);
    } finally {
        return response;
    }
}

/**
 * 
 * @param {JQuery<HTMLElement>} dataContainerDiv 
 * @param {Number|String} use 
 * @param {Number|String} quota 
 */
function updateProgressBar(dataContainerDiv, use, quota) {
    use = parseInt(use);
    quota = parseInt(quota);
    const nbUse = niceBytes(use)
    const nbQuota = niceBytes(quota)

    const progressBar = dataContainerDiv.find('.progress-bar')
    const spanStorageUse = dataContainerDiv.find('.storage-use')
    const spanStorageQuota = dataContainerDiv.find('.storage-quota')

    if (spanStorageUse.hasClass('nice-bytes')) {
        spanStorageUse.text(nbUse)
        spanStorageQuota.text(nbQuota)
    } else {
        spanStorageUse.text(use)
        spanStorageQuota.text(quota)
    }

    const progressPercentage = ((use / quota) * 100).toFixed(1)
    const progressBarClass = 'progress-bar ' + (progressPercentage > 75 ? 'bg-danger' : 'bg-success')

    progressBar.attr('class', progressBarClass)
    progressBar.attr('style', `width: ${progressPercentage}%`)
    progressBar.attr('aria-valuenow', use)
    progressBar.attr('aria-valuemax', quota)
    progressBar.text(`${progressPercentage}%`)
}

/**
 * 
 * @param {String} storageType
  * @param {String} storedDataStorageType
 * @returns 
 */
async function updateStorageUsage(storageType, storedDataStorageType = null) {
    let use = null
    let quota = null
    let dataContainerDiv = null
    const datastore = await getDatastore();

    switch (storageType) {
        case 'uploads':
            dataContainerDiv = $('.storage-container[data-storage-type="uploads"]')
            use = datastore?.storages?.uploads?.use
            quota = datastore?.storages?.uploads?.quota
            break;

        case 'stored_data':
            dataContainerDiv = $(`.storage-container[data-storage-type="stored_data_${storedDataStorageType}"]`)
            const dataStorage = datastore?.storages?.data.find(d => d.type == storedDataStorageType)
            use = dataStorage.use
            quota = dataStorage.quota
            break;

        case "offerings":
            dataContainerDiv = $('.storage-container[data-storage-type="offerings"]')
            use = datastore.endpoints[0].use
            quota = datastore.endpoints[0].quota
            break;

        case 'annexes':
            dataContainerDiv = $('.storage-container[data-storage-type="annexes"]')
            use = datastore?.storages?.annexes?.use
            quota = datastore?.storages?.annexes?.quota
            break;

        default:
            return;
    }

    updateProgressBar(dataContainerDiv, use, quota)
    const hasLowStorage = datastoreHasLowStorage(datastore);

    if (!hasLowStorage) {
        $('.notifications-bar').remove()
        $('.body-wrapper').removeClass('with-notifications-bar')
    }
}

function datastoreHasLowStorage(datastore) {
    const storages = [...datastore?.storages?.data, datastore?.storages?.uploads, datastore?.storages?.annexes, datastore?.endpoints[0]];

    let hasLowStorage = false;

    storages.forEach(storage => {
        const usage = (storage.use / storage.quota) * 100
        if (usage >= 90) {
            hasLowStorage = true
        }
    });

    return hasLowStorage
}
