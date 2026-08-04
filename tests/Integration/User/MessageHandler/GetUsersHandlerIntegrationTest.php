<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\MessageHandler;

use App\Shared\DTO\PaginatedCollection;
use App\User\Message\GetUsers;
use App\User\MessageHandler\GetUsersHandler;
use App\Tests\Integration\IntegrationTestCase;

class GetUsersHandlerIntegrationTest extends IntegrationTestCase
{
    private GetUsersHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(GetUsersHandler::class);
    }

    public function testGetUsersDefaultPagination(): void
    {
        $result = ($this->handler)(new GetUsers());

        $this->assertInstanceOf(PaginatedCollection::class, $result);
        $this->assertSame(1, $result->page);
        $this->assertSame(20, $result->perPage);
    }

    public function testGetUsersEmptyDatabase(): void
    {
        $result = ($this->handler)(new GetUsers());

        $this->assertCount(0, $result->items);
        $this->assertSame(0, $result->total);
    }

    public function testGetUsersWithMultipleUsers(): void
    {
        $this->createUser(email: 'user1@example.com');
        $this->createUser(email: 'user2@example.com');
        $this->createUser(email: 'user3@example.com');

        $result = ($this->handler)(new GetUsers());

        $this->assertCount(3, $result->items);
        $this->assertSame(3, $result->total);
    }

    public function testGetUsersCustomPage(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createUser(email: "user{$i}@example.com");
        }

        $result = ($this->handler)(new GetUsers(page: 2, perPage: 2));

        $this->assertCount(2, $result->items);
        $this->assertSame(2, $result->page);
        $this->assertSame(5, $result->total);
    }

    public function testGetUsersCustomPerPage(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->createUser(email: "user{$i}@example.com");
        }

        $result = ($this->handler)(new GetUsers(page: 1, perPage: 5));

        $this->assertCount(5, $result->items);
        $this->assertSame(5, $result->perPage);
    }

    public function testGetUsersOrdering(): void
    {
        $userC = $this->createUser(email: 'c@example.com');
        $userA = $this->createUser(email: 'a@example.com');
        $userB = $this->createUser(email: 'b@example.com');

        $result = ($this->handler)(new GetUsers());

        $emails = array_map(fn($u) => $u->getEmail(), $result->items);
        $this->assertSame(['c@example.com', 'a@example.com', 'b@example.com'], $emails);
    }
}
