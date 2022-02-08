<?php

namespace App\Service\PlageApi;

use App\Exception\PlageApiException;
use App\Security\KeycloakUserProvider;
use App\Security\User;
use App\Service\PlageApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class AbstractPlageApiService
{
    public const CHECK_TYPES = ['asked', 'in_progress', 'passed', 'failed'];
    public const API_STUB_PATH_ROOT = __DIR__.'/../../../tests/api-stub/';

    protected $apiStubUpdateMode = false;

    /** @var PlageApiService */
    protected $plageApi;

    /** @var User */
    protected $user;
    protected $datastores;

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

    public function __construct(ParameterBagInterface $parameters, TokenStorageInterface $tokenStorage, KeycloakUserProvider $keycloakUserProvider, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->fs = new Filesystem();

        if ('test' != $_ENV['APP_ENV']) {
            if ($tokenStorage->getToken()->getUser() instanceof User) {
                $this->user = $tokenStorage->getToken()->getUser();
            }

            $this->userProvider = $keycloakUserProvider;
            $this->parameters = $parameters;

            $this->apiClient = new NativeHttpClient([
                'base_uri' => $this->parameters->get('api_plage_url'),
                'proxy' => $parameters->get('http_proxy'),
                'verify_peer' => false,
                'verify_host' => false,
            ]);
        } elseif (array_key_exists('API_STUB_UPDATE_MODE', $_ENV) && '1' == $_ENV['API_STUB_UPDATE_MODE']) {
            $this->apiStubUpdateMode = true;
        }
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

        return intval(ceil($total / $limit));
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
        if ('test' == $_ENV['APP_ENV']) { // only GET requests, otherwise does nothing
            return 'GET' == strtoupper($method) ? $this->fakeRequest($url, $expectJson) : [];
        }

        $options = $this->prepareOptions($body, $query, $headers, $fileUpload);

        $response = $this->apiClient->request($method, $url, $options);

        $this->logger->debug(self::class, [$method, $url, $body, $response->getContent(false)]);

        if ('test' != $_ENV['APP_ENV'] && $this->apiStubUpdateMode && 'GET' == strtoupper($method)) {
            $this->updateApiStub($url, $response->getContent(false), $expectJson);
        }

        return $this->handleResponse($response, $expectJson, $includeHeaders);
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
    protected function handleResponse(ResponseInterface $response, $expectJson, $includeHeaders)
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
            $errorResponse = $response->toArray(false);
            throw new PlageApiException(in_array('error_description', array_keys($errorResponse)) ? $errorResponse['error_description'] : 'Plage API Error', $statusCode, $errorResponse);
        }
    }

    protected function fakeRequest($path, $expectJson)
    {
        $path .= $expectJson ? '.json' : '';
        $content = file_get_contents(self::API_STUB_PATH_ROOT.$path);

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

        $options['headers']['Authorization'] = "Bearer {$this->getToken()}";
        $options['query'] = $query;

        return $options;
    }

    protected function getToken()
    {
        if (!$this->user instanceof User) {
            throw new AuthenticationException();
        }

        // performs a token refresh if expired
        $expiryDate = $this->user->getTokenExpiryDate();
        if (new \DateTime() > $expiryDate) {
            $token = $this->userProvider->refreshToken($this->user->getRefreshToken());
            $this->user->setToken($token);
        }

        return $this->user->getAccessToken();
    }
}
