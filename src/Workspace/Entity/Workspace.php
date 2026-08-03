<?php

declare(strict_types=1);

namespace App\Workspace\Entity;

use App\User\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'workspaces')]
class Workspace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'workspace')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $settings = null;

    /** @var Collection<int, \App\Item\Entity\Item> */
    #[ORM\OneToMany(mappedBy: 'workspace', targetEntity: \App\Item\Entity\Item::class, cascade: ['remove'])]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function setSettings(?array $settings): static
    {
        $this->settings = $settings;
        return $this;
    }

    /** @return Collection<int, \App\Item\Entity\Item> */
    public function getItems(): Collection
    {
        return $this->items;
    }
}
