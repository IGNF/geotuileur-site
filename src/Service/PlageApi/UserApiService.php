<?php

namespace App\Service\PlageApi;

use App\Exception\PlageApiException;

class UserApiService extends AbstractPlageApiService
{
    public function getMe()
    {
        return $this->request('GET', 'users/me');
    }

    public function getMyDatastores()
    {
        $me = $this->getMe();

        $datastoresList = [];
        $communitiesMember = $me['communities_member'];
        foreach ($communitiesMember as $communityMember) {
            $community = $communityMember['community'];
            if (isset($community['datastore'])) {
                $datastoresList[] = $community['datastore'];
            }
        }

        $datastores = [];
        foreach ($datastoresList as $datastoreId) {
            try {
                $datastores[$datastoreId] = $this->plageApi->datastore->get($datastoreId);
            } catch (PlageApiException $e) {
                // Rien à faire de particulier. On ignore silencieusement l'erreur et pour l'utilisateur c'est comme si ce datastore n'existait pas.
            }
        }

        return $datastores;
    }
}
