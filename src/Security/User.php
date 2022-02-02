<?php

namespace App\Security;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    private $username;
    private $email;
    private $roles = [];
    private $accessToken;
    private $refreshToken;
    private $tokenExpiryDate;

    public function __construct(array $userinfo = [])
    {
        if (array_key_exists('email', $userinfo)) {
            $this->setEmail($userinfo['email']);
        }

        if (array_key_exists('token', $userinfo)) {
            $this->setToken($userinfo['token']);
        }

        if (array_key_exists('preferred_username', $userinfo)) {
            $this->setUsername($userinfo['preferred_username']);
        }
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): string
    {
        return (string) $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * This method is not needed for apps that do not check user passwords.
     *
     * @see UserInterface
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * This method is not needed for apps that do not check user passwords.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(?string $accessToken): self
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getTokenExpiryDate(): ?DateTime
    {
        return $this->tokenExpiryDate;
    }

    public function setTokenExpiryDate(?DateTime $tokenExpiryDate): self
    {
        $this->tokenExpiryDate = $tokenExpiryDate;

        return $this;
    }

    public function setToken(array $token): self
    {
        $this->accessToken = $token['access_token'];
        $this->tokenExpiryDate = (new \DateTime())->add(new \DateInterval('PT'.$token['expires_in'].'S'));
        $this->refreshToken = $token['refresh_token'];

        return $this;
    }
}
