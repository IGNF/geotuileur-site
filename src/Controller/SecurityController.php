<?php

namespace App\Controller;

use App\Security\User;
use App\Utils;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
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
    public function login(UrlGeneratorInterface $urlGenerator, Request $request, TokenStorageInterface $tokenStorage)
    {
        if (str_contains($request->headers->get('user-agent', ''), 'Cypress')) { // TODO cette condition ne suffit pas pour dire que c'est cypress, faudrait essayer avec un header particulier du coup
            // return $this->redirectToRoute('plage_security_login_check', ['code' => 'cypress-test']);
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
        $token = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());
        $tokenStorage->setToken($token);

        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            $response = new RedirectResponse($targetPath);
        }

        $response = new RedirectResponse($urlGenerator->generate('plage_datastore_index'));
        $response->headers->setCookie(Cookie::create('samesite', 'lax'));

        return $response;
    }
}
