<?php

namespace App\Controller;

use App\Security\KeycloakTokenManager;
use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    /**
     * @Route("/login", name="plage_security_login", methods={"GET"}, options={"expose"=true})
     */
    public function login(UrlGeneratorInterface $urlGenerator, Request $request, TokenStorageInterface $tokenStorage, ParameterBagInterface $params, ClientRegistry $clientRegistry)
    {
        if ('test' == $params->get('app_env')) {
            return $this->testLogin($tokenStorage, $request, $urlGenerator);
        }

        /** @var KeycloakClient */
        $client = $clientRegistry->getClient('keycloak');

        if ($request->query->get('side_login', false)) {
            $request->getSession()->set('side_login', true);
        }

        return $client->redirect(['openid', 'profile', 'email']);
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

    /**
     * @Route("/check-auth", name="plage_security_check_auth", methods={"GET"}, options={"expose"=true})
     */
    public function checkAuth(Request $request): Response
    {
        try {
            $session = $request->getSession();
        } catch (\Throwable $th) {
            return $this->json(['is_authenticated' => false]);
        }

        /** @var ?AccessToken */
        $accessToken = $session->get(KeycloakTokenManager::KEYCLOAK_TOKEN_SESSION_KEY);
        $authenticated = false;

        if (null == $accessToken) { // token not found in session
            $authenticated = false;
        } elseif (($accessToken->getExpires() - 30) > time()) { // access token still valid
            $authenticated = true;
        } elseif (($accessToken->getExpires() + $accessToken->getValues()['refresh_expires_in'] - 30) > time()) { // refresh token still valid
            $authenticated = true;
        } else {
            $authenticated = false;
        }

        return $this->json([
            'is_authenticated' => $authenticated,
        ]);
    }

    /**
     * @Route("/login-success", name="plage_security_side_login_success")
     */
    public function backgroundLoginSuccess(Request $request, UrlGeneratorInterface $urlGenerator): Response
    {
        if (!$request->query->get('side_login', false)) {
            return new RedirectResponse($urlGenerator->generate('plage_home'));
        }

        $request->getSession()->remove('side_login');

        return $this->render('pages/security/side_login_success.html.twig');
    }

    private function testLogin($tokenStorage, Request $request, $urlGenerator)
    {
        $user = new User([
            'preferred_username' => 'test_user',
            'email' => 'test@test.com',
        ]);

        $firewallName = 'main';
        $token = new UsernamePasswordToken($user, $firewallName, $user->getRoles());
        $tokenStorage->setToken($token);

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            $response = new RedirectResponse($targetPath);
        }

        $response = new RedirectResponse($urlGenerator->generate('plage_datastore_index'));

        return $response;
    }
}
