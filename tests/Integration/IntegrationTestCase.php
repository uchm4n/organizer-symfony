<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\User\Entity\User;
use App\User\Enum\Role;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class IntegrationTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;
    protected SchemaTool $schemaTool;

    /** @var array<int, ClassMetadata<object>> */
    private array $metadata = [];

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->schemaTool = new SchemaTool($this->em);
        $this->metadata = $this->em->getMetadataFactory()->getAllMetadata();

        $this->schemaTool->dropSchema($this->metadata);
        $this->schemaTool->createSchema($this->metadata);
    }

    protected function createUser(
        string $email = 'test@example.com',
        string $password = 'password',
        Role $role = Role::User,
    ): User {
        /** @var UserPasswordHasherInterface $hasher */
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail($email);
        $user->setName('Test User');
        $user->setRole($role);
        $user->setPassword($hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        if (isset($this->em)) {
            $this->em->clear();
            $this->em->getConnection()->close();
        }

        parent::tearDown();
    }
}
