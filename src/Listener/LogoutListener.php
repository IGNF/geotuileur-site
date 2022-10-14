<?php

namespace App\Listener;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    private RequestStack $requestStack;
    private ClientRegistry $clientRegistry;

    public function __construct(RequestStack $requestStack, ClientRegistry $clientRegistry)
    {
        $this->requestStack = $requestStack;
        $this->clientRegistry = $clientRegistry;
    }

    /**
     * @SuppressWarnings(UnusedFormalParameter)
     */
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent)
    {
        $session = $this->requestStack->getSession();
        $session->remove('keycloak_token');
        $session->clear();

        /** @var Keycloak */
        $keycloak = $this->clientRegistry->getClient('keycloak')->getOAuth2Provider();

        return new RedirectResponse($keycloak->getLogoutUrl());
    }
}
