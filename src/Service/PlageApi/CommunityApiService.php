<?php

namespace App\Service\PlageApi;

class CommunityApiService extends AbstractPlageApiService
{
    public function get($communityId)
    {
        return $this->request('GET', "communities/$communityId");
    }

    public function getMembers($communityId)
    {
        return $this->request('GET', "communities/$communityId/users");
    }

    /**
     * @param array $data [name, public, contact]
     *
     * @return mixed
     */
    public function modifyCommunity($communityId, $data)
    {
        return $this->request('PATCH', "communities/$communityId", $data);
    }

    /**
     * Undocumented function.
     *
     * @param string $communityId
     * @param string $userId
     * @param array  $rights      [community_rights, uploads_rights, processings_rights, stored_data_rights, datastore_rights, broadcast_rights]
     *
     * @return mixed
     */
    public function addOrModifyUserRights($communityId, $userId, $rights = [])
    {
        return $this->request('PUT', "communities/$communityId/users/$userId", $rights);
    }

    public function removeUserRights($communityId, $userId)
    {
        return $this->request('DELETE', "communities/$communityId/users/$userId");
    }
}
