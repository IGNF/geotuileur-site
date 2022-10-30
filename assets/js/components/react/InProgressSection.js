import React from 'react'
import PropTypes from 'prop-types'
import StoredData from './StoredData';

const InProgressSection = ({ datastoreId, storedDataList }) => {
    return (
        <>
            {storedDataList?.length > 0 ?
                (
                    <div className="row">
                        <div className="col">
                            <div className="row">
                                <h2 className="mt-2">En cours <small className="ml-2"><span>{storedDataList?.length}</span> action{storedDataList?.length > 0 ? 's' : ''}</small></h2>
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

InProgressSection.propTypes = {
    datastoreId: PropTypes.string,
    storedDataList: PropTypes.array
}

export default InProgressSection;
