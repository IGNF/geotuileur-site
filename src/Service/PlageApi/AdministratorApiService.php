<?php

namespace App\Service\PlageApi;

use App\Exception\PlageApiException;
use App\Service\PlageApiService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdministratorApiService
{
    /** @var HttpClientInterface */
    protected $apiClient;

    /** @var ParameterBagInterface */
    protected $params;

    /** @var array */
    protected $me;

    /** @var array */
    protected $token = null;

    public function __construct(
        ParameterBagInterface $params,
        PlageApiService $plageApi)
    {
        $this->params = $params;
        $serviceAccount = $this->params->get('service_account');

        $this->apiClient = HttpClient::createForBaseUri($this->params->get('api_plage_url'), [
            'proxy' => $this->params->get('http_proxy'),
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $this->me = $plageApi->user->getMe();

        // Demande de token
        if ($serviceAccount) {
            $this->token = $this->getAccessToken($serviceAccount);
        }
    }

    public function createCommunity($name, $technicalName, $public = false)
    {
        if (!$this->token) {
            throw new AccessDeniedException();
        }

        $options = $this->prepareOptions([
            'name' => $name,
            'technical_name' => $technicalName,
            'public' => $public,
            'contact' => $this->me['email'],
            'supervisor' => $this->me['_id'],
        ]);

        $response = $this->apiClient->request('POST', 'administrator/communities', $options);

        return $this->handleResponse($response);
    }

    /**
     * @SuppressWarnings(UnusedLocalVariable)
     */
    public function createDatastore($communityId)
    {
        $quota = 100000000; // ~100Mo

        $checks = $this->params->get('api_plage_checks');
        $processings = $this->params->get('api_plage_processings');

        $storages = ['data' => []];

        /** @var array */
        $apiStorages = $this->params->get('api_plage_storages');
        foreach ($apiStorages as $key => $id) {
            $storages['data'][] = ['quota' => $quota, 'storage' => $id];
        }

        foreach (['uploads', 'annexes'] as $name) {
            $storages[$name] = ['quota' => $quota, 'storage' => $apiStorages['storage_filesystem']];
        }

        $options = $this->prepareOptions([
            'community' => $communityId,
            'processings' => array_values($processings),
            'checks' => array_values($checks),
            'storages' => $storages,
            'endpoints' => [
                ['quota' => 2, 'endpoint' => $this->params->get('api_plage_endpoint')],
            ],
        ]);

        $response = $this->apiClient->request('POST', 'administrator/datastores', $options);

        return $this->handleResponse($response);
    }

    /**
     * Active le datastore.
     *
     * @param string $datastoreId
     */
    public function activateDatastore($datastoreId)
    {
        $options = $this->prepareOptions(['active' => true]);

        $response = $this->apiClient->request('PATCH', "administrator/datastores/$datastoreId", $options);

        return $this->handleResponse($response);
    }

    private function prepareOptions($body = [], $query = [], $headers = [])
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json',
        ];

        $options = [
            'json' => $body,
            'headers' => array_merge($defaultHeaders, $headers),
        ];

        $options['headers']['Authorization'] = "Bearer {$this->token['access_token']}";
        $options['query'] = $query;

        return $options;
    }

    private function handleResponse($response)
    {
        $content = null;

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) { // if request is successful
            if (204 == $statusCode || '' == $response->getContent()) { // if response body is empty
                $content = [];
            } else {
                $content = $response->toArray();
            }

            return $content;
        }

        $errorResponse = $response->toArray(false);

        $description = 'Plage API Error';
        if (in_array('error_description', array_keys($errorResponse))) {
            $description = $errorResponse['error_description'];
        }
        throw new PlageApiException($description, $statusCode, $errorResponse);
    }

    /**
     * Recuperation du Token.
     */
    private function getAccessToken($serviceAccount)
    {
        $body = [
            'grant_type' => 'password',
            'username' => $serviceAccount['username'],
            'password' => $serviceAccount['password'],
            'client_id' => $this->params->get('iam_client_id'),
        ];

        $uri = $this->params->get('iam_url');
        if (!preg_match('/\/$/', $uri)) {
            $uri .= '/';
        }
        $client = HttpClient::createForBaseUri($uri, [
            'proxy' => $this->params->get('http_proxy'),
            'verify_peer' => false,
            'verify_host' => false,
        ]);
        $response = $client->request('POST', 'token', [
            'verify_peer' => false,
            'verify_host' => false,
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            return null;
        }

        return \json_decode($response->getContent(), true);
    }
}
