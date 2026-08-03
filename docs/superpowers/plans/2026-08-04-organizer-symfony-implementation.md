# Organizer Symfony Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Symfony 8.1 application that is an exact functional analog of the Laravel Organizer app — a JSON-first personal workspace API.

**Architecture:** Domain-sliced architecture with Symfony Messenger as command bus, Doctrine ORM for persistence, SecurityBundle for bearer token auth. Each domain (Auth, User, Workspace, Item) has its own controllers, messages, handlers, and DTOs.

**Tech Stack:** PHP 8.5, Symfony 8.1, Doctrine ORM, Symfony Messenger, SecurityBundle, NelmioApiDocBundle, PHPUnit 11

---

## File Structure

```
src/
├── Auth/
│   ├── Controller/
│   │   └── LoginController.php
│   ├── Message/
│   │   └── LoginUser.php
│   ├── MessageHandler/
│   │   └── LoginUserHandler.php
│   └── Security/
│       ├── ApiTokenAuthenticator.php
│       ├── ApiTokenManager.php
│       └── UserProvider.php
├── User/
│   ├── Controller/
│   │   ├── UserController.php
│   │   └── UsersController.php
│   ├── DTO/
│   │   ├── UserData.php
│   │   └── UserCollectionData.php
│   ├── Entity/
│   │   ├── ApiToken.php
│   │   └── User.php
│   ├── Enum/
│   │   └── Role.php
│   ├── Message/
│   │   ├── GetUser.php
│   │   ├── GetUsers.php
│   │   └── UpdateUserRole.php
│   └── MessageHandler/
│       ├── GetUserHandler.php
│       ├── GetUsersHandler.php
│       └── UpdateUserRoleHandler.php
├── Workspace/
│   ├── Controller/
│   │   ├── WorkspaceController.php
│   │   ├── WorkspaceIndexController.php
│   │   └── WorkspaceShowController.php
│   ├── DTO/
│   │   └── WorkspaceData.php
│   ├── Entity/
│   │   └── Workspace.php
│   ├── Message/
│   │   ├── CreateWorkspace.php
│   │   ├── GetWorkspace.php
│   │   ├── GetWorkspaceItems.php
│   │   └── UpdateWorkspace.php
│   └── MessageHandler/
│       ├── CreateWorkspaceHandler.php
│       ├── GetWorkspaceHandler.php
│       ├── GetWorkspaceItemsHandler.php
│       └── UpdateWorkspaceHandler.php
├── Item/
│   ├── Controller/
│   │   ├── ItemIndexController.php
│   │   ├── ItemShowController.php
│   │   ├── ItemStoreController.php
│   │   ├── ItemUpdateController.php
│   │   └── ItemDestroyController.php
│   ├── DTO/
│   │   └── ItemData.php
│   ├── Entity/
│   │   └── Item.php
│   ├── Enum/
│   │   └── ItemType.php
│   ├── Message/
│   │   ├── CreateItem.php
│   │   ├── GetItem.php
│   │   ├── GetItems.php
│   │   ├── UpdateItem.php
│   │   └── DeleteItem.php
│   └── MessageHandler/
│       ├── CreateItemHandler.php
│       ├── GetItemHandler.php
│       ├── GetItemsHandler.php
│       ├── UpdateItemHandler.php
│       └── DeleteItemHandler.php
├── Shared/
│   ├── DTO/
│   │   ├── ProblemResponse.php
│   │   └── PaginatedCollection.php
│   ├── EventSubscriber/
│   │   ├── ApiVersionSubscriber.php
│   │   ├── CacheResponseSubscriber.php
│   │   ├── RateLimiterSubscriber.php
│   │   └── TraceIdSubscriber.php
│   ├── HttpKernel/
│   │   ├── ExceptionListener.php
│   │   └── ProblemRenderer.php
│   └── Logging/
│       ├── ExceptionLogger.php
│       └── SlimLineFormatter.php
└── Kernel.php
```

---

## Task 1: Install Dependencies & Configure Doctrine

**Files:**
- Modify: `composer.json`
- Create: `config/packages/doctrine.yaml`
- Create: `config/packages/messenger.yaml`
- Create: `config/packages/security.yaml`
- Create: `config/packages/rate_limiter.yaml`
- Create: `.env.test`

- [ ] **Step 1: Install Symfony packages**

```bash
composer require symfony/security-bundle symfony/validator symfony/rate-limiter symfony/http-cache doctrine/doctrine-bundle doctrine/orm doctrine/doctrine-fixtures-bundle symfony/serializer symfony/property-access symfony/property-info nelmio/api-doc-bundle phpunit/phpunit doctrine/doctrine-test-bundle symfony/browser-kit symfony/css-selector symfony/dom-crawler symfony/yaml --no-interaction
```

- [ ] **Step 2: Configure Doctrine for SQLite**

Create `config/packages/doctrine.yaml`:
```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        # IMPORTANT: You MUST configure your server version,
        # either here or in the DATABASE_URL env var (see .env file)
        #server_version: '13'
    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            App:
                is_bundle: false
                dir: '%kernel.project_dir%/src'
                prefix: 'App'
                alias: App
```

- [ ] **Step 3: Configure Messenger**

Create `config/packages/messenger.yaml`:
```yaml
framework:
    messenger:
        default_bus: command.bus
        buses:
            command.bus:
                middleware:
                    - validation
                    - doctrine_transaction
            query.bus:
                middleware:
                    - validation
```

- [ ] **Step 4: Configure Security**

Create `config/packages/security.yaml`:
```yaml
security:
    password_hashers:
        App\Entity\User: 'auto'

    providers:
        api_token_provider:
            id: App\Auth\Security\UserProvider

    firewalls:
        api:
            pattern: ^/api
            stateless: true
            custom_authenticators:
                - App\Auth\Security\ApiTokenAuthenticator

    access_control:
        - { path: ^/api/v1/login, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```

- [ ] **Step 5: Configure Rate Limiter**

