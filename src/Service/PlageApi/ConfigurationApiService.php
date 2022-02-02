<?php

namespace App\Service\PlageApi;

class ConfigurationApiService extends AbstractPlageApiService
{
    public function create($datastoreId, $pyramidId, $name, $layerName, $title, $description, $bottomLevel, $topLevel)
    {
        $body = [
            'type' => 'WMTS-TMS',
            'name' => $name,
            'layer_name' => $layerName,
            'type_infos' => [
                'title' => $title,
                'abstract' => $description,
                'used_data' => [
                    [
                        'stored_data' => $pyramidId,
                        'bottom_level' => $bottomLevel,
                        'top_level' => $topLevel,
                    ],
                ],
            ],
        ];

        return $this->add($datastoreId, $body);
    }

    // ----------------------------

    public function getAll($datastoreId, $query = [])
    {
        return $this->request('GET', "datastores/$datastoreId/configurations", [], $query);
    }

    public function get($datastoreId, $configurationId)
    {
        return $this->request('GET', "datastores/$datastoreId/configurations/$configurationId");
    }

    /**
     * Utiliser plutôt la fonction create.
     *
     * @param string $datastoreId
     * @param array  $body
     *
     * @return array
     */
    public function add($datastoreId, $body = [])
    {
        return $this->request('POST', "datastores/$datastoreId/configurations", $body);
    }

    public function remove($datastoreId, $configurationId)
    {
        return $this->request('DELETE', "datastores/$datastoreId/configurations/$configurationId");
    }

    /**
     * Récupère toutes les offerings associées à la configuration fournie.
     *
     * @param string $datastoreId
     * @param string $configurationId
     *
     * @return mixed
     */
    public function getOfferings($datastoreId, $configurationId)
    {
        return $this->requestAll("datastores/$datastoreId/configurations/$configurationId/offerings");
    }

    /**
     * Alias of postCreateOffering.
     */
    public function publish($datastoreId, $configurationId, $endpointId)
    {
        return $this->addOffering($datastoreId, $configurationId, $endpointId);
    }

    /**
     * Récupère toutes les offerings du datastore.
     *
     * @param string $datastoreId
     * @param mixed  $query
     *
     * @return mixed
     */
    public function getAllOfferings($datastoreId, $query = [])
    {
        return $this->requestAll("datastores/$datastoreId/offerings", $query);
    }

    public function getOffering($datastoreId, $offeringId)
    {
        return $this->request('GET', "datastores/$datastoreId/offerings/$offeringId");
    }

    public function addOffering($datastoreId, $configurationId, $endpointId)
    {
        $body = [
            'visibility' => 'PUBLIC',
            'endpoint' => $endpointId,
        ];

        return $this->request('POST', "datastores/$datastoreId/configurations/$configurationId/offerings", $body);
    }

    public function removeOffering($datastoreId, $offeringId)
    {
        return $this->request('DELETE', "datastores/$datastoreId/offerings/$offeringId");
    }
}
