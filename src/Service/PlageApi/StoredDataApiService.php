<?php

namespace App\Service\PlageApi;

use App\Constants\StoredDataStatuses;
use App\Constants\StoredDataTypes;

class StoredDataApiService extends AbstractPlageApiService
{
    /**
     * @param string $datastoreId
     * @param array  $query
     *
     * @return array
     */
    public function getAll($datastoreId, $query = [])
    {
        if (!array_key_exists('sort', $query)) { // par défaut, trier par la date de création décroissante
            $query['sort'] = 'date:desc';
        }

        return $this->requestAll("datastores/$datastoreId/stored_data", $query);
    }

    /**
     * @param string $datastoreId
     * @param string $storedDataId
     *
     * @return array
     */
    public function get($datastoreId, $storedDataId)
    {
        return $this->request('GET', "datastores/$datastoreId/stored_data/$storedDataId");
    }

    /**
     * @param string $datastoreId
     * @param string $storedDataId
     *
     * @return array
     */
    public function getDetailed($datastoreId, $storedDataId)
    {
        $storedData = $this->get($datastoreId, $storedDataId);

        // fetch information specific to the type of stored data
        if (StoredDataTypes::VECTOR_DB == $storedData['type']) {
            if (array_key_exists('proc_int_id', $storedData['tags'])) {
                $vectordbProcInt = $this->plageApi->processing->getExecution($datastoreId, $storedData['tags']['proc_int_id']);
                $storedData['input_upload_id'] = $vectordbProcInt['inputs']['upload'][0]['_id'];
            }
        } elseif (StoredDataTypes::ROK4_PYRAMID_VECTOR == $storedData['type']) {
            $offerings = $this->plageApi->configuration->getAllOfferings($datastoreId, [
                    'stored_data' => $storedData['_id'],
                ]);

            // check if pyramid is already published or not
            if (0 == count($offerings)) {
                $storedData['tags']['published'] = false;
            } else {
                $storedData['tags']['published'] = true;
            }
        }

        if (array_key_exists('last_event', $storedData)) {
            $storedData['last_event']['date_text'] = (new \DateTime($storedData['last_event']['date'], new \DateTimeZone('Europe/Paris')))->format('d/m/y H\hi'); // d F Y
        }

        return $storedData;
    }

    public function modifyName($datastoreId, $storedDataId, $newName)
    {
        $this->request('PATCH', "datastores/$datastoreId/stored_data/$storedDataId", [
            'name' => $newName,
        ]);
    }

    /**
     * @param string $datastoreId
     * @param string $storedDataId
     *
     * @return void
     */
    public function remove($datastoreId, $storedDataId)
    {
        $storedData = $this->get($datastoreId, $storedDataId);
        if (StoredDataTypes::ROK4_PYRAMID_VECTOR == $storedData['type'] && array_key_exists('initial_pyramid_id', $storedData['tags'])) {
            $this->removeTags($datastoreId, $storedData['tags']['initial_pyramid_id'], ['update_pyramid_id']);
        }

        if (StoredDataStatuses::CREATED == $storedData['status'] && array_key_exists('proc_int_id', $storedData['tags'])) {
            $this->plageApi->processing->removeExecution($datastoreId, $storedData['tags']['proc_int_id']);
        }

        $this->request('DELETE', "datastores/$datastoreId/stored_data/$storedDataId");
    }

    /**
     * @param string $datastoreId
     * @param string $storedDataId
     * @param array  $tags
     *
     * @return void
     */
    public function addTags($datastoreId, $storedDataId, $tags)
    {
        $this->request('POST', "datastores/$datastoreId/stored_data/$storedDataId/tags", $tags);
    }

    /**
     * @param string $datastoreId
     * @param string $storedDataId
     * @param array $tags
     *
     * @return void
     */
    public function removeTags($datastoreId, $storedDataId, $tags)
    {
        $this->request('DELETE', "datastores/$datastoreId/stored_data/$storedDataId/tags", [], [
            'tags' => $tags,
        ]);
    }

    /**
     * Recupere les tags de style (styles et default_style) dans la storedData.
     *
     * @param string $datastoreId
     * @param string $storedDataId
     *
     * @return array
     */
    public function getTagStyles($datastoreId, $storedDataId)
    {
        $storedData = $this->get($datastoreId, $storedDataId);

        $styles = [];
        $defaultStyle = null;

        if (isset($storedData['tags']['styles'])) {
            $styles = json_decode($storedData['tags']['styles'], true);
        }

        if (isset($storedData['tags']['default_style'])) {
            $defaultStyle = $storedData['tags']['default_style'];
        } elseif (count($styles)) {    // S'il n'est pas dans les tags, on prend le dernier
            $name = array_key_last($styles);
            $defaultStyle = $styles[$name];
        }

        return ['styles' => $styles, 'default_style' => $defaultStyle];
    }

    /**
     * Recupere les tags de style (styles et default_style) dans la storedData et ajoute l'url
     * pour chaque annexe.
     *
     * @param string $datastoreId
     * @param string $storedDataId
     *
     * @return array
     */
    public function getStyles($datastoreId, $storedDataId)
    {
        $storedData = $this->get($datastoreId, $storedDataId);

        $styles = [];
        $defaultStyle = null;
        if (isset($storedData['tags']['styles'])) {
            $tagStyles = json_decode($storedData['tags']['styles'], true);
            foreach ($tagStyles as $id => $name) {
                $annexe = $this->plageApi->annexe->get($datastoreId, $id);
                $styles[$id] = ['name' => $name, 'url' => $annexe['paths'][0]];
            }
        }

        if (isset($storedData['tags']['default_style'])) {
            $defaultStyle = $storedData['tags']['default_style'];
        } elseif (count($styles)) {    // S'il n'est pas dans les tags, on prend le dernier
            $defaultStyle = array_key_last($styles);
        }

        // On met le style par defaut au debut
        if (!is_null($defaultStyle)) {
            $defStyle = [$defaultStyle => $styles[$defaultStyle]];
            unset($styles[$defaultStyle]);
            $styles = array_merge($defStyle, $styles);
        }

        return ['styles' => $styles, 'defaultStyle' => $defaultStyle];
    }
}