Create `config/packages/rate_limiter.yaml`:
```yaml
framework:
    rate_limiter:
        api:
            policy: 'sliding_window'
            limit: 1000
            interval: '1 minute'
            cache_pool: 'cache.app'
        login:
            policy: 'sliding_window'
            limit: 5
            interval: '1 minute'
            cache_pool: 'cache.app'
```

- [ ] **Step 6: Configure test environment**

Create `.env.test`:
```
APP_ENV=test
DATABASE_URL="sqlite::memory:"
```

- [ ] **Step 7: Commit**

```bash
git add composer.json config/ .env.test
git commit -m "feat: install dependencies and configure Doctrine, Messenger, Security, RateLimiter"
```

---

## Task 2: Create Enums

**Files:**
- Create: `src/User/Enum/Role.php`
- Create: `src/Item/Enum/ItemType.php`

- [ ] **Step 1: Create Role enum**

Create `src/User/Enum/Role.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\Enum;

enum Role: string
{
    case Admin = 'admin';
    case User  = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User  => 'User',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isUser(): bool
    {
        return $this === self::User;
    }
}
```

- [ ] **Step 2: Create ItemType enum**

Create `src/Item/Enum/ItemType.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Enum;

enum ItemType: int
{
    case Note        = 1;
    case Todo        = 2;
    case Spreadsheet = 3;
    case TaxFiling   = 4;
    case Event       = 5;
    case Document    = 6;
    case Custom      = 99;

    public function label(): string
    {
        return match ($this) {
            self::Note        => 'Note',
            self::Todo        => 'Todo',
            self::Spreadsheet => 'Spreadsheet',
            self::TaxFiling   => 'TaxFiling',
            self::Event       => 'Event',
            self::Document    => 'Document',
            self::Custom      => 'Custom',
        };
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/User/Enum/ src/Item/Enum/
git commit -m "feat: add Role and ItemType enums"
```

---

## Task 3: Create User Entity

**Files:**
- Create: `src/User/Entity/User.php`

- [ ] **Step 1: Create User entity**

Create `src/User/Entity/User.php`:
```php
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
```

- [ ] **Step 2: Commit**

```bash
git add src/User/Entity/User.php
git commit -m "feat: add User entity"
```

---

## Task 4: Create ApiToken Entity

**Files:**
- Create: `src/User/Entity/ApiToken.php`

- [ ] **Step 1: Create ApiToken entity**

Create `src/User/Entity/ApiToken.php`:
```php
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
```

- [ ] **Step 2: Commit**

```bash
git add src/User/Entity/ApiToken.php
git commit -m "feat: add ApiToken entity"
```

---

## Task 5: Create Workspace Entity

**Files:**
- Create: `src/Workspace/Entity/Workspace.php`

- [ ] **Step 1: Create Workspace entity**

Create `src/Workspace/Entity/Workspace.php`:
```php
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
```

- [ ] **Step 2: Commit**

```bash
git add src/Workspace/Entity/Workspace.php
git commit -m "feat: add Workspace entity"
```

---

## Task 6: Create Item Entity

**Files:**
- Create: `src/Item/Entity/Item.php`

- [ ] **Step 1: Create Item entity**

Create `src/Item/Entity/Item.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Entity;

use App\Item\Enum\ItemType;
use App\Workspace\Entity\Workspace;
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

    #[ORM\Column(type: 'string', enumType: ItemType::class)]
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
```

- [ ] **Step 2: Commit**

```bash
git add src/Item/Entity/Item.php
git commit -m "feat: add Item entity"
```

---

## Task 7: Create Database Migrations

**Files:**
- Create: `migrations/Version20260804000001.php`

- [ ] **Step 1: Create migration**

```bash
php bin/console doctrine:migrations:diff --no-interaction
```

- [ ] **Step 2: Run migration**

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

- [ ] **Step 3: Commit**

```bash
git add migrations/
git commit -m "feat: add database migrations"
```

---

## Task 8: Create Shared DTOs

**Files:**
- Create: `src/Shared/DTO/ProblemResponse.php`
- Create: `src/Shared/DTO/PaginatedCollection.php`

- [ ] **Step 1: Create ProblemResponse**

Create `src/Shared/DTO/ProblemResponse.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\DTO;

final readonly class ProblemResponse
{
    public function __construct(
        public int $status,
        public string $title,
        public string $detail,
        public ?array $extra = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'title'  => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
        ];

        if ($this->extra !== null) {
            $data = array_merge($data, $this->extra);
        }

        return $data;
    }
}
```

- [ ] **Step 2: Create PaginatedCollection**

Create `src/Shared/DTO/PaginatedCollection.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\DTO;

final readonly class PaginatedCollection
{
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function toArray(): array
    {
        return [
            'data'  => $this->items,
            'meta'  => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Shared/DTO/
git commit -m "feat: add ProblemResponse and PaginatedCollection DTOs"
```

---

## Task 9: Create ProblemRenderer

**Files:**
- Create: `src/Shared/HttpKernel/ProblemRenderer.php`

- [ ] **Step 1: Create ProblemRenderer**

Create `src/Shared/HttpKernel/ProblemRenderer.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\HttpKernel;

use App\Shared\DTO\ProblemResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProblemRenderer
{
    public static function response(
        int $status,
        string $title,
        string $detail,
        array $extra = [],
    ): JsonResponse {
        $problem = new ProblemResponse(
            status: $status,
            title: $title,
            detail: $detail,
            extra: $extra,
        );

        return new JsonResponse(
            data: $problem->toArray(),
            status: $status,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }

    public static function titleForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Shared/HttpKernel/ProblemRenderer.php
git commit -m "feat: add ProblemRenderer for RFC 9457 responses"
```

---

## Task 10: Create TraceIdSubscriber

**Files:**
- Create: `src/Shared/EventSubscriber/TraceIdSubscriber.php`

- [ ] **Step 1: Create TraceIdSubscriber**

