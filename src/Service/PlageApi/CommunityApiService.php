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
}
