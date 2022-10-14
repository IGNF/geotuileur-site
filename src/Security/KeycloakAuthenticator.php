<?php

namespace App\Security;

use App\Exception\AppException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class KeycloakAuthenticator extends OAuth2Authenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'plage_security_login';
    public const LOGIN_CHECK_ROUTE = 'plage_security_login_check';
    public const SUCCESS_ROUTE = 'plage_datastore_index';
    public const HOME_ROUTE = 'plage_home';

    private ClientRegistry $clientRegistry;
    private RequestStack $requestStack;
    private $urlGenerator;
    private $logger;

    public function __construct(ClientRegistry $clientRegistry, RequestStack $requestStack, UrlGeneratorInterface $urlGenerator, LoggerInterface $logger)
    {
        $this->clientRegistry = $clientRegistry;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
        $this->logger = $logger;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function supports(Request $request): ?bool
    {
        return self::LOGIN_CHECK_ROUTE === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        /** @var KeycloakClient */
        $keycloakClient = $this->clientRegistry->getClient('keycloak');

        $accessToken = $this->fetchAccessToken($keycloakClient);

        try {
            /** @var KeycloakResourceOwner */
            $keycloakUser = $keycloakClient->fetchUserFromToken($accessToken);
        } catch (IdentityProviderException $ex) {
            throw new CustomUserMessageAuthenticationException($ex->getMessage(), $ex->getResponseBody(), $ex->getCode(), $ex);
        }

        $this->requestStack->getSession()->set('keycloak_token', $accessToken);

        $userBadge = new UserBadge($keycloakUser->getEmail());

        return new SelfValidatingPassport($userBadge, [
            new RememberMeBadge(),
        ]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate(self::SUCCESS_ROUTE));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            /** @var IdentityProviderException */
            $ex = $exception->getPrevious(); // récupérer l'exception IdentityProviderException capturée dans la méthode Authenticate
            $details = $ex->getResponseBody();

            if (array_key_exists('error', $details) && array_key_exists('error_description', $details)) {
                if ('invalid_grant' == $details['error'] && 'Code not valid' == $details['error_description']) {
                    throw new AppException("Votre authentification a échoué en raison d'une erreur interne", Response::HTTP_UNAUTHORIZED, $details, $ex);
                }

                if ('invalid_grant' == $details['error'] && 'Invalid user credentials' == $details['error_description']) {
                    throw new AppException("Votre authentification a échoué : nom d'utilisateur et/ou mot de passe sont incorrects", Response::HTTP_UNAUTHORIZED, $details, $ex);
                }
            }

            throw $ex;
        }

        // l'exception lancée dans KeycloakUserProvider::getToken
        if ($exception instanceof TokenNotFoundException) {
            throw new AppException('Votre authentification a échoué', Response::HTTP_UNAUTHORIZED);
        }

        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        $this->logger->debug(self::class, [$message, $exception]);

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
