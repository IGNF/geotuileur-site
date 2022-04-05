import { niceBytes } from '../utils'

const flash = require("./flash-messages");

/**
 * Classe de base pour les action sur les stored datas
 */
export class StoredDataAction {
    constructor(datastoreId, storedData) {
        this.url = null;
        this.title = null;
        this.message = null;
        this.failMessage = null;

        this.datastoreId = datastoreId;
        this.storedData = storedData;
    }

    confirm() {
        let _self = this;
        if (!this.url) { return; }

        bootbox.confirm({
            title: this.title,
            message: this.message,
            buttons: {
                confirm: { className: 'btn btn--plain btn--primary' },
                cancel: { className: 'btn btn--ghost btn--gray' }
            },
            callback: (result => {
                if (!result) return;

                $.post(_self.url, () => { }).fail(() => {
                    flash.flashAdd(_self.failMessage, 'danger');
                });
            })
        });
    }
};

/**
 * Action de suppression
 */
export class RemoveAction extends StoredDataAction {
    constructor(datastoreId, storedData) {
        super(datastoreId, storedData);

        // Calcule de l'espace qui sera libere
        let bytes = niceBytes(storedData.size);
        let status = storedData.status;
        let pyramidIsPublished = storedData?.tags?.published;

        this.title = `Vous êtes sur le point de supprimer ${storedData.name}`;

        this.message = `<p>Cette donnée sera définitivement supprimée. Cela va libérer ${bytes} de votre espace de travail.<br/>`
        if (pyramidIsPublished) {
            this.message += `Le flux associé sera dépublié.<br/>`;
        }
        this.message += `Voulez-vous continuer ?</p>`;

        this.failMessage = `La suppression de la donnée ${storedData._id} a échoué.`;

        switch (storedData.status) {
            // Suppression d'une donnee de type base Postgres ou de type pyramide
            case 'GENERATED':
                if (pyramidIsPublished) {
                    this.url = Routing.generate('plage_pyramid_delete_published_ajax', { datastoreId: datastoreId, pyramidId: storedData._id });
                } else {
                    this.url = Routing.generate('plage_stored_data_delete_ajax', { datastoreId: datastoreId, storedDataId: storedData._id });
                }
                break;
            case 'UNSTABLE':
                this.url = Routing.generate('plage_stored_data_delete_ajax', { datastoreId: datastoreId, storedDataId: storedData._id });
                break;
            case 'GENERATING':
            default: break;
        }
    }
}

/**
 * Action de depublication
 */
export class UnpublishAction extends StoredDataAction {
    constructor(datastoreId, storedData) {
        super(datastoreId, storedData);

        this.url = Routing.generate('plage_pyramid_unpublish_ajax', { datastoreId: datastoreId, pyramidId: storedData._id });
        this.title = `Dépublication de ${storedData.name}`;
        this.message = 'Etes-vous sûr de vouloir dépublier ce flux ?';
        this.failMessage = `La dépublication du flux ${storedData._id} a échoué.`;
    }
}

/**
 * Factroy d'action
 */
export class StoredDataActionFactory {
    create(type, datastoreId, storedData) {
        switch (type) {
            case 'remove':
                return new RemoveAction(datastoreId, storedData);
            case 'unpublish':
                return new UnpublishAction(datastoreId, storedData);
            default: return null;
        }
    }
}
