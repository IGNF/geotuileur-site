<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationExpiredException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

class KeycloakTokenManager
{
    public const KEYCLOAK_TOKEN_SESSION_KEY = 'keycloak_token';

    private ClientRegistry $clientRegistry;
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(ClientRegistry $clientRegistry, RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->clientRegistry = $clientRegistry;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * Retrieves the access token from the session, refreshes the token via KeycloakClient if expired and stores it in the session, and finally returns the token.
     */
    public function getToken(): AccessToken
    {
        $session = $this->requestStack->getSession();

        /** @var KeycloakClient */
        $keycloakClient = $this->clientRegistry->getClient('keycloak');

        // retrieves the token from session
        /** @var AccessToken */
        $accessToken = $session->get(self::KEYCLOAK_TOKEN_SESSION_KEY);

        if (null == $accessToken) {
            throw new TokenNotFoundException('Votre authentification a échoué', Response::HTTP_UNAUTHORIZED);
        }

        // refreshes the token via KeycloakClient if expired
        if (($accessToken->getExpires() - 30) < time()) {
            $this->logger->debug('Token expired [{id_token}]', ['id_token' => $accessToken->getValues()['id_token']]);

            try {
                /** @var AccessToken */
                $accessToken = $keycloakClient->refreshAccessToken($accessToken->getRefreshToken());
            } catch (IdentityProviderException $ex) {
                throw new AuthenticationExpiredException('Votre authentication a expirée, veuillez vous reconnecter', Response::HTTP_UNAUTHORIZED, $ex);
            }

            $this->logger->debug('Token refreshed [{id_token}]', ['id_token' => $accessToken->getValues()['id_token']]);

            $session->set(self::KEYCLOAK_TOKEN_SESSION_KEY, $accessToken);
        } else {
            $this->logger->debug('Token still valid [{id_token}]', ['id_token' => $accessToken->getValues()['id_token']]);
        }

        return $accessToken;
    }
}
