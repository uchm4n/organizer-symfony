<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\User\Entity\User;
use App\User\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class FunctionalApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    protected SchemaTool $schemaTool;

    /** @var array<int, ClassMetadata<object>> */
    private array $metadata = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = $this->client->getContainer();

        $this->em = $container->get(EntityManagerInterface::class);
        $this->schemaTool = new SchemaTool($this->em);
        $this->metadata = $this->em->getMetadataFactory()->getAllMetadata();

        $this->schemaTool->dropSchema($this->metadata);
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function createUser(
        string $email,
        string $password,
        Role $role = Role::User,
        string $name = 'Test User',
    ): User {
        $hasher = $this->client->getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRole($role);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

}
