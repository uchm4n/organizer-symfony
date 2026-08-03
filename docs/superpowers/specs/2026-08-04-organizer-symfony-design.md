# Organizer Symfony — Design Spec

**Date:** 2026-08-04
**Status:** Approved
**Port of:** Laravel Organizer app (`uchm4n/organizer`)

---

## Overview

Build a Symfony 8.1 application that is an exact functional analog of the Laravel Organizer app — a JSON-first personal workspace API supporting notes, todos, spreadsheets, tax declarations, calendar/events, and a document vault. The app is API-only (no frontend), token-authenticated, versioned via headers, and returns RFC 9457 Problem+JSON errors.

---

## Architecture Decisions

| Concern | Laravel | Symfony Decision |
|---------|---------|------------------|
| Database | Eloquent | Doctrine ORM |
| Action pattern | Plain service classes | Symfony Messenger (command bus) |
| Authentication | Sanctum bearer tokens | SecurityBundle + ApiToken entity |
| Rate limiting | `throttle:api` middleware | `symfony/rate-limiter` component |
| Response caching | `spatie/laravel-responsecache` | Custom `CacheResponseSubscriber` |
| Error responses | `Problem::response()` helper | Custom `ProblemRenderer` |
| Trace IDs | `AssignTraceId` middleware | Custom `TraceIdSubscriber` |
| Logging | Monolog + SlimLineFormatter | Monolog + SlimLineFormatter |
| API docs | Scribe | NelmioApiDocBundle |
| DTOs | `spatie/laravel-data` | Symfony Serializer + plain DTOs |
| Validation | FormRequest | Symfony Validator attributes |

---

## Directory Structure

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

## Domain Models (Doctrine Entities)

### User Entity

```php
#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
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
    private ?DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist'])]
    private ?Workspace $workspace = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiToken::class, cascade: ['remove'])]
    private Collection $apiTokens;
}
```

### ApiToken Entity

```php
#[ORM\Entity]
#[ORM\Table(name: 'personal_access_tokens')]
class ApiToken
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiTokens')]
    private User $user;

    #[ORM\Column(unique: true)]
    private string $token;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;
}
```

### Workspace Entity

```php
#[ORM\Entity]
#[ORM\Table(name: 'workspaces')]
class Workspace
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'workspace')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column]
    private string $name;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $settings = null;
}
```

### Item Entity

```php
#[ORM\Entity]
#[ORM\Table(name: 'items')]
#[ORM\HasLifecycleCallbacks]
class Item
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Workspace::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private Workspace $workspace;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'children')]
    private ?Item $parent = null;

    #[ORM\Column(type: 'string', enumType: ItemType::class)]
    private ItemType $type;

    #[ORM\Column]
    private string $title;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $data = null;

    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;
}
```

### Enums

```php
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
}

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

---

## Command Bus Pattern (Symfony Messenger)

Each Laravel Action becomes a **Message + MessageHandler** pair:

| Laravel | Symfony Message | Symfony Handler |
|---------|----------------|-----------------|
| `LoginUserAction` | `LoginUser` | `LoginUserHandler` |
| `CreateItemAction` | `CreateItem` | `CreateItemHandler` |
| `UpdateUserProfileAction` | `UpdateUserProfile` | `UpdateUserProfileHandler` |

### Messenger Configuration

```yaml
# config/packages/messenger.yaml
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

- **command.bus** — write operations (LoginUser, CreateItem, UpdateWorkspace)
- **query.bus** — read operations (GetUser, GetUsers, GetWorkspaceItems)

### Message Classes

Messages are simple DTOs holding operation data:

```php
final readonly class LoginUser
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}

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

### MessageHandler Classes

Handlers contain business logic, receive messages, return domain objects:

```php
#[AsMessageHandler]
final readonly class LoginUserHandler
{
    public function __construct(
        private UserRepository $users,
        private ApiTokenManager $tokenManager,
    ) {}

    public function __invoke(LoginUser $message): ApiToken
    {
        $user = $this->users->findOneByEmail($message->email);

        if (!$user || !$this->tokenManager->checkPassword($user, $message->password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenManager->createToken($user, 'api-token');
    }
}
```

---

## Authentication & Security

### Token Flow

1. Client sends `POST /api/v1/login` with `{ email, password }`
2. Server validates credentials, revokes old tokens, creates new ApiToken
3. Returns `{ access_token, token_type }` (plain-text token shown once)
4. Client sends `Authorization: Bearer <token>` on subsequent requests
5. Authenticator hashes token, looks up in DB, checks expiration
6. Loads User via UserProvider for authenticated requests

### ApiTokenManager

```php
final class ApiTokenManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function createToken(User $user, string $name, int $ttlMinutes = 2880): ApiToken
    {
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
            expiresAt: new DateTimeImmutable("+{$ttlMinutes} minutes"),
        );

        $this->em->persist($token);
        $this->em->flush();

        $token->setPlainTextToken($plainToken);
        return $token;
    }
}
```

### Security Configuration

```yaml
# config/packages/security.yaml
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

