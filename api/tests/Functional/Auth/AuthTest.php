<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\User\Domain\Entity\User u WHERE u.email LIKE :pattern')
            ->setParameter('pattern', '%@test.helpdesk%')
            ->execute();
        parent::tearDown();
    }

    public function testRegisterCreatesUserAndReturnsToken(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'alice@test.helpdesk',
                'password' => 'Password123!',
                'full_name' => 'Alice Smith',
            ]),
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testRegisterFailsWithDuplicateEmail(): void
    {
        $payload = json_encode([
            'email' => 'duplicate@test.helpdesk',
            'password' => 'Password123!',
            'full_name' => 'Dup User',
        ]);

        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterFailsWithMissingFields(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'bad@test.helpdesk']),
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testLoginReturnsToken(): void
    {
        $this->registerUser('login@test.helpdesk', 'Password123!', 'Login User');

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'login@test.helpdesk', 'password' => 'Password123!']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->registerUser('wrongpass@test.helpdesk', 'CorrectPass1!', 'User');

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'wrongpass@test.helpdesk', 'password' => 'WrongPass!']),
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointRequiresJwt(): void
    {
        $this->client->request('GET', '/api');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testProtectedEndpointAccessibleWithValidJwt(): void
    {
        $token = $this->registerUserAndGetToken('protected@test.helpdesk');

        $this->client->request('GET', '/api', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '.$token]);

        $this->assertResponseIsSuccessful();
    }

    public function testRefreshReturnsNewToken(): void
    {
        $token = $this->registerUserAndGetToken('refresh@test.helpdesk');

        $this->client->request(
            'POST',
            '/api/auth/refresh',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    private function registerUser(string $email, string $password, string $fullName): void
    {
        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password, 'full_name' => $fullName]),
        );
    }

    private function registerUserAndGetToken(string $email): string
    {
        $this->registerUser($email, 'Password123!', 'Test User');

        $data = json_decode((string) $this->client->getResponse()->getContent(), true);

        return $data['token'];
    }
}
