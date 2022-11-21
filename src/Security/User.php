<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    // keycloak user info
    private string $email;
    private string $username;
    private array $roles = ['ROLE_USER'];

    // api user info
    private string $id;
    private \DateTimeInterface $accountCreationDate;
    private string $firstName;
    private string $lastName;
    private \DateTimeInterface $lastApiCallDate;
    private array $communitiesMember = [];

    public function __construct(array $keycloakUserinfo = [], $apiUser = null)
    {
        $this->email = $keycloakUserinfo['email'];
        $this->username = $keycloakUserinfo['preferred_username'];

        if ($apiUser) {
            if (array_key_exists('administrator', $apiUser) && $apiUser['administrator']) {
                array_push($this->roles, 'ROLE_ADMIN');
            }

            $this->id = $apiUser['_id'];
            $this->accountCreationDate = new \DateTime($apiUser['creation']);
            $this->firstName = $apiUser['first_name'];
            $this->lastName = $apiUser['last_name'];
            $this->lastApiCallDate = new \DateTime($apiUser['last_call']);

            foreach ($apiUser['communities_member'] as $community) {
                $this->communitiesMember[$community['community']['_id']] = $community;
            }
        }
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get the value of username.
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * This method can be removed in Symfony 6.0 - is not needed for apps that do not check user passwords.
     *
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return null;
    }

    /**
     * This method can be removed in Symfony 6.0 - is not needed for apps that do not check user passwords.
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

    // ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAccountCreationDate(): ?\DateTimeInterface
    {
        return $this->accountCreationDate;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getLastApiCallDate(): ?\DateTimeInterface
    {
        return $this->lastApiCallDate;
    }

    public function getCommunitiesMember(): array
    {
        return $this->communitiesMember;
    }
}
