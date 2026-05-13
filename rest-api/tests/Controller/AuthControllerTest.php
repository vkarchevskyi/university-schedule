<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    use JsonTestAssertions;

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testAdminCanLoginWithValidCredentials(): void
    {
        $this->createAdmin('admin@example.com', 'correct-password');

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        self::assertResponseIsSuccessful();

        $payload = $this->responseJson($this->client);

        self::assertArrayHasKey('token', $payload);
        $admin = $this->objectValue($payload, 'admin');

        self::assertSame('admin@example.com', $this->stringValue($admin, 'email'));
        self::assertSame('Ada', $this->stringValue($admin, 'firstName'));
    }

    public function testAdminCannotLoginWithInvalidCredentials(): void
    {
        $this->createAdmin('admin@example.com', 'correct-password');

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testProtectedRouteRejectsAnonymousRequests(): void
    {
        $this->client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testCurrentAdminEndpointReturnsAuthenticatedAdmin(): void
    {
        $this->createAdmin('admin@example.com', 'correct-password');

        $this->client->jsonRequest('POST', '/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'correct-password',
        ]);

        $payload = $this->responseJson($this->client);

        $this->client->request('GET', '/api/auth/me', server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $this->stringValue($payload, 'token')),
        ]);

        self::assertResponseIsSuccessful();

        $mePayload = $this->responseJson($this->client);
        $admin = $this->objectValue($mePayload, 'admin');

        self::assertSame('admin@example.com', $this->stringValue($admin, 'email'));
    }

    private function createAdmin(string $email, string $plainPassword): Admin
    {
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

        $admin = new Admin('Ada', 'Lovelace', $email, $passwordHash, new \DateTimeImmutable());

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }
}
