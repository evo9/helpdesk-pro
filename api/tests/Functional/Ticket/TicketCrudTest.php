<?php

declare(strict_types=1);

namespace App\Tests\Functional\Ticket;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TicketCrudTest extends WebTestCase
{
    private KernelBrowser $client;
    private User $reporter;
    private User $agent;
    private User $manager;
    private Category $category;
    private string $reporterToken;
    private string $agentToken;
    private string $managerToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $this->reporter = new User('reporter@test.helpdesk', '', 'Reporter User', UserRole::REPORTER);
        $this->reporter->updatePassword($hasher->hashPassword($this->reporter, 'Password1!'));
        $em->persist($this->reporter);

        $this->agent = new User('agent@test.helpdesk', '', 'Agent User', UserRole::AGENT);
        $this->agent->updatePassword($hasher->hashPassword($this->agent, 'Password1!'));
        $em->persist($this->agent);

        $this->manager = new User('manager@test.helpdesk', '', 'Manager User', UserRole::MANAGER);
        $this->manager->updatePassword($hasher->hashPassword($this->manager, 'Password1!'));
        $em->persist($this->manager);

        $this->category = new Category('Support');
        $em->persist($this->category);

        $sla = new SlaPolicy($this->category, TicketPriority::MEDIUM, 4, 24);
        $em->persist($sla);

        $em->flush();