### Token Properties

- Global TTL: 2880 minutes (2 days)
- On login: prior tokens revoked before creating new one
- Expired tokens return `401 Problem+JSON` — no auto-refresh
- Token stored as SHA-256 hash (plain-text only returned once on creation)

---

## API Layer

### Versioning (Header-Based)

```php
final class ApiVersionSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED = [1];

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => ['onControllerEvent', 100]];
    }

    public function onControllerEvent(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api/')) return;

        $requested = $request->headers->get('X-API-Version');
        $version = $requested === null
            ? self::SUPPORTED[array_key_last(self::SUPPORTED)]
            : filter_var($requested, FILTER_VALIDATE_INT);

        if ($version === false || !in_array($version, self::SUPPORTED, true)) {
            throw new UnsupportedApiVersionException($requested, self::SUPPORTED);
        }

        $request->attributes->set('api.version', $version);
    }
}
```

### Error Handling (RFC 9457 Problem+JSON)

```php
final class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', -128]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) return;

        $response = match (true) {
            $exception instanceof ValidationException => ProblemRenderer::response(
                422, 'Unprocessable Entity', 'Validation failed.',
                ['errors' => $exception->getErrors()]
            ),
            $exception instanceof AuthenticationException => ProblemRenderer::response(
                401, 'Unauthorized', 'Invalid or expired token.'
            ),
            $exception instanceof AccessDeniedException => ProblemRenderer::response(
                403, 'Forbidden', 'Insufficient permissions.'
            ),
            $exception instanceof NotFoundHttpException => ProblemRenderer::response(
                404, 'Not Found', 'Resource not found.'
            ),
            $exception instanceof RateLimitException => ProblemRenderer::response(
                429, 'Too Many Requests', 'Rate limit exceeded.',
                ['retry_after' => $exception->getRetryAfter()]
            ),
            $exception instanceof HttpException => ProblemRenderer::response(
                $exception->getStatusCode(),
                ProblemRenderer::titleForStatus($exception->getStatusCode()),
                $exception->getMessage()
            ),
            default => ProblemRenderer::response(
                500, 'Internal Server Error',
                in_array($request->server->get('APP_ENV'), ['dev', 'test'])
                    ? $exception->getMessage()
                    : 'An unexpected error occurred. Please try again later.'
            ),
        };

        $event->setResponse($response);
    }
}
```

### ProblemResponse Format

No `type` field (intentional project convention):

```json
{
    "title": "Not Found",
    "status": 404,
    "detail": "Resource not found."
}
```

### Route Definitions

```php
// Auth (public)
#[Route('/api/v1/login', name: 'api.v1.auth.login', methods: ['POST'])]
#[Throttle(limit: 5, interval: 60)]

// User (authenticated)
#[Route('/api/v1/user', name: 'api.v1.user.show', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]

// Users (admin only)
#[Route('/api/v1/users', name: 'api.v1.user.index', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]

// Items
#[Route('/api/v1/items', name: 'api.v1.item.index', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
```

---

## Cross-Cutting Concerns

### Trace ID

- Generates 8 hex chars per request (or accepts client `X-Trace-Id` header)
- Stored in request attributes, exposed via `X-Trace-Id` response header
- Included in every log line's context
- Included in Problem+JSON error body

### Slim One-Line Log Format

```
[2026-08-04 12:34:56] api.ERROR: 500 InternalServerException "boom" at src/X.php:42 {"status":500,"route":"api.v1.item.store","method":"GET","url":"/api/items","ip":"127.0.0.1","trace_id":"4f3a2b1c","user":42}
```

Stack traces are NEVER written to disk. Use Symfony Profiler in dev for trace inspection.

### Tiered Exception Logging

