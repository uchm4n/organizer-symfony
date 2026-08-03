<?php

declare(strict_types=1);

namespace App\Tests\Auth\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    public function testLoginEndpointReturnsJson(): void
    {
        $client = static::createClient();

        $container = $client->getContainer();
        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $metadataFactory = $em->getMetadataFactory();
        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($metadataFactory->getAllMetadata());

        $client->request('POST', '/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertResponseStatusCodeSame(401);
    }
}
