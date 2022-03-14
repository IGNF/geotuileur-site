<?php

namespace App\Security;

use App\Exception\AppException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class KeycloakAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'plage_security_login';
    public const LOGIN_CHECK_ROUTE = 'plage_security_login_check';
    public const SUCCESS_ROUTE = 'plage_datastore_index';
    public const HOME_ROUTE = 'plage_home';

    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function supports(Request $request)
    {
        return self::LOGIN_CHECK_ROUTE === $request->attributes->get('_route');
    }

    public function getCredentials(Request $request)
    {
        return $request->query->all();
    }

    /**
     * @param array                $credentials
     * @param KeycloakUserProvider $userProvider
     *
     * @return User
     *
     * @throws CustomUserMessageAuthenticationException
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            return $userProvider->getUser($credentials);
        } catch (AppException $ex) {
            throw new CustomUserMessageAuthenticationException($ex->getMessage(), $ex->getDetails(), $ex->getCode(), $ex);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        // user is already authenticated by keycloak
        return true;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::SUCCESS_ROUTE));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /** @var AppException */
        $ex = $exception->getPrevious(); // get the original AppException
        $details = $ex->getDetails();

        if (array_key_exists('error', $details) && array_key_exists('error_description', $details)) {
            if ('invalid_grant' == $details['error'] && 'Code not valid' == $details['error_description']) {
                throw new AppException("Authentification échouée en raison d'une erreur interne", Response::HTTP_UNAUTHORIZED, $details, $ex);
            }

            if ('invalid_grant' == $details['error'] && 'Invalid user credentials' == $details['error_description']) {
                throw new AppException("Authentification échouée : nom d'utilisateur et/ou mot de passe sont incorrects", Response::HTTP_UNAUTHORIZED, $details, $ex);
            }
        }

        throw $ex;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
