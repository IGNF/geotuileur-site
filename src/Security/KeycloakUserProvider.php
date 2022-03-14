<?php

namespace App\Security;

use App\Exception\AppException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakUserProvider implements UserProviderInterface
{
    private $urlBase;
    private $clientId;

    private $client;
    private $urlGenerator;
    private $logger;

    public function __construct(ParameterBagInterface $parameters, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger)
    {
        $this->urlBase = $parameters->get('iam_url');
        $this->clientId = $parameters->get('iam_client_id');

        $this->client = HttpClient::createForBaseUri($this->urlBase, [
            'proxy' => $parameters->get('http_proxy'),
            'verify_peer' => false,
            'verify_host' => false,
        ]);

        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function getUser($credentials)
    {
        $token = $this->getAccessToken($credentials['code']);
        $userInfo = $this->getUserInfo($token);

        return new User($userInfo);
    }

    public function getAccessToken($code)
    {
        $redirectUri = $this->urlGenerator->generate('plage_security_login_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $body = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
        ];

        $url = $this->urlBase.'/token';
        $response = $this->client->request('POST', $url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
        ]);

        $this->logger->info(self::class.': getAccessToken', ['POST', $url, $body, $response->getContent(false)]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $responseData = json_decode($response->getContent(false), true);
            throw new AppException('Authentication failed', Response::HTTP_INTERNAL_SERVER_ERROR, $responseData);
        }

        return \json_decode($response->getContent(), true);
    }

    public function getUserInfo($token)
    {
        $body = ['access_token' => $token['access_token']];

        $url = $this->urlBase.'/userinfo';
        $response = $this->client->request('POST', $url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
        ]);

        $this->logger->info(self::class.': getUserInfo', ['POST', $url, $body, $response->getContent(false)]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new AuthenticationException();
        }

        $userInfo = \json_decode($response->getContent(), true);
        $userInfo['token'] = $token;

        return $userInfo;
    }

    public function refreshToken($refreshToken)
    {
        $body = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'refresh_token' => $refreshToken,
        ];

        $url = $this->urlBase.'/token';
        $response = $this->client->request('POST', $url, [
            'body' => $body,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
        ]);

        $this->logger->info(self::class.': refreshToken', ['POST', $url, $body, $response->getContent(false)]);

        if (Response::HTTP_OK == $response->getStatusCode()) {
            return \json_decode($response->getContent(), true);
        } else {
            throw new AuthenticationException();
        }
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException if the user is not found
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadUserByUsername($username)
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        throw new \Exception('Not needed: fill in loadUserByUsername() inside '.__FILE__);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     *
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
