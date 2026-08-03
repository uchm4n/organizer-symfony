<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\User\Enum\Role;
use App\User\Entity\ApiToken;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'string', enumType: Role::class)]
    private Role $role;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist'])]
    private ?\App\Workspace\Entity\Workspace $workspace = null;

    /** @var Collection<int, ApiToken> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiToken::class, cascade: ['remove'])]
    private Collection $apiTokens;

    public function __construct()
    {
        $this->apiTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): static
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }

    public function getWorkspace(): ?\App\Workspace\Entity\Workspace
    {
        return $this->workspace;
    }

    /** @return Collection<int, ApiToken> */
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    public function addApiToken(ApiToken $apiToken): static
    {
        if (!$this->apiTokens->contains($apiToken)) {
            $this->apiTokens->add($apiToken);
            $apiToken->setUser($this);
        }
        return $this;
    }

    public function removeApiToken(ApiToken $apiToken): static
    {
        if ($this->apiTokens->removeElement($apiToken)) {
            if ($apiToken->getUser() === $this) {
                $apiToken->setUser(null);
            }
        }
        return $this;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->role === Role::Admin) {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }

    public function eraseCredentials(): void
    {
    }
}