| Tier | Status codes | Level |
|------|-------------|-------|
| Server errors | 5xx + non-HTTP Throwables | `error`, always |
| Client abuse signals | 403, 422, 429 | `warning`, always |
| Client noise | 401, 404, NotFound | silent (not logged) |
| Other 4xx | 400, 405, 410, 415, ... | `info` |

### Response Cache

- 15-minute freshness window (`max-age=900`)
- 5 minutes of stale-while-revalidate (`stale-while-revalidate=300`)
- `X-Cache-Status: HIT/MISS` header for debugging
- Cache tags: `users`, `workspaces`, `items`
- Workspace mutations also invalidate items (cascade)
- User role changes invalidate all three tags

### Rate Limiting

- **Authenticated users:** 1000 requests/minute, keyed by token
- **Guests:** 60 requests/minute, keyed by IP
- **Login:** 5 requests/minute per email

Headers on both success and 429 responses:
- `Retry-After`
- `X-RateLimit-Limit`
- `X-RateLimit-Remaining`
- `X-RateLimit-Reset`

### Middleware Stack Order

1. `ApiVersionSubscriber` (priority 100) — resolve version
2. `RateLimiterSubscriber` (priority 80) — check rate limits
3. `TraceIdSubscriber` (priority 70) — generate trace ID
4. `CacheResponseSubscriber` (priority -50) — set cache headers
5. `ExceptionListener` (priority -128) — catch all exceptions

---

## Testing

- **PHPUnit 11** with Doctrine TestBundle
- **SQLite in-memory** for test database
- **MessageHandler tests** — unit tests with mocked dependencies
- **Controller tests** — functional tests with `WebTestCase`
- **Feature tests** — full HTTP-level tests for auth flows, rate limiting, etc.

### Test Structure

```
tests/
├── Auth/
│   ├── MessageHandler/
│   │   └── LoginUserHandlerTest.php
│   └── Controller/
│       └── LoginControllerTest.php
├── User/
│   ├── MessageHandler/
│   └── Controller/
├── Item/
│   ├── MessageHandler/
│   └── Controller/
├── Shared/
│   ├── HttpKernel/
│   │   └── ExceptionListenerTest.php
│   └── EventSubscriber/
│       ├── TraceIdSubscriberTest.php
│       └── ApiVersionSubscriberTest.php
└── Functional/
    ├── AuthFlowTest.php
    └── RateLimitTest.php
```

---

## API Documentation

- **NelmioApiDocBundle** for OpenAPI 3.0 spec generation
- Swagger UI at `/api/doc`
- Auto-generated from PHP attributes on controllers
- Supports `application/problem+json` response documentation

---

## Packages to Install

| Package | Purpose |
|---------|---------|
| `symfony/security-bundle` | Authentication |
| `symfony/validator` | Request validation |
| `symfony/rate-limiter` | Rate limiting |
| `symfony/http-cache` | Response caching |
| `doctrine/doctrine-bundle` | Doctrine ORM integration |
| `doctrine/orm` | Doctrine ORM |
| `doctrine/doctrine-fixtures-bundle` | Test data fixtures |
| `symfony/serializer` | Response serialization |
| `symfony/property-access` | Property access for serializer |
| `symfony/property-info` | Property info for serializer |
| `nelmio/api-doc-bundle` | API documentation |
| `phpunit/phpunit` | Testing |
| `doctrine/doctrine-test-bundle` | Test database transactions |
| `symfony/browser-kit` | Functional HTTP testing |
| `symfony/css-selector` | DOM testing |
| `symfony/dom-crawler` | DOM testing |

---

## Implementation Phases

1. **Database & Entities** — SQLite schema, Doctrine migrations, entity classes
2. **Auth Domain** — LoginUser command, ApiToken authenticator, UserProvider
3. **User Domain** — GetUser/GetUsers commands, controllers
4. **Workspace Domain** — CreateWorkspace/GetWorkspace/UpdateWorkspace commands, controllers
5. **Item Domain** — CRUD commands, controllers
6. **Error Handling** — ProblemRenderer, ExceptionListener, TraceIdSubscriber
7. **Rate Limiting** — Symfony rate-limiter config, ThrottleSubscriber
8. **Response Caching** — CacheResponseSubscriber, cache headers, invalidation
9. **API Docs** — NelmioApiDocBundle setup, OpenAPI spec generation
10. **Tests** — MessageHandler tests, Controller tests, Feature tests