Create `src/Shared/EventSubscriber/TraceIdSubscriber.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TraceIdSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 70],
            KernelEvents::RESPONSE   => ['onResponse', -100],
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $traceId = $request->headers->get('X-Trace-Id')
            ?: bin2hex(random_bytes(4));

        $request->attributes->set('trace_id', $traceId);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $traceId = $request->attributes->get('trace_id');

        if ($traceId) {
            $event->getResponse()->headers->set('X-Trace-Id', $traceId);
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Shared/EventSubscriber/TraceIdSubscriber.php
git commit -m "feat: add TraceIdSubscriber for request trace IDs"
```

---

## Task 11: Create ApiVersionSubscriber

**Files:**
- Create: `src/Shared/EventSubscriber/ApiVersionSubscriber.php`
- Create: `src/Shared/Exception/UnsupportedApiVersionException.php`

- [ ] **Step 1: Create exception**

Create `src/Shared/Exception/UnsupportedApiVersionException.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class UnsupportedApiVersionException extends HttpException
{
    public function __construct(string $requested, array $supported)
    {
        parent::__construct(
            400,
            sprintf(
                'Unsupported API version "%s". Supported versions: %s.',
                $requested,
                implode(', ', $supported),
            ),
            null,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
```

- [ ] **Step 2: Create ApiVersionSubscriber**

Create `src/Shared/EventSubscriber/ApiVersionSubscriber.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use App\Shared\Exception\UnsupportedApiVersionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiVersionSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED = [1];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onControllerEvent', 100],
        ];
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $requested = $request->headers->get('X-API-Version');

        if ($requested === null) {
            $version = self::SUPPORTED[array_key_last(self::SUPPORTED)];
        } else {
            $version = filter_var($requested, FILTER_VALIDATE_INT);
            if ($version === false || !in_array($version, self::SUPPORTED, true)) {
                throw new UnsupportedApiVersionException($requested, self::SUPPORTED);
            }
        }

        $request->attributes->set('api.version', $version);
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Shared/EventSubscriber/ApiVersionSubscriber.php src/Shared/Exception/
git commit -m "feat: add ApiVersionSubscriber for header-based versioning"
```

---

## Task 12: Create ExceptionListener

**Files:**
- Create: `src/Shared/HttpKernel/ExceptionListener.php`

- [ ] **Step 1: Create ExceptionListener**

Create `src/Shared/HttpKernel/ExceptionListener.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\HttpKernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationException;

final class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', -128],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $response = match (true) {
            $exception instanceof ValidationException => ProblemRenderer::response(
                422,
                'Unprocessable Entity',
                'Validation failed.',
                ['errors' => $exception->getConstraint()->payload ?? []]
            ),
            $exception instanceof AuthenticationException => ProblemRenderer::response(
                401,
                'Unauthorized',
                'Invalid or expired token.'
            ),
            $exception instanceof AccessDeniedException => ProblemRenderer::response(
                403,
                'Forbidden',
                'Insufficient permissions.'
            ),
            $exception instanceof NotFoundHttpException => ProblemRenderer::response(
                404,
                'Not Found',
                'Resource not found.'
            ),
            $exception instanceof HttpException => ProblemRenderer::response(
                $exception->getStatusCode(),
                ProblemRenderer::titleForStatus($exception->getStatusCode()),
                $exception->getMessage()
            ),
            default => ProblemRenderer::response(
                500,
                'Internal Server Error',
                in_array($request->server->get('APP_ENV'), ['dev', 'test'])
                    ? $exception->getMessage()
                    : 'An unexpected error occurred. Please try again later.'
            ),
        };

        $event->setResponse($response);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Shared/HttpKernel/ExceptionListener.php
git commit -m "feat: add ExceptionListener for Problem+JSON error responses"
```

---

## Task 13: Create Auth Domain - LoginUser Message

**Files:**
- Create: `src/Auth/Message/LoginUser.php`

- [ ] **Step 1: Create LoginUser message**

Create `src/Auth/Message/LoginUser.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\Message;

final readonly class LoginUser
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Auth/Message/LoginUser.php
git commit -m "feat: add LoginUser message"
```

---

## Task 14: Create Auth Domain - ApiTokenManager

**Files:**
- Create: `src/Auth/Security/ApiTokenManager.php`

- [ ] **Step 1: Create ApiTokenManager**

Create `src/Auth/Security/ApiTokenManager.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\Security;

use App\User\Entity\ApiToken;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ApiTokenManager
{
    private const TOKEN_TTL_MINUTES = 2880; // 2 days

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function createToken(User $user, string $name, ?int $ttlMinutes = null): ApiToken
    {
        $ttlMinutes ??= self::TOKEN_TTL_MINUTES;

        // Revoke existing tokens
        foreach ($user->getApiTokens() as $existing) {
            $this->em->remove($existing);
        }

        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $token = new ApiToken(
            user: $user,
            name: $name,
            token: $hashedToken,
            expiresAt: new \DateTimeImmutable("+{$ttlMinutes} minutes"),
        );

        $this->em->persist($token);
        $this->em->flush();

        $token->setPlainTextToken($plainToken);
        return $token;
    }

    public function checkPassword(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function findValidToken(string $hashedToken): ?ApiToken
    {
        $token = $this->em->getRepository(ApiToken::class)
            ->findOneBy(['token' => $hashedToken]);

        if ($token === null || $token->isExpired()) {
            return null;
        }

        return $token;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Auth/Security/ApiTokenManager.php
git commit -m "feat: add ApiTokenManager for token operations"
```

---

## Task 15: Create Auth Domain - UserProvider

**Files:**
- Create: `src/Auth/Security/UserProvider.php`

- [ ] **Step 1: Create UserProvider**

Create `src/Auth/Security/UserProvider.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\Security;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $identifier]);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }

        $refreshed = $this->em->getRepository(User::class)
            ->find($user->getId());

        if ($refreshed === null) {
            throw new UserNotFoundException();
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Auth/Security/UserProvider.php
git commit -m "feat: add UserProvider for Symfony security"
```

---

## Task 16: Create Auth Domain - ApiTokenAuthenticator

**Files:**
- Create: `src/Auth/Security/ApiTokenAuthenticator.php`

- [ ] **Step 1: Create ApiTokenAuthenticator**

