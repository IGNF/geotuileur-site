<?php

namespace App\Controller;

use App\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="plage_security_login", methods={"GET"})
     */
    public function login(UrlGeneratorInterface $urlGenerator)
    {
        $keycloakUrl = $this->getParameter('iam_url');
        $clientId = $this->getParameter('iam_client_id');
        $redirectUrl = $urlGenerator->generate('plage_security_login_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $nonce = Utils::generateUid();

        $url = "$keycloakUrl/auth?client_id=$clientId&response_type=code&scope=openid%20profile%20email&redirect_uri=$redirectUrl&nonce=$nonce";

        return new RedirectResponse($url);
    }

    /**
     * @Route("/login/check", name="plage_security_login_check", methods={"GET"})
     */
    public function loginCheck()
    {
    }

    /**
     * @Route("/logout", name="plage_security_logout", methods={"GET"})
     */
    public function logout()
    {
    }
}
