<?php

namespace App\Service\PlageApi;

use App\Exception\PlageApiException;
// use App\Security\KeycloakUserProvider;
use App\Security\KeycloakUserProvider;
use App\Service\PlageApiService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPlageApiService
{
    public const CHECK_TYPES = ['asked', 'in_progress', 'passed', 'failed'];
    public const API_STUB_PATH_ROOT = __DIR__.'/../../../tests/api-stub/';

    /** @var PlageApiService */
    protected $plageApi;

    /** @var HttpClientInterface */
    protected $apiClient;

    /** @var KeycloakUserProvider */
    protected $userProvider;

    /** @var ParameterBagInterface */
    protected $parameters;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Filesystem */
    protected $fs;

    protected ClientRegistry $clientRegistry;

    private $updateApiStubMode = false;

    public function __construct(ParameterBagInterface $parameters, KeycloakUserProvider $keycloakUserProvider, ClientRegistry $clientRegistry, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->fs = new Filesystem();
        $this->userProvider = $keycloakUserProvider;
        $this->parameters = $parameters;
        $this->clientRegistry = $clientRegistry;

        $this->apiClient = new NativeHttpClient([
            'base_uri' => $this->parameters->get('api_plage_url'),
            'proxy' => $this->parameters->get('http_proxy'),
            'verify_peer' => false,
            'verify_host' => false,
        ]);
    }

    public function setPlageApi(PlageApiService $plageApi)
    {
        $this->plageApi = $plageApi;
    }

    public function sort($dataList, $by, $orderDesc = true)
    {
        $sortedResults = $dataList;

        switch ($by) {
            case 'last_event_date':
                usort($dataList, function ($data1, $data2) use ($orderDesc) {
                    try {
                        $date1 = new \DateTime($data1['last_event']['date']);
                        $date2 = new \DateTime($data2['last_event']['date']);
                    } catch (\Throwable $th) {
                        return 1;
                    }

                    if ($orderDesc) {
                        return $date1 < $date2 ? 1 : 0;
                    } else {
                        return $date1 > $date2 ? 1 : 0;
                    }
                });
                $sortedResults = $dataList;
                break;

            case 'size':
                usort($dataList, function ($data1, $data2) use ($orderDesc) {
                    try {
                        $size1 = $data1['size'];
                        $size2 = $data2['size'];
                    } catch (\Throwable $th) {
                        return 1;
                    }

                    if ($orderDesc) {
                        return $size1 < $size2 ? 1 : 0;
                    } else {
                        return $size1 > $size2 ? 1 : 0;
                    }
                });
                $sortedResults = $dataList;
                break;

            default:
                break;
        }

        return $sortedResults;
    }

    /**
     * Calculates and returns the number of pages from the Content-Range header.
     *
     * @param string $contentRange
     * @param int    $limit
     *
     * @return int
     */
    protected function getResultsPageCount($contentRange, $limit)
    {
        $contentRangeArr = explode('/', $contentRange);
        $total = $contentRangeArr[1];

        return intval(ceil(intval($total) / $limit));
    }

    protected function postFile($url, $filepath, $query = [])
    {
        $formFields = [
            'filename' => DataPart::fromPath($filepath),
        ];
        $formData = new FormDataPart($formFields);

        $prepHeaders = $formData->getPreparedHeaders()->toArray();
        $headers['Content-Type'] = substr($prepHeaders[0], 14);

        return $this->request('POST', $url, $formData->bodyToIterable(), $query, $headers, true);
    }

    /**
     * Récupère toutes les ressources en faisant la requête GET en boucle (en utilisant le header content-range).
     *
     * @param string $url
     * @param array  $query
     * @param array  $headers
     *
     * @return mixed
     */
    protected function requestAll($url, $query = [], $headers = [])
    {
        if ($this->isTest()) {
            return $this->request('GET', $url, [], $query, $headers);
        }

        $query['page'] = 1;
        $query['limit'] = 50;

        $response = $this->request('GET', $url, [], $query, $headers, false, true, true);

        $allResources = $response['content'];

        $contentRange = $response['headers']['content-range'][0];
        $pageCount = $this->getResultsPageCount($contentRange, $query['limit']);

        // on a déjà le contenu de la page 1, donc on commence à 2 et on va jusqu'à $pageCount
        for ($i = 2; $i <= $pageCount; ++$i) {
            $query['page'] = $i;
            $allResources = array_merge($allResources, $this->request('GET', $url, [], $query, $headers));
        }

        return $allResources;
    }

    /**
     * Generic function to make an API call.
     * The API is expected to return with a JSON string or an empty body on most cases, except some known cases, when it is expected to return a plain string (logs for example).
     *
     * Throws an exception in case of a bad request, with error details sent by the API.
     *
     * @param string $method
     * @param string $url
     * @param array  $body
     * @param array  $query
     * @param array  $headers
     * @param bool   $fileUpload
     * @param bool   $expectJson
     * @param bool   $includeHeaders
     *
     * @return mixed
     *
     * @throws PlageApiException
     */
    protected function request($method, $url, $body = [], $query = [], $headers = [], $fileUpload = false, $expectJson = true, $includeHeaders = false)
    {
        if ($this->isTest()) { // only GET requests, otherwise does nothing
            return 'GET' == strtoupper($method) ? $this->fakeRequest($url, $expectJson) : [];
        }

        $options = $this->prepareOptions($body, $query, $headers, $fileUpload);

        $response = $this->apiClient->request($method, $url, $options);

        $this->logger->debug(self::class, [$method, $url, $body, $query, $response->getContent(false)]);

        if ($this->updateApiStubMode && !$this->isTest()) {
            $this->updateApiStub($url, $response->getContent(false), $expectJson);
        }

        return $this->handleResponse($method, $url, $body, $query, $response, $expectJson, $includeHeaders);
    }

    /**
     * Gestion de la réponse de l'API.
     *
     * @param ResponseInterface $response       la réponse envoyée par l'API
     * @param bool              $expectJson
     * @param bool              $includeHeaders
     *
     * @return mixed
     *
     * @throws PlageApiException
     */
    protected function handleResponse($method, $url, $body, $query, ResponseInterface $response, $expectJson, $includeHeaders)
    {
        $content = null;

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 200 && $statusCode < 300) { // if request is successful
            if (204 == $statusCode || '' == $response->getContent()) { // if response body is empty
                $content = [];
            } else {
                if (!$expectJson) { // if the API is expected to return a non JSON string
                    $content = $response->getContent();
                } else {
                    $content = $response->toArray();
                }
            }

            if ($includeHeaders) {
                return [
                    'content' => $content,
                    'headers' => $response->getHeaders(),
                ];
            } else {
                return $content;
            }
        } else {
            try {
                $errorResponse = $response->toArray(false);
            } catch (JsonException $ex) {
                $errorResponse = $response->getContent(false);
            }

            $this->logger->warning(self::class, [$method, $url, $body, $query, $errorResponse]);

            $errorMsg = 'Plage API Error';
            if (is_array($errorResponse) && in_array('error_description', array_keys($errorResponse))) {
                $errorMsg = $errorResponse['error_description'];
            }

            throw new PlageApiException($errorMsg, $statusCode, $errorResponse);
        }
    }

    protected function fakeRequest($path, $expectJson)
    {
        $path .= $expectJson ? '.json' : '';

        try {
            $content = file_get_contents(self::API_STUB_PATH_ROOT.$path);
        } catch (\Throwable $th) {
            return $expectJson ? [] : '';
        }

        return $expectJson ? json_decode($content, true) : $content;
    }

    protected function updateApiStub($path, $content, $expectJson)
    {
        $path .= $expectJson ? '.json' : '';
        $content = $expectJson && $content ? json_encode(json_decode($content), JSON_PRETTY_PRINT) : $content;
        $this->fs->dumpFile(self::API_STUB_PATH_ROOT.$path, $content);
    }

    /**
     * Prepares http request options according to the type of request.
     *
     * @param array $body
     * @param array $query
     * @param array $headers
     * @param bool  $fileUpload
     *
     * @return array
     */
    protected function prepareOptions($body = [], $query = [], $headers = [], $fileUpload = false)
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            // "Accept" => "application/json",
        ];

        $options = [];
        if ($fileUpload) {
            $options = [
                'body' => $body,
                'headers' => $headers,
            ];
        } else {
            $options = [
                'json' => $body,
                'headers' => array_merge($defaultHeaders, $headers),
            ];
        }

        /** @var AccessToken */
        $accessToken = $this->userProvider->getToken();

        $options['headers']['Authorization'] = "Bearer {$accessToken->getToken()}";
        $options['query'] = $query;

        return $options;
    }

    protected function isTest()
    {
        $user = $this->userProvider->loadUser();

        return 'test_user' == $user->getUserIdentifier();
    }
}
