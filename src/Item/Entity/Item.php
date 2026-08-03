<?php

declare(strict_types=1);

namespace App\Item\Entity;

use App\Item\Enum\ItemType;
use App\Workspace\Entity\Workspace;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'items')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'children')]
    private ?Item $parent = null;

    /** @var Collection<int, Item> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Item::class)]
    private Collection $children;

    #[ORM\Column(type: 'integer', enumType: ItemType::class)]
    private ItemType $type;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    public function setWorkspace(Workspace $workspace): static
    {
        $this->workspace = $workspace;
        return $this;
    }

    public function getParent(): ?Item
    {
        return $this->parent;
    }

    public function setParent(?Item $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /** @return Collection<int, Item> */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function getType(): ItemType
    {
        return $this->type;
    }

    public function setType(ItemType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(?array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }
}
