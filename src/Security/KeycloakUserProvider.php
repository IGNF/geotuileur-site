<?php

namespace App\Security;

use App\Service\PlageApi\UserApiService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use League\OAuth2\Client\Token\AccessToken;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class KeycloakUserProvider implements UserProviderInterface
{
    private ClientRegistry $clientRegistry;
    private KeycloakTokenManager $keycloakTokenManager;
    private UserApiService $userApiService;
    private ParameterBagInterface $params;

    public function __construct(ClientRegistry $clientRegistry, KeycloakTokenManager $keycloakTokenManager, UserApiService $userApiService, ParameterBagInterface $params)
    {
        $this->clientRegistry = $clientRegistry;
        $this->keycloakTokenManager = $keycloakTokenManager;
        $this->userApiService = $userApiService;
        $this->params = $params;
    }

    public function loadUser(AccessToken $accessToken = null): User
    {
        if ('test' == $this->params->get('app_env')) {
            return new User([
                'preferred_username' => 'test_user',
                'email' => 'test@test.com',
            ]);
        }

        if (null == $accessToken) {
            $accessToken = $this->keycloakTokenManager->getToken();
        }

        /** @var KeycloakClient */
        $keycloakClient = $this->clientRegistry->getClient('keycloak');

        /** @var KeycloakResourceOwner */
        $keycloakUser = $keycloakClient->fetchUserFromToken($accessToken);

        $apiUser = $this->userApiService->getMe();

        return new User($keycloakUser->toArray(), $apiUser);
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me. If you're not using these features, you do not
     * need to implement this method.
     *
     * @throws UserNotFoundException if the user is not found
     *
     * {@inheritDoc}
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
