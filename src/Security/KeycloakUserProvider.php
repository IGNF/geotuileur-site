<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakUserProvider implements UserProviderInterface
{
    private ClientRegistry $clientRegistry;
    private RequestStack $requestStack;
    private LoggerInterface $logger;

    public function __construct(ClientRegistry $clientRegistry, RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->clientRegistry = $clientRegistry;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    public function loadUser(AccessToken $accessToken = null): User
    {
        if (null == $accessToken) {
            $accessToken = $this->getToken();
        }

        /** @var KeycloakClient */
        $keycloakClient = $this->clientRegistry->getClient('keycloak');

        dump($accessToken);

        /** @var KeycloakResourceOwner */
        $keycloakUser = $keycloakClient->fetchUserFromToken($accessToken);

        return new User($keycloakUser->toArray());
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
        $accessToken = $session->get('keycloak_token');
        dump($accessToken);

        // fetches the token via KeycloakClient if null
        // if (null == $accessToken) {
        //     /** @var AccessToken */
        //     $accessToken = $keycloakClient->getAccessToken();
        //     $session->set('keycloak_token', $accessToken);
        // }

        // TODO hasExpired n'a pas l'air fonctionner
        // refreshes the token via KeycloakClient if expired
        // if ($accessToken->hasExpired()) {
        $this->logger->debug('Token [{id_token}] expired', ['id_token' => $accessToken->getValues()['id_token']]);

        /** @var AccessToken */
        $accessToken = $keycloakClient->refreshAccessToken($accessToken->getRefreshToken());

        $this->logger->debug('Token [{id_token}] refreshed', ['id_token' => $accessToken->getValues()['id_token']]);

        $session->set('keycloak_token', $accessToken);
        // }

        return $accessToken;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me. If you're not using these features, you do not
     * need to implement this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        return $this->loadUser();
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
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

        return $this->loadUser();
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
