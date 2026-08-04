<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app.home', methods: ['GET'])]
final class HomeController extends AbstractController
{
    public function __invoke(): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Organizer API</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 0 auto; padding: 2rem 1rem; color: #1a202c; line-height: 1.6; }
        h1 { border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; }
        code { background: #f7fafc; padding: 0.15rem 0.35rem; border-radius: 4px; font-size: 0.9em; }
        table { border-collapse: collapse; width: 100%; margin: 1rem 0; }
        th, td { text-align: left; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e2e8f0; }
        .doc-link { display: inline-block; margin: 1rem 0; padding: 0.6rem 1.2rem; background: #2b6cb0; color: #fff; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>Organizer API</h1>
    <p>JSON-first personal workspace API supporting notes, todos, spreadsheets, tax declarations, calendar/events, and a document vault.</p>

    <p><a class="doc-link" href="/api/doc">Interactive API documentation (Swagger UI)</a></p>
    <p>Raw OpenAPI spec: <code><a href="/api/doc.json">/api/doc.json</a></code></p>

    <h2>Getting started</h2>
    <ol>
        <li>Authenticate: <code>POST /api/v1/login</code> with <code>{ "email": "...", "password": "..." }</code></li>
        <li>Use the returned <code>access_token</code> as <code>Authorization: Bearer {token}</code> on all subsequent requests</li>
    </ol>

    <h2>Endpoints</h2>
    <table>
        <thead>
            <tr><th>Method</th><th>Path</th><th>Description</th></tr>
        </thead>
        <tbody>
            <tr><td>POST</td><td><code>/api/v1/login</code></td><td>Login, get bearer token</td></tr>
            <tr><td>GET</td><td><code>/api/v1/user</code></td><td>Get authenticated user</td></tr>
            <tr><td>GET</td><td><code>/api/v1/users</code></td><td>List all users</td></tr>
            <tr><td>GET</td><td><code>/api/v1/workspace</code></td><td>Get current user's workspace</td></tr>
            <tr><td>POST</td><td><code>/api/v1/workspace</code></td><td>Create workspace</td></tr>
            <tr><td>PATCH</td><td><code>/api/v1/workspace</code></td><td>Update workspace</td></tr>
            <tr><td>GET</td><td><code>/api/v1/workspaces/{id}</code></td><td>Get workspace by ID</td></tr>
            <tr><td>GET</td><td><code>/api/v1/workspaces/{id}/items</code></td><td>List workspace items</td></tr>
            <tr><td>GET</td><td><code>/api/v1/items</code></td><td>List current user's items</td></tr>
            <tr><td>POST</td><td><code>/api/v1/items</code></td><td>Create item</td></tr>
            <tr><td>GET</td><td><code>/api/v1/items/{id}</code></td><td>Get item by ID</td></tr>
            <tr><td>PATCH</td><td><code>/api/v1/items/{id}</code></td><td>Update item</td></tr>
            <tr><td>DELETE</td><td><code>/api/v1/items/{id}</code></td><td>Delete item</td></tr>
        </tbody>
    </table>

    <h2>Headers</h2>
    <ul>
        <li><code>X-API-Version</code> — API version (currently <code>1</code>, default: latest)</li>
        <li><code>X-Trace-Id</code> — request trace ID (auto-generated if not provided)</li>
    </ul>

    <h2>Errors</h2>
    <p>Errors follow RFC 9457 Problem+JSON (<code>application/problem+json</code>) with <code>title</code>, <code>status</code> and <code>detail</code> fields.</p>
</body>
</html>
HTML;

        return new Response($html);
    }
}
