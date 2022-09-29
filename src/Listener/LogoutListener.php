<?php

namespace App\Listener;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutListener implements LogoutSuccessHandlerInterface
{
    private $params;
    private $urlGenerator;
    private $session;

    public function __construct(ParameterBagInterface $params, UrlGeneratorInterface $urlGenerator, SessionInterface $session)
    {
        $this->params = $params;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }

    /**
     * @SuppressWarnings(UnusedFormalParameter)
     */
    public function onLogoutSuccess(Request $request)
    {
        $keycloakUrl = $this->params->get('iam_url');
        $clientId = $this->params->get('iam_client_id');
        $redirectUrl = $this->urlGenerator->generate('plage_home', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $url = "$keycloakUrl/logout?client_id=$clientId&redirect_uri=$redirectUrl";

        $this->session->clear();

        return new RedirectResponse($url);
    }
}
