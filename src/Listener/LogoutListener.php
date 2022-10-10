<?php

namespace App\Listener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    private $params;
    private $urlGenerator;
    private RequestStack $requestStack;

    public function __construct(ParameterBagInterface $params, UrlGeneratorInterface $urlGenerator, RequestStack $requestStack)
    {
        $this->params = $params;
        $this->urlGenerator = $urlGenerator;
        $this->requestStack = $requestStack;
    }

    /**
     * @SuppressWarnings(UnusedFormalParameter)
     */
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent)
    {
        $keycloakUrl = $this->params->get('iam_url').'/realms/'.$this->params->get('iam_realm').'/protocol/openid-connect';
        $clientId = $this->params->get('iam_client_id');
        $redirectUrl = $this->urlGenerator->generate('plage_home', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $url = "$keycloakUrl/logout?".http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUrl,
        ]);

        $session = $this->requestStack->getSession();
        $session->remove('keycloak_token');
        $session->clear();

        return new RedirectResponse($url);
    }
}