Create `src/Auth/Security/ApiTokenAuthenticator.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\Security;

use App\Shared\HttpKernel\ProblemRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiTokenManager $tokenManager,
    ) {}

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = $request->headers->get('Authorization');
        $plainToken = substr($header, 7);

        $hashedToken = hash('sha256', $plainToken);
        $token = $this->tokenManager->findValidToken($hashedToken);

        if ($token === null) {
            throw new AuthenticationException('Invalid or expired token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($token->getUser()->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, UserInterface $user): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return ProblemRenderer::response(
            401,
            'Unauthorized',
            $exception->getMessage()
        );
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Auth/Security/ApiTokenAuthenticator.php
git commit -m "feat: add ApiTokenAuthenticator for bearer token auth"
```

---

## Task 17: Create Auth Domain - LoginUserHandler

**Files:**
- Create: `src/Auth/MessageHandler/LoginUserHandler.php`
- Create: `src/Auth/Exception/InvalidCredentialsException.php`

- [ ] **Step 1: Create exception**

Create `src/Auth/Exception/InvalidCredentialsException.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class InvalidCredentialsException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
```

- [ ] **Step 2: Create LoginUserHandler**

Create `src/Auth/MessageHandler/LoginUserHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\MessageHandler;

use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Message\LoginUser;
use App\Auth\Security\ApiTokenManager;
use App\User\Entity\ApiToken;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class LoginUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApiTokenManager $tokenManager,
    ) {}

    public function __invoke(LoginUser $message): ApiToken
    {
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $message->email]);

        if ($user === null || !$this->tokenManager->checkPassword($user, $message->password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenManager->createToken($user, 'api-token');
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/Auth/MessageHandler/LoginUserHandler.php src/Auth/Exception/
git commit -m "feat: add LoginUserHandler for authentication"
```

---

## Task 18: Create Auth Domain - LoginController

**Files:**
- Create: `src/Auth/Controller/LoginController.php`

- [ ] **Step 1: Create LoginController**

Create `src/Auth/Controller/LoginController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Message\LoginUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/login', name: 'api.v1.auth.login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $message = new LoginUser(
            email: $request->request->get('email'),
            password: $request->request->get('password'),
        );

        $token = $this->commandBus->dispatch($message);

        return $this->json([
            'access_token' => $token->getPlainTextToken(),
            'token_type'   => 'Bearer',
        ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Auth/Controller/LoginController.php
git commit -m "feat: add LoginController for authentication endpoint"
```

---

## Task 19: Create User Domain - GetUser/GetUsers Messages

**Files:**
- Create: `src/User/Message/GetUser.php`
- Create: `src/User/Message/GetUsers.php`
- Create: `src/User/Message/UpdateUserRole.php`

- [ ] **Step 1: Create GetUser message**

Create `src/User/Message/GetUser.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\Message;

final readonly class GetUser
{
    public function __construct(
        public int $userId,
    ) {}
}
```

- [ ] **Step 2: Create GetUsers message**

Create `src/User/Message/GetUsers.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\Message;

final readonly class GetUsers
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
```

- [ ] **Step 3: Create UpdateUserRole message**

Create `src/User/Message/UpdateUserRole.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\Message;

use App\User\Enum\Role;

final readonly class UpdateUserRole
{
    public function __construct(
        public int $userId,
        public Role $role,
    ) {}
}
```

- [ ] **Step 4: Commit**

```bash
git add src/User/Message/
git commit -m "feat: add User messages (GetUser, GetUsers, UpdateUserRole)"
```

---

## Task 20: Create User Domain - MessageHandlers

**Files:**
- Create: `src/User/MessageHandler/GetUserHandler.php`
- Create: `src/User/MessageHandler/GetUsersHandler.php`
- Create: `src/User/MessageHandler/UpdateUserRoleHandler.php`

- [ ] **Step 1: Create GetUserHandler**

Create `src/User/MessageHandler/GetUserHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\MessageHandler;

use App\User\Entity\User;
use App\User\Message\GetUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetUser $message): User
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        if ($user === null) {
            throw new UnrecoverableMessageException('User not found.');
        }

        return $user;
    }
}
```

- [ ] **Step 2: Create GetUsersHandler**

Create `src/User/MessageHandler/GetUsersHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\MessageHandler;

use App\Shared\DTO\PaginatedCollection;
use App\User\Entity\User;
use App\User\Message\GetUsers;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUsersHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetUsers $message): PaginatedCollection
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($message->perPage)
            ->setFirstResult(($message->page - 1) * $message->perPage);

        $paginator = new Paginator($qb->getQuery());

        $totalQuery = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u');

        $total = (int) $totalQuery->getQuery()->getSingleScalarResult();

        return new PaginatedCollection(
            items: iterator_to_array($paginator),
            total: $total,
            page: $message->page,
            perPage: $message->perPage,
        );
    }
}
```

- [ ] **Step 3: Create UpdateUserRoleHandler**

Create `src/User/MessageHandler/UpdateUserRoleHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\MessageHandler;

use App\User\Entity\User;
use App\User\Message\UpdateUserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler]
final class UpdateUserRoleHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateUserRole $message): User
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        if ($user === null) {
            throw new UnrecoverableMessageException('User not found.');
        }

        $user->setRole($message->role);
        $this->em->flush();

        return $user;
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/User/MessageHandler/
git commit -m "feat: add User message handlers"
```

---

## Task 21: Create User Domain - DTOs

**Files:**
- Create: `src/User/DTO/UserData.php`
- Create: `src/User/DTO/UserCollectionData.php`

- [ ] **Step 1: Create UserData**

Create `src/User/DTO/UserData.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\User;

final readonly class UserData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            role: $user->getRole()->value,
        );
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
        ];
    }
}
```

- [ ] **Step 2: Create UserCollectionData**

Create `src/User/DTO/UserCollectionData.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\Shared\DTO\PaginatedCollection;
use App\User\Entity\User;

final readonly class UserCollectionData
{
    public function __construct(
        public array $users,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public static function fromPaginatedCollection(PaginatedCollection $collection): self
    {
        return new self(
            users: array_map(
                fn (User $user) => UserData::fromEntity($user),
                $collection->items
            ),
            total: $collection->total,
            page: $collection->page,
            perPage: $collection->perPage,
        );
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(
                fn (UserData $user) => $user->toArray(),
                $this->users
            ),
            'meta' => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/User/DTO/
git commit -m "feat: add User DTOs"
```

