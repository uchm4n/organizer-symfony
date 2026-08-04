# Organizer

A JSON-first personal workspace API. Supports notes, todos, spreadsheets, tax declarations, calendar/events, and a document vault.

## Architecture

The application is organized into domain modules (`Auth`, `User`, `Workspace`, `Item`, plus `Shared` for cross-cutting concerns), each following the same flow:

```
HTTP request → Controller → Message (command.bus / query.bus) → MessageHandler → Entity (Doctrine) → DTO → JSON response
```

### Command bus (CQS — not full CQRS)

All operations go through Symfony Messenger as **Message + MessageHandler** pairs, split into two buses:

- **`command.bus`** — write operations (`LoginUser`, `CreateItem`, `UpdateWorkspace`, ...). Middleware: `validation`, `doctrine_transaction`.
- **`query.bus`** — read operations (`GetUser`, `GetItems`, `GetWorkspaceItems`, ...). Middleware: `validation`.

This is Command-Query Separation over a command bus: reads and writes are explicitly separated at the messaging layer, but there are **no dedicated read models** — query handlers return the same Doctrine entities, and the controllers convert them to response DTOs. Single storage, no projections, no eventual consistency. Full CQRS would add complexity without benefit for a single-user JSON API.

### Key decisions

- **Authentication:** SecurityBundle with a custom bearer-token authenticator backed by the `ApiToken` entity (SHA-256 hashed, 2-day TTL, revoked on re-login)
- **Error handling:** RFC 9457 Problem+JSON (`application/problem+json`) via `ProblemRenderer` + a global `ExceptionListener`
- **API versioning:** Header-based (`X-API-Version`, currently `1`); trace IDs via `X-Trace-Id`
- **Persistence:** Doctrine ORM directly in handlers (no repository layer)
- **API docs:** NelmioApiDocBundle — OpenAPI 3.0 spec auto-generated from `OpenApi\Attributes` on controllers and DTOs (see `/api/doc`)

## Tech Stack

- PHP 8.5
- Symfony 8.1
- Doctrine ORM (SQLite)
- Symfony Messenger (command bus)
- SecurityBundle (bearer token auth)
- PHPUnit 11
- Monolog (SlimLineFormatter)

## Quick Start

```bash
# Install dependencies
composer install

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction

# Run tests
php bin/phpunit

# Start dev server
php bin/console server:run
```

## API Overview

All endpoints are under `/api/v1/`. Authentication uses `Authorization: Bearer {token}`.

### Authentication

```bash
# Login
curl -X POST http://localhost:8000/api/v1/login \
  -d '{"email":"user@example.com","password":"secret"}'

# Use token
curl -H "Authorization: Bearer {token}" \
  http://localhost:8000/api/v1/user
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/login` | Login, get bearer token |
| GET | `/api/v1/user` | Get authenticated user |
| GET | `/api/v1/users` | List all users |
| GET | `/api/v1/workspace` | Get current user's workspace |
| POST | `/api/v1/workspace` | Create workspace |
| PATCH | `/api/v1/workspace` | Update workspace |
| GET | `/api/v1/workspaces/{id}` | Get workspace by ID |
| GET | `/api/v1/workspaces/{id}/items` | List workspace items |
| GET | `/api/v1/items` | List current user's items |
| GET | `/api/v1/items/{id}` | Get item by ID |
| POST | `/api/v1/items` | Create item |
| PATCH | `/api/v1/items/{id}` | Update item |
| DELETE | `/api/v1/items/{id}` | Delete item |

## Project Structure

```
src/
├── Auth/              # Authentication domain
│   ├── Controller/    # LoginController
│   ├── Message/       # LoginUser
│   ├── MessageHandler/# LoginUserHandler
│   └── Security/      # ApiTokenManager, ApiTokenAuthenticator, UserProvider
├── User/              # User domain
│   ├── Controller/    # UserController, UsersController
│   ├── DTO/           # UserData, UserCollectionData
│   ├── Entity/        # User, ApiToken
│   ├── Enum/          # Role
│   ├── Message/       # GetUser, GetUsers, UpdateUserRole
│   └── MessageHandler/# GetUserHandler, GetUsersHandler, UpdateUserRoleHandler
├── Workspace/         # Workspace domain
│   ├── Controller/    # WorkspaceController, WorkspaceShowController, WorkspaceIndexController
│   ├── DTO/           # WorkspaceData
│   ├── Entity/        # Workspace
│   ├── Message/       # CreateWorkspace, GetWorkspace, GetWorkspaceItems, UpdateWorkspace
│   └── MessageHandler/# CreateWorkspaceHandler, GetWorkspaceHandler, GetWorkspaceItemsHandler, UpdateWorkspaceHandler
├── Item/              # Item domain
│   ├── Controller/    # ItemIndexController, ItemShowController, ItemStoreController, ItemUpdateController, ItemDestroyController
│   ├── DTO/           # ItemData
│   ├── Entity/        # Item
│   ├── Enum/          # ItemType
│   ├── Message/       # CreateItem, GetItem, GetItems, UpdateItem, DeleteItem
│   └── MessageHandler/# CreateItemHandler, GetItemHandler, GetItemsHandler, UpdateItemHandler, DeleteItemHandler
└── Shared/            # Cross-cutting concerns
    ├── DTO/           # ProblemResponse, PaginatedCollection
    ├── EventSubscriber/# TraceIdSubscriber, ApiVersionSubscriber
    ├── Exception/     # UnsupportedApiVersionException
    ├── HttpKernel/    # ProblemRenderer, ExceptionListener
    └── Logging/       # SlimLineFormatter, ExceptionLogger
```

## For AI Agents

If you're an AI agent picking up this project:

1. **Read `AGENTS.md`** for architecture decisions and conventions
2. **Read `docs/superpowers/specs/2026-08-04-organizer-symfony-design.md`** for the full design spec
3. **Read `docs/superpowers/plans/2026-08-04-organizer-symfony-implementation.md`** for the implementation plan with complete code
4. **Run `php bin/phpunit`** to verify current state
5. **Check `config/packages/messenger.yaml`** for command bus setup
6. **Check `config/packages/security.yaml`** for auth configuration

### Key Patterns

- **Commands:** `readonly` classes with constructor promotion, dispatched via `$commandBus->dispatch()`
- **Handlers:** `#[AsMessageHandler]` attribute, `__invoke()` method, EntityManager for persistence
- **Query Handlers:** Use `bus: 'query.bus'` parameter
- **Controllers:** Extend `AbstractController`, use `#[Route]` attributes
- **DTOs:** `readonly` classes with `fromEntity()` and `toArray()` methods
- **Errors:** `ProblemRenderer::response()` for RFC 9457 responses

### Adding New Features

1. Create `Message` class in domain's `Message/` directory
2. Create `MessageHandler` in domain's `MessageHandler/` directory
3. Create `DTO` in domain's `DTO/` directory
4. Create `Controller` in domain's `Controller/` directory
5. Add routes via `#[Route]` attributes
6. Add tests in `tests/` directory

## Environment

```env
APP_ENV=dev
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

```env
# .env.test
APP_ENV=test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.db"
```

## License

MIT
