<?php

declare(strict_types=1);

namespace App\User\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personal_access_tokens')]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(unique: true)]
    private string $token;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    private ?string $plainTextToken = null;

    public function __construct(User $user, string $name, string $token, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->user = $user;
        $this->name = $name;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPlainTextToken(): ?string
    {
        return $this->plainTextToken;
    }

    public function setPlainTextToken(string $plainTextToken): static
    {
        $this->plainTextToken = $plainTextToken;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }
}
