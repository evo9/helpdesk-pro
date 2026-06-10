<?php

declare(strict_types=1);

namespace App\Tests\Functional\Dashboard;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private string $reporterToken;
    private string $agentToken;
    private string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanDatabase();

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class);

        $reporter = new User('reporter@dashboard.helpdesk', '', 'Reporter', UserRole::REPORTER);
        $reporter->updatePassword($hasher->hashPassword($reporter, 'Password1!'));
        $this->em->persist($reporter);

        $agent = new User('agent@dashboard.helpdesk', '', 'Agent', UserRole::AGENT);
        $agent->updatePassword($hasher->hashPassword($agent, 'Password1!'));
        $this->em->persist($agent);

        $manager = new User('manager@dashboard.helpdesk', '', 'Manager', UserRole::MANAGER);
        $manager->updatePassword($hasher->hashPassword($manager, 'Password1!'));
        $this->em->persist($manager);

        $category = new Category('Dashboard IT');
        $this->em->persist($category);

        $ticket = new Ticket('Test', 'Desc', TicketPriority::MEDIUM, $category, $reporter, null, null, null);
        $this->em->persist($ticket);

        $this->em->flush();

        $this->reporterToken = $jwt->create($reporter);
        $this->agentToken = $jwt->create($agent);
        $this->managerToken = $jwt->create($manager);
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    // ======= /api/dashboard/summary =======

    public function testManagerCanAccessSummary(): void
    {
        $this->request('GET', '/api/dashboard/summary', $this->managerToken);
        $this->assertResponseIsSuccessful();

        $data = $this->json();
        $this->assertArrayHasKey('statuses', $data);
        $this->assertArrayHasKey('slaBreachedToday', $data);
        $this->assertIsArray($data['statuses']);
    }

    public function testSummaryContainsAllStatuses(): void
    {
        $this->request('GET', '/api/dashboard/summary', $this->managerToken);
        $statuses = $this->json()['statuses'];

        foreach (['open', 'in_progress', 'pending', 'resolved', 'closed'] as $status) {
            $this->assertArrayHasKey($status, $statuses);
        }
    }

    public function testAgentCannotAccessSummary(): void
    {
        $this->request('GET', '/api/dashboard/summary', $this->agentToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testReporterCannotAccessSummary(): void
    {
        $this->request('GET', '/api/dashboard/summary', $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedCannotAccessSummary(): void
    {
        $this->request('GET', '/api/dashboard/summary', null);
        $this->assertResponseStatusCodeSame(401);
    }

    // ======= /api/dashboard/agents =======

    public function testManagerCanAccessAgents(): void
    {
        $this->request('GET', '/api/dashboard/agents', $this->managerToken);
        $this->assertResponseIsSuccessful();

        $members = $this->members();
        $this->assertIsArray($members);
        $this->assertNotEmpty($members);

        $entry = $members[0];
        $this->assertArrayHasKey('agentId', $entry);
        $this->assertArrayHasKey('name', $entry);
        $this->assertArrayHasKey('activeTickets', $entry);
        $this->assertArrayHasKey('resolvedLast30d', $entry);
    }

    public function testAgentCannotAccessAgentWorkload(): void
    {
        $this->request('GET', '/api/dashboard/agents', $this->agentToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testReporterCannotAccessAgentWorkload(): void
    {
        $this->request('GET', '/api/dashboard/agents', $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= /api/dashboard/tickets-by-category =======

    public function testManagerCanAccessTicketsByCategory(): void
    {
        $this->request('GET', '/api/dashboard/tickets-by-category', $this->managerToken);
        $this->assertResponseIsSuccessful();

        $members = $this->members();
        $this->assertIsArray($members);

        $entry = $members[0];
        $this->assertArrayHasKey('categoryId', $entry);
        $this->assertArrayHasKey('categoryName', $entry);
        $this->assertArrayHasKey('count', $entry);
    }

    public function testAgentCannotAccessTicketsByCategory(): void
    {
        $this->request('GET', '/api/dashboard/tickets-by-category', $this->agentToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testReporterCannotAccessTicketsByCategory(): void
    {
        $this->request('GET', '/api/dashboard/tickets-by-category', $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    private function request(string $method, string $uri, ?string $token): void
    {
        $headers = ['HTTP_ACCEPT' => 'application/json'];
        if (null !== $token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }
        $this->client->request($method, $uri, [], [], $headers);
    }

    /** @return array<mixed> */
    private function json(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true);
    }

    /** @return array<array<mixed>> */
    private function members(): array
    {
        $data = $this->json();

        return $data['hydra:member'] ?? $data['member'] ?? $data;
    }

    private function cleanDatabase(): void
    {
        $this->em->createQuery('DELETE FROM App\Ticket\Domain\Entity\AuditLog l')->execute();
        $this->em->createQuery('DELETE FROM App\Ticket\Domain\Entity\Comment c')->execute();
        $this->em->createQuery('DELETE FROM App\Ticket\Domain\Entity\Ticket t')->execute();
        $this->em->createQuery('DELETE FROM App\Sla\Domain\Entity\Category c WHERE c.name = :n')
            ->setParameter('n', 'Dashboard IT')->execute();
        $this->em->createQuery("DELETE FROM App\User\Domain\Entity\User u WHERE u.email LIKE :p")
            ->setParameter('p', '%@dashboard.helpdesk')
            ->execute();
    }
}
