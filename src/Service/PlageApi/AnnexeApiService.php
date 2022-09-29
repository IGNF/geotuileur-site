<?php

namespace App\Service\PlageApi;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class AnnexeApiService extends AbstractPlageApiService
{
    public function getAll($datastoreId)
    {
        return $this->requestAll("datastores/$datastoreId/annexes");
    }

    public function get($datastoreId, $annexeId)
    {
        $apiPlageAnnexeUrl = $this->parameters->get('api_plage_annexe_url');
        $response = $this->request('GET', "datastores/$datastoreId/annexes/$annexeId");
        $response['paths'][0] = $apiPlageAnnexeUrl.$response['paths'][0];

        return $response;
    }

    public function add($datastoreId, UploadedFile $annexeFile, $path)
    {
        $directory = $this->parameters->get('oneup_uploader_gallery_path');
        $filepath = $directory.'/'.$annexeFile->getClientOriginalName();
        $annexeFile->move($directory, $annexeFile->getClientOriginalName());

        $response = $this->postFile("datastores/$datastoreId/annexes", $filepath, [
            'published' => 'true',
            'paths' => $path,
        ]);

        $this->fs->remove($filepath);

        return $response;
    }

    public function publish($datastoreId, $annexeId)
    {
        return $this->request('PATCH', "datastores/$datastoreId/annexes/$annexeId", [
            'published' => true,
        ]);
    }

    public function remove($datastoreId, $annexeId)
    {
        return $this->request('DELETE', "datastores/$datastoreId/annexes/$annexeId");
    }
}
