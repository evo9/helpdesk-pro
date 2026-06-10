<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UsersApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private User $reporter;
    private User $agent;
    private User $manager;
    private string $reporterToken;
    private string $agentToken;
    private string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class);

        $this->reporter = new User('reporter@users.helpdesk', '', 'Reporter', UserRole::REPORTER);
        $this->reporter->updatePassword($hasher->hashPassword($this->reporter, 'Password1!'));
        $em->persist($this->reporter);

        $this->agent = new User('agent@users.helpdesk', '', 'Agent', UserRole::AGENT);
        $this->agent->updatePassword($hasher->hashPassword($this->agent, 'Password1!'));
        $em->persist($this->agent);

        $this->manager = new User('manager@users.helpdesk', '', 'Manager', UserRole::MANAGER);
        $this->manager->updatePassword($hasher->hashPassword($this->manager, 'Password1!'));
        $em->persist($this->manager);

        $em->flush();

        $this->reporterToken = $jwt->create($this->reporter);
        $this->agentToken = $jwt->create($this->agent);
        $this->managerToken = $jwt->create($this->manager);
    }

    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery("DELETE FROM App\User\Domain\Entity\User u WHERE u.email LIKE :p")
            ->setParameter('p', '%@users.helpdesk')
            ->execute();
        parent::tearDown();
    }

    // ======= LIST =======

    public function testReporterCannotListUsers(): void
    {
        $this->request('GET', '/api/users', $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotListUsers(): void
    {
        $this->request('GET', '/api/users', $this->agentToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedCannotListUsers(): void
    {
        $this->request('GET', '/api/users', null);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testManagerCanListUsers(): void
    {
        $this->request('GET', '/api/users', $this->managerToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $ids = array_column($members, 'id');
        $this->assertContains((string) $this->manager->getId(), $ids);
    }

    // ======= GET ITEM =======

    public function testManagerCanGetUser(): void
    {
        $this->request('GET', '/api/users/'.$this->reporter->getId(), $this->managerToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertEquals((string) $this->reporter->getId(), $data['id']);
        $this->assertEquals('reporter@users.helpdesk', $data['email']);
        $this->assertEquals('reporter', $data['role']);
        $this->assertTrue($data['isActive']);
    }

    public function testReporterCannotGetUser(): void
    {
        $this->request('GET', '/api/users/'.$this->agent->getId(), $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotGetUser(): void
    {
        $this->request('GET', '/api/users/'.$this->reporter->getId(), $this->agentToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testNonExistentUserReturns404(): void
    {
        $this->request('GET', '/api/users/00000000-0000-7000-8000-000000000000', $this->managerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    // ======= CREATE =======

    public function testManagerCanCreateAgent(): void
    {
        $this->request('POST', '/api/users', $this->managerToken, [
            'email' => 'newagent@users.helpdesk',
            'password' => 'SecurePass1!',
            'fullName' => 'New Agent',
            'role' => 'agent',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertEquals('newagent@users.helpdesk', $data['email']);
        $this->assertEquals('agent', $data['role']);
        $this->assertTrue($data['isActive']);
        $this->assertNotNull($data['id']);
    }

    public function testCreateUserWithDuplicateEmailReturns422(): void
    {
        $this->request('POST', '/api/users', $this->managerToken, [
            'email' => 'reporter@users.helpdesk',
            'password' => 'SecurePass1!',
            'fullName' => 'Duplicate',
            'role' => 'reporter',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testReporterCannotCreateUser(): void
    {
        $this->request('POST', '/api/users', $this->reporterToken, [
            'email' => 'new@users.helpdesk',
            'password' => 'SecurePass1!',
            'fullName' => 'New',
            'role' => 'reporter',
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotCreateUser(): void
    {
        $this->request('POST', '/api/users', $this->agentToken, [
            'email' => 'new2@users.helpdesk',
            'password' => 'SecurePass1!',
            'fullName' => 'New2',
            'role' => 'reporter',
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= UPDATE =======

    public function testManagerCanChangeUserRole(): void
    {
        $this->patch('/api/users/'.$this->reporter->getId(), $this->managerToken, ['role' => 'agent']);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('agent', $this->json()['role']);
    }

    public function testManagerCanDeactivateUser(): void
    {
        $this->patch('/api/users/'.$this->reporter->getId(), $this->managerToken, ['isActive' => false]);
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->json()['isActive']);
    }

    public function testManagerCanReactivateUser(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->reporter->deactivate();
        $em->flush();

        $this->patch('/api/users/'.$this->reporter->getId(), $this->managerToken, ['isActive' => true]);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->json()['isActive']);
    }

    public function testReporterCannotUpdateUser(): void
    {
        $this->patch('/api/users/'.$this->agent->getId(), $this->reporterToken, ['role' => 'manager']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotUpdateUser(): void
    {
        $this->patch('/api/users/'.$this->reporter->getId(), $this->agentToken, ['isActive' => false]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= helpers =======

    private function request(string $method, string $uri, ?string $token, array $body = []): void
    {
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if (null !== $token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        $content = [] !== $body ? json_encode($body) : null;
        $this->client->request($method, $uri, [], [], $headers, $content);
    }

    private function patch(string $uri, string $token, array $body): void
    {
        $this->client->request('PATCH', $uri, [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ], json_encode($body));
    }

    /** @return array<mixed> */
    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }
}
