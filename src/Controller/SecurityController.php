<?php

namespace App\Controller;

use App\Security\User;
use App\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    /**
     * @Route("/login", name="plage_security_login", methods={"GET"})
     */
    public function login(UrlGeneratorInterface $urlGenerator, Request $request, TokenStorageInterface $tokenStorage, ParameterBagInterface $params)
    {
        if ('test' == $params->get('app_env')) {
            return $this->testLogin($tokenStorage, $request, $urlGenerator);
        }

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

    private function testLogin($tokenStorage, Request $request, $urlGenerator)
    {
        $user = new User([
            'preferred_username' => 'test_user',
            'email' => 'test@test.com',
        ]);

        $providerKey = 'main';
        $token = new UsernamePasswordToken($user, $providerKey, $user->getRoles());
        $tokenStorage->setToken($token);

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            $response = new RedirectResponse($targetPath);
        }

        $response = new RedirectResponse($urlGenerator->generate('plage_datastore_index'));

        return $response;
    }
}