---

## Task 22: Create User Domain - Controllers

**Files:**
- Create: `src/User/Controller/UserController.php`
- Create: `src/User/Controller/UsersController.php`

- [ ] **Step 1: Create UserController**

Create `src/User/Controller/UserController.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\DTO\UserData;
use App\User\Message\GetUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/user', name: 'api.v1.user.show', methods: ['GET'])]
final class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        return $this->json(UserData::fromEntity($user)->toArray());
    }
}
```

- [ ] **Step 2: Create UsersController**

Create `src/User/Controller/UsersController.php`:
```php
<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\DTO\UserCollectionData;
use App\User\Message\GetUsers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users', name: 'api.v1.user.index', methods: ['GET'])]
final class UsersController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        $collection = $this->queryBus->dispatch(new GetUsers());

        return $this->json(UserCollectionData::fromPaginatedCollection($collection)->toArray());
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add src/User/Controller/
git commit -m "feat: add User controllers"
```

---

## Task 23: Create Workspace Domain - Messages & Handlers

**Files:**
- Create: `src/Workspace/Message/CreateWorkspace.php`
- Create: `src/Workspace/Message/GetWorkspace.php`
- Create: `src/Workspace/Message/GetWorkspaceItems.php`
- Create: `src/Workspace/Message/UpdateWorkspace.php`
- Create: `src/Workspace/MessageHandler/CreateWorkspaceHandler.php`
- Create: `src/Workspace/MessageHandler/GetWorkspaceHandler.php`
- Create: `src/Workspace/MessageHandler/GetWorkspaceItemsHandler.php`
- Create: `src/Workspace/MessageHandler/UpdateWorkspaceHandler.php`
- Create: `src/Workspace/DTO/WorkspaceData.php`

- [ ] **Step 1: Create messages**

Create `src/Workspace/Message/CreateWorkspace.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class CreateWorkspace
{
    public function __construct(
        public int $userId,
        public string $name,
        public ?array $settings = null,
    ) {}
}
```

Create `src/Workspace/Message/GetWorkspace.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class GetWorkspace
{
    public function __construct(
        public int $userId,
    ) {}
}
```

Create `src/Workspace/Message/GetWorkspaceItems.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class GetWorkspaceItems
{
    public function __construct(
        public int $workspaceId,
    ) {}
}
```

Create `src/Workspace/Message/UpdateWorkspace.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class UpdateWorkspace
{
    public function __construct(
        public int $workspaceId,
        public string $name,
        public ?array $settings = null,
    ) {}
}
```

- [ ] **Step 2: Create handlers**

Create `src/Workspace/MessageHandler/CreateWorkspaceHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\User\Entity\User;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\CreateWorkspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateWorkspaceHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CreateWorkspace $message): Workspace
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        $workspace = new Workspace();
        $workspace->setUser($user);
        $workspace->setName($message->name);
        $workspace->setSettings($message->settings);

        $this->em->persist($workspace);
        $this->em->flush();

        return $workspace;
    }
}
```

Create `src/Workspace/MessageHandler/GetWorkspaceHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\User\Entity\User;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\GetWorkspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkspaceHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetWorkspace $message): Workspace
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        if ($user === null || $user->getWorkspace() === null) {
            throw new UnrecoverableMessageException('Workspace not found.');
        }

        return $user->getWorkspace();
    }
}
```

Create `src/Workspace/MessageHandler/GetWorkspaceItemsHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\Item\Entity\Item;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\GetWorkspaceItems;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkspaceItemsHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetWorkspaceItems $message): array
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        if ($workspace === null) {
            throw new UnrecoverableMessageException('Workspace not found.');
        }

        return $this->em->getRepository(Item::class)
            ->findBy(['workspace' => $workspace, 'parent' => null], ['sortOrder' => 'ASC']);
    }
}
```

Create `src/Workspace/MessageHandler/UpdateWorkspaceHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\Workspace\Entity\Workspace;
use App\Workspace\Message\UpdateWorkspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler]
final class UpdateWorkspaceHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateWorkspace $message): Workspace
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        if ($workspace === null) {
            throw new UnrecoverableMessageException('Workspace not found.');
        }

        $workspace->setName($message->name);
        $workspace->setSettings($message->settings);

        $this->em->flush();

        return $workspace;
    }
}
```

- [ ] **Step 3: Create DTO**

Create `src/Workspace/DTO/WorkspaceData.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\DTO;

use App\Workspace\Entity\Workspace;

final readonly class WorkspaceData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?array $settings,
    ) {}

    public static function fromEntity(Workspace $workspace): self
    {
        return new self(
            id: $workspace->getId(),
            name: $workspace->getName(),
            settings: $workspace->getSettings(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'settings' => $this->settings,
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Workspace/
git commit -m "feat: add Workspace domain messages, handlers, and DTOs"
```

---

## Task 24: Create Workspace Domain - Controllers

**Files:**
- Create: `src/Workspace/Controller/WorkspaceController.php`
- Create: `src/Workspace/Controller/WorkspaceShowController.php`
- Create: `src/Workspace/Controller/WorkspaceIndexController.php`

- [ ] **Step 1: Create controllers**

Create `src/Workspace/Controller/WorkspaceController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Workspace\DTO\WorkspaceData;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\Message\GetWorkspace;
use App\Workspace\Message\UpdateWorkspace;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspace', name: 'api.v1.workspace.', methods: ['GET|POST|PATCH'])]
final class WorkspaceController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $this->queryBus->dispatch(new GetWorkspace($user->getId()));

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }

    #[Route('', name: 'store', methods: ['POST'])]
    public function store(Request $request): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $this->commandBus->dispatch(new CreateWorkspace(
            userId: $user->getId(),
            name: $request->request->get('name'),
            settings: $request->request->all('settings'),
        ));

        return $this->json(
            WorkspaceData::fromEntity($workspace)->toArray(),
            Response::HTTP_CREATED,
        );
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $this->queryBus->dispatch(new GetWorkspace($user->getId()));

        $workspace = $this->commandBus->dispatch(new UpdateWorkspace(
            workspaceId: $workspace->getId(),
            name: $request->request->get('name'),
            settings: $request->request->all('settings'),
        ));

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }
}
```

