<?php

namespace App\Service\PlageApi;

class DatastoreApiService extends AbstractPlageApiService
{
    public function get($datastoreId)
    {
        return $this->request('GET', "datastores/$datastoreId");
    }

    public function getEndpoints($datastoreId)
    {
        $datastore = $this->get($datastoreId);

        return array_key_exists('endpoints', $datastore) ? $datastore['endpoints'] : [];
    }
}
