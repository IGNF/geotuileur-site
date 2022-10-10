<?php

namespace App\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
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

        dump($accessToken);

        $this->requestStack->getSession()->set('keycloak_token', $accessToken);

        $userBadge = new UserBadge($accessToken->getToken());

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
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        $this->logger->debug(self::class, [$message, $exception]);

        return new Response($message, Response::HTTP_FORBIDDEN);
    }
}
