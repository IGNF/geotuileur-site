<?php

namespace App\Controller;

use App\Security\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
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
    public function login(UrlGeneratorInterface $urlGenerator, Request $request, TokenStorageInterface $tokenStorage, ParameterBagInterface $params, ClientRegistry $clientRegistry)
    {
        if ('test' == $params->get('app_env')) {
            return $this->testLogin($tokenStorage, $request, $urlGenerator);
        }

        /** @var KeycloakClient */
        $client = $clientRegistry->getClient('keycloak');

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
