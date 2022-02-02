<?php

namespace App\Service;

use App\Service\PlageApi\AnnexeApiService;
use App\Service\PlageApi\CommunityApiService;
use App\Service\PlageApi\ConfigurationApiService;
use App\Service\PlageApi\DatastoreApiService;
use App\Service\PlageApi\ProcessingApiService;
use App\Service\PlageApi\StoredDataApiService;
use App\Service\PlageApi\UploadApiService;
use App\Service\PlageApi\UserApiService;

class PlageApiService
{
    /** @var UserApiService */
    public $user;

    /** @var DatastoreApiService */
    public $datastore;

    /** @var UploadApiService */
    public $upload;

    /** @var StoredDataApiService */
    public $storedData;

    /** @var ProcessingApiService */
    public $processing;

    /** @var AnnexeApiService */
    public $annexe;

    /** @var CommunityApiService */
    public $community;

    /** @var ConfigurationApiService */
    public $configuration;

    public function __construct(
        UserApiService $user,
        DatastoreApiService $datastore,
        UploadApiService $upload,
        StoredDataApiService $storedData,
        ProcessingApiService $processing,
        AnnexeApiService $annexe,
        CommunityApiService $community,
        ConfigurationApiService $configuration
    ) {
        $this->user = $user;
        $this->datastore = $datastore;
        $this->upload = $upload;
        $this->storedData = $storedData;
        $this->processing = $processing;
        $this->annexe = $annexe;
        $this->community = $community;
        $this->configuration = $configuration;

        $this->user->setPlageApi($this);
        $this->datastore->setPlageApi($this);
        $this->upload->setPlageApi($this);
        $this->storedData->setPlageApi($this);
        $this->processing->setPlageApi($this);
        $this->annexe->setPlageApi($this);
        $this->community->setPlageApi($this);
        $this->configuration->setPlageApi($this);
    }
}