        $this->reporterToken = $jwtManager->create($this->reporter);
        $this->agentToken = $jwtManager->create($this->agent);
        $this->managerToken = $jwtManager->create($this->manager);
    }

    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Ticket\Domain\Entity\AuditLog l')->execute();
        $em->createQuery('DELETE FROM App\Ticket\Domain\Entity\Comment c')->execute();
        $em->createQuery('DELETE FROM App\Ticket\Domain\Entity\Ticket t')->execute();
        $em->createQuery('DELETE FROM App\Sla\Domain\Entity\SlaPolicy p')->execute();
        $em->createQuery('DELETE FROM App\Sla\Domain\Entity\Category c')->execute();
        $em->createQuery("DELETE FROM App\User\Domain\Entity\User u WHERE u.email LIKE :p")
            ->setParameter('p', '%@test.helpdesk')
            ->execute();
        parent::tearDown();
    }

    // ======= CREATE =======

    public function testReporterCanCreateTicket(): void
    {
        $this->request('POST', '/api/tickets', $this->reporterToken, [
            'title' => 'Broken keyboard',
            'description' => 'Keys not working',
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'medium',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertEquals('Broken keyboard', $data['title']);
        $this->assertEquals('open', $data['status']);
        $this->assertEquals('medium', $data['priority']);
        $this->assertNotNull($data['id']);
    }

    public function testCreateTicketSetsSlaDates(): void
    {
        $this->request('POST', '/api/tickets', $this->reporterToken, [
            'title' => 'SLA test',
            'description' => 'Check SLA',
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'medium',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertNotNull($data['responseDueAt']);
        $this->assertNotNull($data['resolutionDueAt']);
        $this->assertEquals('ok', $data['responseSlaStatus']);
        $this->assertEquals('ok', $data['resolutionSlaStatus']);
    }

    public function testAgentCannotCreateTicket(): void
    {
        $this->request('POST', '/api/tickets', $this->agentToken, [
            'title' => 'Test', 'description' => 'Test',
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'medium',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerCannotCreateTicket(): void
    {
        $this->request('POST', '/api/tickets', $this->managerToken, [
            'title' => 'Test', 'description' => 'Test',
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'medium',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedCannotCreateTicket(): void
    {
        $this->request('POST', '/api/tickets', null, [
            'title' => 'Test', 'description' => 'Test',
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'medium',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // ======= READ COLLECTION =======

    public function testReporterSeesOnlyOwnTickets(): void
    {
        $t1 = $this->createTicketDirectly($this->reporter);
        $this->createTicketDirectly($this->reporter); // second own ticket
        $other = new User('other@test.helpdesk', '', 'Other', UserRole::REPORTER);
        static::getContainer()->get(EntityManagerInterface::class)->persist($other);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $this->createTicketDirectly($other); // another reporter's ticket

        $this->request('GET', '/api/tickets', $this->reporterToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertCount(2, $data['hydra:member'] ?? $data['member'] ?? $data);
    }

    public function testAgentSeesOwnAndUnassignedTickets(): void
    {
        $assigned = $this->createTicketDirectly($this->reporter, $this->agent);
        $unassigned = $this->createTicketDirectly($this->reporter);
        $otherAgent = new User('otheragent@test.helpdesk', '', 'Other Agent', UserRole::AGENT);
        static::getContainer()->get(EntityManagerInterface::class)->persist($otherAgent);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $this->createTicketDirectly($this->reporter, $otherAgent);

        $this->request('GET', '/api/tickets', $this->agentToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $ids = array_column($data['hydra:member'] ?? $data['member'] ?? $data, 'id');
        $this->assertContains((string) $assigned->getId(), $ids);
        $this->assertContains((string) $unassigned->getId(), $ids);
        $this->assertCount(2, $ids);
    }

    public function testManagerSeesAllTickets(): void
    {
        $this->createTicketDirectly($this->reporter);
        $this->createTicketDirectly($this->reporter, $this->agent);

        $this->request('GET', '/api/tickets', $this->managerToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertCount(2, $data['hydra:member'] ?? $data['member'] ?? $data);
    }

    // ======= READ ITEM =======

    public function testReporterCanViewOwnTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->request('GET', '/api/tickets/'.$ticket->getId(), $this->reporterToken);
        $this->assertResponseIsSuccessful();
        $this->assertEquals((string) $ticket->getId(), $this->json()['id']);
    }

    public function testReporterCannotViewOthersTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);
        $otherReporter = new User('other2@test.helpdesk', '', 'Other', UserRole::REPORTER);
        static::getContainer()->get(EntityManagerInterface::class)->persist($otherReporter);
        static::getContainer()->get(EntityManagerInterface::class)->flush();
        $otherToken = static::getContainer()->get(JWTTokenManagerInterface::class)->create($otherReporter);

        $this->request('GET', '/api/tickets/'.$ticket->getId(), $otherToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCanViewUnassignedTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->request('GET', '/api/tickets/'.$ticket->getId(), $this->agentToken);
        $this->assertResponseIsSuccessful();
    }

    public function testNonExistentTicketReturns404(): void
    {
        $this->request('GET', '/api/tickets/00000000-0000-7000-8000-000000000000', $this->managerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    // ======= UPDATE STATUS =======

    public function testAgentCanChangeStatusOfOwnTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter, $this->agent);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->agentToken, ['status' => 'in_progress']);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('in_progress', $this->json()['status']);
    }

    public function testAgentCannotChangeStatusOfUnassignedTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->agentToken, ['status' => 'in_progress']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerCanChangeStatusOfAnyTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->managerToken, ['status' => 'in_progress']);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('in_progress', $this->json()['status']);
    }

    public function testReporterCannotChangeStatus(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->reporterToken, ['status' => 'in_progress']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testInvalidStatusTransitionReturns422(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->managerToken, ['status' => 'resolved']);
        $this->assertResponseStatusCodeSame(422);
    }

    // ======= ASSIGN =======

    public function testManagerCanAssignTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->managerToken, [
            'assignee' => '/api/users/'.$this->agent->getId(),
        ]);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertStringContainsString((string) $this->agent->getId(), $data['assignee'] ?? '');
    }

    public function testManagerCanUnassignTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter, $this->agent);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->managerToken, ['assignee' => null]);
        $this->assertResponseIsSuccessful();
        $this->assertNull($this->json()['assignee']);
    }

    public function testAgentCannotAssignTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->agentToken, [
            'assignee' => '/api/users/'.$this->agent->getId(),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= PRIORITY =======

    public function testManagerCanChangePriority(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->managerToken, ['priority' => 'high']);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('high', $this->json()['priority']);
    }

    // ======= DELETE =======

    public function testManagerCanDeleteTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->client->request('DELETE', '/api/tickets/'.$ticket->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->managerToken,
        ]);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testAgentCannotDeleteTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->client->request('DELETE', '/api/tickets/'.$ticket->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->agentToken,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testReporterCannotDeleteOwnTicket(): void
    {
        $ticket = $this->createTicketDirectly($this->reporter);

        $this->client->request('DELETE', '/api/tickets/'.$ticket->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->reporterToken,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= REOPEN =======

    public function testReporterCanReopenOwnResolvedTicket(): void
    {
        $ticket = $this->createResolvedTicketDirectly($this->reporter);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->reporterToken, ['status' => 'open']);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('open', $this->json()['status']);
    }

    public function testReporterCannotReopenExpiredTicket(): void
    {
        $ticket = $this->createResolvedTicketDirectly($this->reporter, 73);

        $this->patch('/api/tickets/'.$ticket->getId(), $this->reporterToken, ['status' => 'open']);
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

    private function createTicketDirectly(User $reporter, ?User $assignee = null): Ticket
    {
        $ticket = new Ticket(
            'Test ticket',
            'Test description',
            TicketPriority::MEDIUM,
            $this->category,
            $reporter,
            null, null, null,
        );
        if (null !== $assignee) {
            $ticket->assignTo($assignee);
        }
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($ticket);
        $em->flush();

        return $ticket;
    }

    private function createResolvedTicketDirectly(User $reporter, int $resolvedHoursAgo = 1): Ticket
    {
        $ticket = $this->createTicketDirectly($reporter);
        $ticket->changeStatus(TicketStatus::RESOLVED);
        $ticket->markResolved();

        $ref = new \ReflectionProperty(Ticket::class, 'resolvedAt');
        $ref->setValue($ticket, new \DateTimeImmutable("-{$resolvedHoursAgo} hours"));

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->flush();

        return $ticket;
    }
}
