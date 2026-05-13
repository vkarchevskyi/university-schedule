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

        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('token', $payload);
        self::assertSame('admin@example.com', $payload['admin']['email']);
        self::assertSame('Ada', $payload['admin']['firstName']);
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

        $payload = json_decode($this->client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);

        $this->client->request('GET', '/api/auth/me', server: [
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $payload['token']),
        ]);

        self::assertResponseIsSuccessful();

        $mePayload = json_decode($this->client->getResponse()->getContent() ?: '', true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('admin@example.com', $mePayload['admin']['email']);
    }

    private function createAdmin(string $email, string $plainPassword): Admin
    {
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

        self::assertIsString($passwordHash);

        $admin = new Admin('Ada', 'Lovelace', $email, $passwordHash, new \DateTimeImmutable());

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }
}
