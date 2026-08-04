<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Item\Entity\Item;
use App\Item\Enum\ItemType;
use App\User\Entity\User;
use App\User\Enum\Role;
use App\Workspace\Entity\Workspace;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'admin@example.com';
    public const ADMIN_PASSWORD = 'admin123';
    public const USER_EMAIL = 'user@example.com';
    public const USER_PASSWORD = 'user123';
    public const FRESH_EMAIL = 'fresh@example.com';
    public const FRESH_PASSWORD = 'fresh123';

    private const SEEDED_EMAILS = [
        self::ADMIN_EMAIL,
        self::USER_EMAIL,
        self::FRESH_EMAIL,
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->removeExistingSeedData($manager);

        $verifiedAt = new \DateTimeImmutable('2026-08-04T00:00:00+00:00');

        $admin = $this->createUser(
            manager: $manager,
            email: self::ADMIN_EMAIL,
            password: self::ADMIN_PASSWORD,
            name: 'Admin User',
            role: Role::Admin,
            emailVerifiedAt: $verifiedAt,
        );
        $adminWorkspace = $this->createWorkspace(
            manager: $manager,
            user: $admin,
            name: 'Operations Hub',
            settings: [
                'theme' => 'dark',
                'language' => 'en',
                'timezone' => 'UTC',
                'landing_tab' => 'dashboard',
            ],
        );
        $adminTodo = $this->createItem(
            manager: $manager,
            workspace: $adminWorkspace,
            type: ItemType::Todo,
            title: 'Ship Organizer API',
            data: [
                'completed' => false,
                'priority' => 'high',
                'labels' => ['release', 'api'],
            ],
            sortOrder: 1,
        );
        $this->createItem(
            manager: $manager,
            workspace: $adminWorkspace,
            type: ItemType::Note,
            title: 'Release checklist',
            data: [
                'content' => 'Verify login, workspace, and items endpoints before deployment.',
            ],
            sortOrder: 2,
        );
        $this->createItem(
            manager: $manager,
            workspace: $adminWorkspace,
            type: ItemType::Spreadsheet,
            title: 'Infrastructure budget',
            data: [
                'columns' => ['Category', 'Amount'],
                'rows' => [
                    ['Hosting', 1200],
                    ['Monitoring', 300],
                ],
            ],
            sortOrder: 3,
        );
        $this->createItem(
            manager: $manager,
            workspace: $adminWorkspace,
            type: ItemType::Custom,
            title: 'Service bookmark',
            data: [
                'schema' => 'bookmark',
                'url' => 'https://github.com/uchm4n/organizer-symfony',
            ],
            sortOrder: 4,
        );
        $this->createItem(
            manager: $manager,
            workspace: $adminWorkspace,
            type: ItemType::Note,
            title: 'Smoke test login flow',
            data: [
                'content' => 'Use the seeded admin account and verify bearer token issuance.',
            ],
            sortOrder: 1,
            parent: $adminTodo,
        );

        $user = $this->createUser(
            manager: $manager,
            email: self::USER_EMAIL,
            password: self::USER_PASSWORD,
            name: 'Demo User',
            role: Role::User,
            emailVerifiedAt: $verifiedAt,
        );
        $userWorkspace = $this->createWorkspace(
            manager: $manager,
            user: $user,
            name: 'Personal Workspace',
            settings: [
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'Europe/Tbilisi',
                'landing_tab' => 'notes',
            ],
        );
        $userTodo = $this->createItem(
            manager: $manager,
            workspace: $userWorkspace,
            type: ItemType::Todo,
            title: 'Plan vacation',
            data: [
                'completed' => false,
                'priority' => 'medium',
                'due_date' => '2026-09-01',
            ],
            sortOrder: 1,
        );
        $this->createItem(
            manager: $manager,
            workspace: $userWorkspace,
            type: ItemType::Note,
            title: 'Welcome note',
            data: [
                'content' => 'This workspace is seeded for manual API verification.',
            ],
            sortOrder: 2,
        );
        $this->createItem(
            manager: $manager,
            workspace: $userWorkspace,
            type: ItemType::Document,
            title: 'Passport scan',
            data: [
                'filename' => 'passport.pdf',
                'mime_type' => 'application/pdf',
            ],
            sortOrder: 3,
        );
        $this->createItem(
            manager: $manager,
            workspace: $userWorkspace,
            type: ItemType::Event,
            title: 'Quarterly planning meeting',
            data: [
                'starts_at' => '2026-08-12T09:00:00+00:00',
                'location' => 'Conference Room A',
            ],
            sortOrder: 4,
        );
        $this->createItem(
            manager: $manager,
            workspace: $userWorkspace,
            type: ItemType::TaxFiling,
            title: '2025 tax declaration',
            data: [
                'year' => 2025,
                'status' => 'draft',
            ],
            sortOrder: 5,
        );
        $this->createItem(
            manager: $manager,
            workspace: $userWorkspace,
            type: ItemType::Note,
            title: 'Book flights',
            data: [
                'content' => 'Compare morning flights before Friday.',
            ],
            sortOrder: 1,
            parent: $userTodo,
        );

        $this->createUser(
            manager: $manager,
            email: self::FRESH_EMAIL,
            password: self::FRESH_PASSWORD,
            name: 'Fresh User',
            role: Role::User,
            emailVerifiedAt: $verifiedAt,
        );

        $manager->flush();
    }

    private function removeExistingSeedData(ObjectManager $manager): void
    {
        // Keep fixture loading safe with --append by rebuilding only our known seed records.
        foreach (self::SEEDED_EMAILS as $email) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user instanceof User) {
                continue;
            }

            foreach ($user->getApiTokens() as $token) {
                $manager->remove($token);
            }

            if ($user->getWorkspace() !== null) {
                $manager->remove($user->getWorkspace());
            }

            $manager->remove($user);
        }

        $manager->flush();
    }

    private function createUser(
        ObjectManager $manager,
        string $email,
        string $password,
        string $name,
        Role $role,
        ?\DateTimeImmutable $emailVerifiedAt = null,
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setRole($role);
        $user->setEmailVerifiedAt($emailVerifiedAt);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $manager->persist($user);

        return $user;
    }

    private function createWorkspace(
        ObjectManager $manager,
        User $user,
        string $name,
        ?array $settings = null,
    ): Workspace {
        $workspace = new Workspace();
        $workspace->setUser($user);
        $workspace->setName($name);
        $workspace->setSettings($settings);

        $user->setWorkspace($workspace);

        $manager->persist($workspace);

        return $workspace;
    }

    private function createItem(
        ObjectManager $manager,
        Workspace $workspace,
        ItemType $type,
        string $title,
        ?array $data = null,
        int $sortOrder = 0,
        ?Item $parent = null,
    ): Item {
        $item = new Item();
        $item->setWorkspace($workspace);
        $item->setType($type);
        $item->setTitle($title);
        $item->setData($data);
        $item->setSortOrder($sortOrder);
        $item->setParent($parent);

        $manager->persist($item);

        return $item;
    }
}