Create `src/Workspace/Controller/WorkspaceShowController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Workspace\DTO\WorkspaceData;
use App\Workspace\Message\GetWorkspace;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspaces/{workspaceId}', name: 'api.v1.workspace.general.', methods: ['GET'])]
final class WorkspaceShowController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function __invoke(int $workspaceId): JsonResponse
    {
        $workspace = $this->queryBus->dispatch(new GetWorkspace($workspaceId));

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }
}
```

Create `src/Workspace/Controller/WorkspaceIndexController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Item\DTO\ItemData;
use App\Workspace\Message\GetWorkspaceItems;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspaces/{workspaceId}/items', name: 'api.v1.workspace.items.', methods: ['GET'])]
final class WorkspaceIndexController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function __invoke(int $workspaceId): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetWorkspaceItems($workspaceId));

        return $this->json([
            'data' => array_map(
                fn ($item) => ItemData::fromEntity($item)->toArray(),
                $items
            ),
        ]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Workspace/Controller/
git commit -m "feat: add Workspace controllers"
```

---

## Task 25: Create Item Domain - Messages & Handlers

**Files:**
- Create: `src/Item/Message/CreateItem.php`
- Create: `src/Item/Message/GetItem.php`
- Create: `src/Item/Message/GetItems.php`
- Create: `src/Item/Message/UpdateItem.php`
- Create: `src/Item/Message/DeleteItem.php`
- Create: `src/Item/MessageHandler/CreateItemHandler.php`
- Create: `src/Item/MessageHandler/GetItemHandler.php`
- Create: `src/Item/MessageHandler/GetItemsHandler.php`
- Create: `src/Item/MessageHandler/UpdateItemHandler.php`
- Create: `src/Item/MessageHandler/DeleteItemHandler.php`
- Create: `src/Item/DTO/ItemData.php`

- [ ] **Step 1: Create messages**

Create `src/Item/Message/CreateItem.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Message;

use App\Item\Enum\ItemType;

final readonly class CreateItem
{
    public function __construct(
        public int $workspaceId,
        public ItemType $type,
        public string $title,
        public ?int $parentId = null,
        public ?array $data = null,
        public int $sortOrder = 0,
    ) {}
}
```

Create `src/Item/Message/GetItem.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Message;

final readonly class GetItem
{
    public function __construct(
        public int $itemId,
    ) {}
}
```

Create `src/Item/Message/GetItems.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Message;

final readonly class GetItems
{
    public function __construct(
        public int $workspaceId,
        public ?int $parentId = null,
    ) {}
}
```

Create `src/Item/Message/UpdateItem.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Message;

use App\Item\Enum\ItemType;

final readonly class UpdateItem
{
    public function __construct(
        public int $itemId,
        public ?string $title = null,
        public ?ItemType $type = null,
        public ?array $data = null,
        public ?int $sortOrder = null,
    ) {}
}
```

Create `src/Item/Message/DeleteItem.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Message;

final readonly class DeleteItem
{
    public function __construct(
        public int $itemId,
    ) {}
}
```

- [ ] **Step 2: Create handlers**

Create `src/Item/MessageHandler/CreateItemHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\CreateItem;
use App\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CreateItem $message): Item
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        $parent = null;
        if ($message->parentId !== null) {
            $parent = $this->em->getRepository(Item::class)
                ->find($message->parentId);
        }

        $item = new Item();
        $item->setWorkspace($workspace);
        $item->setType($message->type);
        $item->setTitle($message->title);
        $item->setParent($parent);
        $item->setData($message->data);
        $item->setSortOrder($message->sortOrder);

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }
}
```

Create `src/Item/MessageHandler/GetItemHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\GetItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetItem $message): Item
    {
        $item = $this->em->getRepository(Item::class)
            ->find($message->itemId);

        if ($item === null) {
            throw new UnrecoverableMessageException('Item not found.');
        }

        return $item;
    }
}
```

Create `src/Item/MessageHandler/GetItemsHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\GetItems;
use App\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetItemsHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetItems $message): array
    {
        $criteria = ['workspace' => $message->workspaceId];
        if ($message->parentId !== null) {
            $criteria['parent'] = $message->parentId;
        }

        return $this->em->getRepository(Item::class)
            ->findBy($criteria, ['sortOrder' => 'ASC']);
    }
}
```

Create `src/Item/MessageHandler/UpdateItemHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\UpdateItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler]
final class UpdateItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateItem $message): Item
    {
        $item = $this->em->getRepository(Item::class)
            ->find($message->itemId);

        if ($item === null) {
            throw new UnrecoverableMessageException('Item not found.');
        }

        if ($message->title !== null) {
            $item->setTitle($message->title);
        }
        if ($message->type !== null) {
            $item->setType($message->type);
        }
        if ($message->data !== null) {
            $item->setData($message->data);
        }
        if ($message->sortOrder !== null) {
            $item->setSortOrder($message->sortOrder);
        }

        $this->em->flush();

        return $item;
    }
}
```

Create `src/Item/MessageHandler/DeleteItemHandler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\DeleteItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler]
final class DeleteItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(DeleteItem $message): void
    {
        $item = $this->em->getRepository(Item::class)
            ->find($message->itemId);

        if ($item === null) {
            throw new UnrecoverableMessageException('Item not found.');
        }

        $this->em->remove($item);
        $this->em->flush();
    }
}
```

- [ ] **Step 3: Create DTO**

