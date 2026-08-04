<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: symfony/ai-mate.
<!-- END AI_MATE_INSTRUCTIONS -->

# AI Agent Instructions

## Project Overview

Symfony 8.1 port of the Laravel Organizer app — a JSON-first personal workspace API supporting notes, todos, spreadsheets, tax declarations, calendar/events, and a document vault.

## Architecture

- **Command Bus:** Symfony Messenger (not plain service classes)
- **Authentication:** SecurityBundle with manual token table (`ApiToken` entity)
- **Database:** Doctrine ORM with SQLite (dev/test)
- **Error Format:** RFC 9457 Problem+JSON via `ProblemRenderer`
- **API Versioning:** Header-based via `X-API-Version` (currently `v1`)
- **Logging:** Monolog with `SlimLineFormatter`

## Domain Structure

```
src/
├── Auth/          # Login, token management, authenticator
├── User/          # User entity, roles, CRUD
├── Workspace/     # User workspaces, settings
├── Item/          # Items within workspaces (notes, todos, etc.)
└── Shared/        # DTOs, event subscribers, exceptions, logging
```

## Key Files

| File | Purpose |
|------|---------|
| `src/Auth/Security/ApiTokenManager.php` | Token creation, validation, password hashing |
| `src/Auth/Security/ApiTokenAuthenticator.php` | Bearer token authentication |
| `src/Auth/MessageHandler/LoginUserHandler.php` | Login message handler |
| `src/Shared/HttpKernel/ProblemRenderer.php` | RFC 9457 error responses |
| `src/Shared/HttpKernel/ExceptionListener.php` | Global exception handling |
| `src/Shared/EventSubscriber/TraceIdSubscriber.php` | Request trace IDs |
| `config/packages/messenger.yaml` | Command bus configuration |
| `config/packages/security.yaml` | Authentication configuration |

## Running Tests

```bash
php bin/phpunit
```

## Running the Dev Server

```bash
php bin/console server:run
```

## Database Commands

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console doctrine:database:create --env=test
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Front page (README-style) |
| GET | `/api/doc` | Swagger UI (OpenAPI docs) |
| GET | `/api/doc.json` | Raw OpenAPI 3.0 spec |
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

## Conventions

- All API responses are JSON
- Authentication via `Authorization: Bearer {token}` header
- API version via `X-API-Version` header (default: latest)
- Trace IDs via `X-Trace-Id` header (auto-generated if not provided)
- Errors return `application/problem+json` content type
- No Doctrine repositories — use EntityManager directly in handlers
- Messages use `readonly` classes with constructor promotion
- Handlers use `#[AsMessageHandler]` attribute
- Query handlers use `bus: 'query.bus'` parameter
