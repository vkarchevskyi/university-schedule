<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Enum\UserRole;
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
        $user = $this->objectValue($payload, 'user');

        self::assertSame('admin@example.com', $this->stringValue($user, 'email'));
        self::assertSame('Ada', $this->stringValue($user, 'firstName'));
        self::assertSame('admin', $this->stringValue($user, 'role'));
    }

    public function testApiCorsPreflightAllowsFrontendOrigin(): void
    {
        $this->client->request('OPTIONS', '/api/auth/login', server: [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', 'http://localhost:5173');
        self::assertResponseHeaderSame('Access-Control-Allow-Methods', 'GET, OPTIONS, POST, PATCH, DELETE');
        self::assertResponseHeaderSame('Access-Control-Allow-Headers', 'content-type, authorization');
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

    public function testCurrentUserEndpointReturnsAuthenticatedAdminUser(): void
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
        $user = $this->objectValue($mePayload, 'user');

        self::assertSame('admin@example.com', $this->stringValue($user, 'email'));
        self::assertSame('admin', $this->stringValue($user, 'role'));
    }

    private function createAdmin(string $email, string $plainPassword): User
    {
        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);

        $admin = new User('Ada', 'Lovelace', $email, $passwordHash, new \DateTimeImmutable(), UserRole::Admin);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        return $admin;
    }
}