Create `src/Item/DTO/ItemData.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\DTO;

use App\Item\Entity\Item;

final readonly class ItemData
{
    public function __construct(
        public int $id,
        public int $workspaceId,
        public ?int $parentId,
        public string $type,
        public string $title,
        public ?array $data,
        public int $sortOrder,
    ) {}

    public static function fromEntity(Item $item): self
    {
        return new self(
            id: $item->getId(),
            workspaceId: $item->getWorkspace()->getId(),
            parentId: $item->getParent()?->getId(),
            type: $item->getType()->value,
            title: $item->getTitle(),
            data: $item->getData(),
            sortOrder: $item->getSortOrder(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspaceId,
            'parent_id'    => $this->parentId,
            'type'         => $this->type,
            'title'        => $this->title,
            'data'         => $this->data,
            'sort_order'   => $this->sortOrder,
        ];
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add src/Item/
git commit -m "feat: add Item domain messages, handlers, and DTOs"
```

---

## Task 26: Create Item Domain - Controllers

**Files:**
- Create: `src/Item/Controller/ItemIndexController.php`
- Create: `src/Item/Controller/ItemShowController.php`
- Create: `src/Item/Controller/ItemStoreController.php`
- Create: `src/Item/Controller/ItemUpdateController.php`
- Create: `src/Item/Controller/ItemDestroyController.php`

- [ ] **Step 1: Create controllers**

Create `src/Item/Controller/ItemIndexController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Message\GetItems;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items', name: 'api.v1.item.index', methods: ['GET'])]
final class ItemIndexController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $user->getWorkspace();
        if ($workspace === null) {
            return $this->json(['data' => []]);
        }

        $items = $this->queryBus->dispatch(new GetItems($workspace->getId()));

        return $this->json([
            'data' => array_map(
                fn ($item) => ItemData::fromEntity($item)->toArray(),
                $items
            ),
        ]);
    }
}
```

Create `src/Item/Controller/ItemShowController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Message\GetItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.show', methods: ['GET'])]
final class ItemShowController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(int $itemId): JsonResponse
    {
        $item = $this->queryBus->dispatch(new GetItem($itemId));

        return $this->json(ItemData::fromEntity($item)->toArray());
    }
}
```

Create `src/Item/Controller/ItemStoreController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Enum\ItemType;
use App\Item\Message\CreateItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items', name: 'api.v1.item.store', methods: ['POST'])]
final class ItemStoreController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $message = new CreateItem(
            workspaceId: (int) $request->request->get('workspace_id'),
            type: ItemType::from((int) $request->request->get('type')),
            title: $request->request->get('title'),
            parentId: $request->request->get('parent_id') ? (int) $request->request->get('parent_id') : null,
            data: $request->request->all('data'),
            sortOrder: (int) $request->request->get('sort_order', 0),
        );

        $item = $this->commandBus->dispatch($message);

        return $this->json(
            ItemData::fromEntity($item)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
```

Create `src/Item/Controller/ItemUpdateController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Enum\ItemType;
use App\Item\Message\UpdateItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.update', methods: ['PATCH'])]
final class ItemUpdateController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(int $itemId, Request $request): JsonResponse
    {
        $message = new UpdateItem(
            itemId: $itemId,
            title: $request->request->get('title'),
            type: $request->request->get('type') ? ItemType::from((int) $request->request->get('type')) : null,
            data: $request->request->all('data'),
            sortOrder: $request->request->has('sort_order') ? (int) $request->request->get('sort_order') : null,
        );

        $item = $this->commandBus->dispatch($message);

        return $this->json(ItemData::fromEntity($item)->toArray());
    }
}
```

Create `src/Item/Controller/ItemDestroyController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\Message\DeleteItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.destroy', methods: ['DELETE'])]
final class ItemDestroyController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(int $itemId): Response
    {
        $this->commandBus->dispatch(new DeleteItem($itemId));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Item/Controller/
git commit -m "feat: add Item controllers"
```

---

## Task 27: Create Logging - SlimLineFormatter

**Files:**
- Create: `src/Shared/Logging/SlimLineFormatter.php`
- Create: `config/packages/monolog.yaml`

- [ ] **Step 1: Create SlimLineFormatter**

Create `src/Shared/Logging/SlimLineFormatter.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use Monolog\Formatter\LineFormatter;

final class SlimLineFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct(
            format: "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            dateFormat: 'Y-m-d H:i:s',
            inlineOpen: '{',
            inlineClose: '}',
        );

        $this->setBasePath(dirname($this->basePath));
        $this->ignoreEmptyContextAndExtra();
    }
}
```

- [ ] **Step 2: Configure Monolog**

Create `config/packages/monolog.yaml`:
```yaml
monolog:
    channels:
        - deprecation # Deprecations are logged in its own channel
    handlers:
        default:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: debug
            formatter: app.formatter.slim
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine", "!deprecation"]
        deprecation:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.deprecation.log"
            channels: [deprecation]
            process_psr_3_messages: false

services:
    app.formatter.slim:
        class: App\Shared\Logging\SlimLineFormatter
```

- [ ] **Step 3: Commit**

```bash
git add src/Shared/Logging/ config/packages/monolog.yaml
git commit -m "feat: add SlimLineFormatter for slim log output"
```

---

## Task 28: Create ExceptionLogger

**Files:**
- Create: `src/Shared/Logging/ExceptionLogger.php`

- [ ] **Step 1: Create ExceptionLogger**

