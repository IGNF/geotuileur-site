import React from 'react'
import StoredData from './StoredData';

const ActionsRequiredSection = ({ datastoreId, storedDataList }) => {
    return (
        <>
            {storedDataList?.length > 0 ?
                (
                    <div className="row">
                        <div className="col">
                            <div className="row">
                                <h2>Mes actions à terminer <small className="ml-2"><span>{storedDataList?.length}</span> action{storedDataList?.length > 0 ? 's' : ''}</small></h2>
                            </div>

                            {
                                storedDataList.map((storedDataId) =>
                                    <StoredData key={storedDataId} datastoreId={datastoreId} storedDataId={storedDataId} autoRefresh={false} />
                                )
                            }
                        </div>
                    </div>
                ) : ('')
            }
        </>
    )
}

export default ActionsRequiredSection
