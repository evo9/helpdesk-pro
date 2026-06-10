<?php

declare(strict_types=1);

namespace App\Tests\Functional\Ticket;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\Ticket\Domain\Enum\TicketStatus;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuditLogApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $reporter;
    private User $agent;
    private Category $category;
    private string $reporterToken;
    private string $agentToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanDatabase();

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class);

        $this->reporter = new User('reporter@audit.helpdesk', '', 'Reporter', UserRole::REPORTER);
        $this->reporter->updatePassword($hasher->hashPassword($this->reporter, 'Password1!'));
        $this->em->persist($this->reporter);

        $this->agent = new User('agent@audit.helpdesk', '', 'Agent', UserRole::AGENT);
        $this->agent->updatePassword($hasher->hashPassword($this->agent, 'Password1!'));
        $this->em->persist($this->agent);

        $this->category = new Category('IT');
        $this->em->persist($this->category);
        $this->em->flush();

        $this->reporterToken = $jwt->create($this->reporter);
        $this->agentToken = $jwt->create($this->agent);
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    public function testAgentCanAccessAuditLog(): void
    {
        $ticket = $this->createTicket();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $this->assertResponseIsSuccessful();
        $this->assertIsArray($this->members());
    }

    public function testReporterCannotAccessAuditLog(): void
    {
        $ticket = $this->createTicket();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->reporterToken);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAuditLogContainsTicketCreatedEntry(): void
    {
        $ticket = $this->createTicket();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $actions = array_column($this->members(), 'action');
        $this->assertContains('ticket.created', $actions);
    }

    public function testAuditLogContainsStatusChangedEntry(): void
    {
        $ticket = $this->createTicket();
        $ticket->assignTo($this->agent);
        $ticket->changeStatus(TicketStatus::IN_PROGRESS);
        $this->em->flush();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $actions = array_column($this->members(), 'action');
        $this->assertContains('ticket.status_changed', $actions);
    }

    public function testAuditLogContainsCommentAddedEntry(): void
    {
        $ticket = $this->createTicket();
        $comment = new Comment($ticket, $this->agent, 'Working on it', false);
        $this->em->persist($comment);
        $this->em->flush();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $actions = array_column($this->members(), 'action');
        $this->assertContains('comment.added', $actions);
    }

    public function testAuditLogIsSortedByCreatedAtDesc(): void
    {
        $ticket = $this->createTicket();
        $ticket->assignTo($this->agent);
        $ticket->changeStatus(TicketStatus::IN_PROGRESS);
        $this->em->flush();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $members = $this->members();
        $this->assertGreaterThanOrEqual(2, \count($members));

        $dates = array_column($members, 'createdAt');
        $sorted = $dates;
        rsort($sorted);
        $this->assertSame($sorted, $dates, 'Audit log should be sorted by createdAt DESC');
    }

    public function testAuditLogEntryHasExpectedFields(): void
    {
        $ticket = $this->createTicket();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $members = $this->members();
        $this->assertNotEmpty($members);

        $entry = $members[0];
        $this->assertArrayHasKey('id', $entry);
        $this->assertArrayHasKey('action', $entry);
        $this->assertArrayHasKey('payload', $entry);
        $this->assertArrayHasKey('actorName', $entry);
        $this->assertArrayHasKey('createdAt', $entry);
    }

    public function testStatusChangedPayloadContainsOldAndNewStatus(): void
    {
        $ticket = $this->createTicket();
        $ticket->assignTo($this->agent);
        $ticket->changeStatus(TicketStatus::IN_PROGRESS);
        $this->em->flush();

        $this->request('GET', '/api/tickets/'.$ticket->getId().'/audit', $this->agentToken);

        $statusEntry = current(array_filter($this->members(), static fn ($e) => 'ticket.status_changed' === $e['action']));

        $this->assertNotFalse($statusEntry);
        $this->assertArrayHasKey('from', $statusEntry['payload']);
        $this->assertArrayHasKey('to', $statusEntry['payload']);
        $this->assertSame('open', $statusEntry['payload']['from']);
        $this->assertSame('in_progress', $statusEntry['payload']['to']);
    }

    private function createTicket(): Ticket
    {
        $ticket = new Ticket('Test Ticket', 'Description', TicketPriority::MEDIUM, $this->category, $this->reporter, null, null, null);
        $this->em->persist($ticket);
        $this->em->flush();

        return $ticket;
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
        $this->em->createQuery('DELETE FROM App\Sla\Domain\Entity\Category c')->execute();
        $this->em->createQuery("DELETE FROM App\User\Domain\Entity\User u WHERE u.email LIKE :p")
            ->setParameter('p', '%@audit.helpdesk')
            ->execute();
    }
}