Create `src/Shared/Logging/ExceptionLogger.php`:
```php
<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use App\Shared\HttpKernel\ProblemRenderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExceptionLogger
{
    private const CLIENT_NOISE = [
        AuthenticationException::class,
        NotFoundHttpException::class,
    ];

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {}

    public function log(\Throwable $exception, int $status): void
    {
        $level = $this->determineLevel($exception, $status);
        $request = $this->requestStack->getCurrentRequest();

        $context = [
            'status'   => $status,
            'route'    => $request?->attributes->get('_route', $request->getPathInfo()),
            'method'   => $request?->getMethod(),
            'url'      => $request?->getUri(),
            'ip'       => $request?->getClientIp(),
            'trace_id' => $request?->attributes->get('trace_id'),
            'user'     => $request->getUser()?->getId(),
        ];

        $message = sprintf(
            '%d %s %s "%s" at %s:%d',
            $status,
            ProblemRenderer::titleForStatus($status),
            $exception::class,
            $this->cleanMessage($exception->getMessage()),
            $this->relativePath($exception->getFile()),
            $exception->getLine(),
        );

        $this->logger->$level($message, $context);
    }

    private function determineLevel(\Throwable $exception, int $status): string
    {
        return match (true) {
            $status >= 500 => 'error',
            in_array($status, [403, 422, 429]) => 'warning',
            $this->isClientNoise($exception) => 'info',
            default => 'info',
        };
    }

    private function isClientNoise(\Throwable $exception): bool
    {
        foreach (self::CLIENT_NOISE as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }
        return false;
    }

    private function cleanMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'no message';
        }
        return preg_replace('/\s+/', ' ', $message) ?? $message;
    }

    private function relativePath(string $path): string
    {
        $base = dirname(__DIR__, 2);
        return str_starts_with($path, $base) ? substr($path, strlen($base) + 1) : $path;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add src/Shared/Logging/ExceptionLogger.php
git commit -m "feat: add ExceptionLogger for tiered logging"
```

---

## Task 29: Configure Services

**Files:**
- Modify: `config/services.yaml`

- [ ] **Step 1: Update services.yaml**

Update `config/services.yaml`:
```yaml
parameters:

services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/DependencyInjection/'
            - '../src/Entity/'
            - '../src/Kernel.php'

    App\Shared\Logging\ExceptionLogger:
        arguments:
            $logger: '@monolog.logger.api'

    App\Shared\HttpKernel\ExceptionListener:
        tags:
            - { name: kernel.event_subscriber }

    App\Shared\EventSubscriber\:
        resource: '../src/Shared/EventSubscriber/'
        tags: ['kernel.event_subscriber']
```

- [ ] **Step 2: Commit**

```bash
git add config/services.yaml
git commit -m "feat: configure services and event subscribers"
```

---

## Task 30: Create Tests

**Files:**
- Create: `tests/Auth/MessageHandler/LoginUserHandlerTest.php`
- Create: `tests/Auth/Controller/LoginControllerTest.php`
- Create: `tests/Shared/EventSubscriber/TraceIdSubscriberTest.php`
- Create: `tests/Shared/EventSubscriber/ApiVersionSubscriberTest.php`
- Create: `tests/Shared/HttpKernel/ExceptionListenerTest.php`

- [ ] **Step 1: Create LoginUserHandlerTest**

Create `tests/Auth/MessageHandler/LoginUserHandlerTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Auth\MessageHandler;

use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Message\LoginUser;
use App\Auth\MessageHandler\LoginUserHandler;
use App\Auth\Security\ApiTokenManager;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginUserHandlerTest extends TestCase
{
    private $em;
    private $tokenManager;
    private LoginUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenManager = $this->createMock(ApiTokenManager::class);
        $this->handler = new LoginUserHandler($this->em, $this->tokenManager);
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $this->tokenManager->expects($this->once())
            ->method('checkPassword')
            ->with($user, 'password')
            ->willReturn(true);

        $token = $this->createMock(\App\User\Entity\ApiToken::class);
        $this->tokenManager->expects($this->once())
            ->method('createToken')
            ->with($user, 'api-token')
            ->willReturn($token);

        $result = ($this->handler)(new LoginUser('test@example.com', 'password'));

        $this->assertSame($token, $result);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        ($this->handler)(new LoginUser('wrong@example.com', 'wrong'));
    }
}
```

- [ ] **Step 2: Create LoginControllerTest**

Create `tests/Auth/Controller/LoginControllerTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Auth\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLoginEndpointReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseHeaderSame('content-type', 'application/json');
    }
}
```

- [ ] **Step 3: Create TraceIdSubscriberTest**

Create `tests/Shared/EventSubscriber/TraceIdSubscriberTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Shared\EventSubscriber;

use App\Shared\EventSubscriber\TraceIdSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class TraceIdSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = TraceIdSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    }
}
```

- [ ] **Step 4: Create ApiVersionSubscriberTest**

Create `tests/Shared/EventSubscriber/ApiVersionSubscriberTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Shared\EventSubscriber;

use App\Shared\EventSubscriber\ApiVersionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiVersionSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ApiVersionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
    }
}
```

- [ ] **Step 5: Create ExceptionListenerTest**

Create `tests/Shared/HttpKernel/ExceptionListenerTest.php`:
```php
<?php

declare(strict_types=1);

namespace App\Tests\Shared\HttpKernel;

use App\Shared\HttpKernel\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListenerTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ExceptionListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }
}
```

- [ ] **Step 6: Commit**

```bash
git add tests/
git commit -m "feat: add unit and functional tests"
```

---

## Task 31: Run Tests & Fix Issues

- [ ] **Step 1: Run tests**

```bash
php bin/phpunit
```

- [ ] **Step 2: Fix any failing tests**

Review test output and fix any issues found.

- [ ] **Step 3: Run static analysis**

```bash
php bin/phpstan analyse
```

- [ ] **Step 4: Commit fixes**

```bash
git add -A
git commit -m "fix: resolve test failures and static analysis issues"
```

---

## Task 32: Final Verification

- [ ] **Step 1: Run full test suite**

```bash
php bin/phpunit --testdox
```

- [ ] **Step 2: Verify all endpoints work**

```bash
php bin/console debug:router
```

- [ ] **Step 3: Final commit**

```bash
git add -A
git commit -m "feat: complete Symfony port of Organizer app"
```

---

## Summary

This plan implements the complete Symfony port of the Organizer Laravel app with:

- **4 domains**: Auth, User, Workspace, Item
- **Command bus**: Symfony Messenger with validation and doctrine_transaction middleware
- **Authentication**: SecurityBundle with bearer token authenticator
- **API versioning**: Header-based via X-API-Version
- **Error handling**: RFC 9457 Problem+JSON responses
- **Logging**: Slim one-line format with trace IDs
- **Testing**: PHPUnit 11 with functional and unit tests

Each task produces self-contained changes that can be committed independently. The plan follows TDD principles with tests written before implementation where applicable.
