import React from 'react'
import StoredData from './StoredData';

const PublishedPyramidsSection = ({ datastoreId, storedDataList }) => {
    return (
        <>
            <div className="row">
                <div className="col">
                    <div className="row">
                        <h2 className="mt-2">Mes flux publiés
                            { storedDataList?.length > 0 ?
                            <small className="ml-2"><span>{storedDataList?.length}</span> flux</small>
                            : "" }
                        </h2>
                    </div>

                    {
                        storedDataList?.length > 0 ?
                            storedDataList.map((storedDataId) =>
                                <StoredData key={storedDataId} datastoreId={datastoreId} storedDataId={storedDataId} autoRefresh={false} />
                            ) : (
                                <div className="row border border-darken-1 mb-1 p-1">
                                    <div className="col-md-6 my-auto d-flex align-items-center">
                                        <i className="icons-tiles"></i>&nbsp;Vous n'avez publié aucun flux
                                    </div>
                                    <div className="col-md-2 offset-md-3 d-flex align-items-center">
                                        <a href={Routing.generate('plage_upload_add', { datastoreId: datastoreId })} className="btn btn--plain btn--primary btn-sm w-100">Lancez-vous</a>
                                    </div>
                                </div>
                            )
                    }
                </div>
            </div>
        </>
    )
}

export default PublishedPyramidsSection;
