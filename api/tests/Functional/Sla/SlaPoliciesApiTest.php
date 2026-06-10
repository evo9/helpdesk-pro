<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sla;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SlaPoliciesApiTest extends WebTestCase
{
    private KernelBrowser $client;
    private User $reporter;
    private User $agent;
    private User $manager;
    private Category $category;
    private SlaPolicy $sla;
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

        $this->reporter = new User('reporter@sla.helpdesk', '', 'Reporter', UserRole::REPORTER);
        $this->reporter->updatePassword($hasher->hashPassword($this->reporter, 'Password1!'));
        $em->persist($this->reporter);

        $this->agent = new User('agent@sla.helpdesk', '', 'Agent', UserRole::AGENT);
        $this->agent->updatePassword($hasher->hashPassword($this->agent, 'Password1!'));
        $em->persist($this->agent);

        $this->manager = new User('manager@sla.helpdesk', '', 'Manager', UserRole::MANAGER);
        $this->manager->updatePassword($hasher->hashPassword($this->manager, 'Password1!'));
        $em->persist($this->manager);

        $this->category = new Category('Support');
        $em->persist($this->category);

        $this->sla = new SlaPolicy($this->category, TicketPriority::MEDIUM, 4, 24);
        $em->persist($this->sla);

        $em->flush();

        $this->reporterToken = $jwt->create($this->reporter);
        $this->agentToken = $jwt->create($this->agent);
        $this->managerToken = $jwt->create($this->manager);
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
            ->setParameter('p', '%@sla.helpdesk')
            ->execute();
        parent::tearDown();
    }

    // ======= GET COLLECTION =======

    public function testAgentCanListSlaPolicies(): void
    {
        $this->request('GET', '/api/sla-policies', $this->agentToken);
        $this->assertResponseIsSuccessful();
        $members = $this->members();
        $ids = array_column($members, 'id');
        $this->assertContains((string) $this->sla->getId(), $ids);
    }

    public function testManagerCanListSlaPolicies(): void
    {
        $this->request('GET', '/api/sla-policies', $this->managerToken);
        $this->assertResponseIsSuccessful();
    }

    public function testReporterCannotListSlaPolicies(): void
    {
        $this->request('GET', '/api/sla-policies', $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedCannotListSlaPolicies(): void
    {
        $this->request('GET', '/api/sla-policies', null);
        $this->assertResponseStatusCodeSame(401);
    }

    // ======= GET ITEM =======

    public function testManagerCanGetSlaPolicy(): void
    {
        $this->request('GET', '/api/sla-policies/'.$this->sla->getId(), $this->managerToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertEquals((string) $this->sla->getId(), $data['id']);
        $this->assertEquals('medium', $data['priority']);
        $this->assertEquals(4, $data['responseHours']);
        $this->assertEquals(24, $data['resolutionHours']);
    }

    public function testReporterCannotGetSlaPolicy(): void
    {
        $this->request('GET', '/api/sla-policies/'.$this->sla->getId(), $this->reporterToken);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testNonExistentSlaPolicyReturns404(): void
    {
        $this->request('GET', '/api/sla-policies/00000000-0000-7000-8000-000000000000', $this->managerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    // ======= CREATE =======

    public function testManagerCanCreateSlaPolicy(): void
    {
        $this->request('POST', '/api/sla-policies', $this->managerToken, [
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'high',
            'responseHours' => 2,
            'resolutionHours' => 8,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertEquals('high', $data['priority']);
        $this->assertEquals(2, $data['responseHours']);
        $this->assertEquals(8, $data['resolutionHours']);
    }

    public function testReporterCannotCreateSlaPolicy(): void
    {
        $this->request('POST', '/api/sla-policies', $this->reporterToken, [
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'low',
            'responseHours' => 8,
            'resolutionHours' => 48,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotCreateSlaPolicy(): void
    {
        $this->request('POST', '/api/sla-policies', $this->agentToken, [
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'low',
            'responseHours' => 8,
            'resolutionHours' => 48,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= UPDATE =======

    public function testManagerCanUpdateSlaPolicy(): void
    {
        $this->patch('/api/sla-policies/'.$this->sla->getId(), $this->managerToken, [
            'responseHours' => 2,
            'resolutionHours' => 12,
        ]);

        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertEquals(2, $data['responseHours']);
        $this->assertEquals(12, $data['resolutionHours']);
    }

    public function testReporterCannotUpdateSlaPolicy(): void
    {
        $this->patch('/api/sla-policies/'.$this->sla->getId(), $this->reporterToken, [
            'responseHours' => 99,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotUpdateSlaPolicy(): void
    {
        $this->patch('/api/sla-policies/'.$this->sla->getId(), $this->agentToken, [
            'responseHours' => 99,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= DELETE =======

    public function testManagerCanDeleteSlaPolicy(): void
    {
        $fresh = new SlaPolicy($this->category, TicketPriority::CRITICAL, 1, 4);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($fresh);
        $em->flush();

        $this->client->request('DELETE', '/api/sla-policies/'.$fresh->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->managerToken,
        ]);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testReporterCannotDeleteSlaPolicy(): void
    {
        $this->client->request('DELETE', '/api/sla-policies/'.$this->sla->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->reporterToken,
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= 8.9 SNAPSHOT INVARIANT =======

    public function testChangingSlaPolicyDoesNotAffectExistingTickets(): void
    {
        // Create a ticket — SLA deadlines are snapshotted at creation time
        $this->request('POST', '/api/tickets', $this->reporterToken, [
            'title' => 'SLA snapshot test',
            'description' => 'Testing SLA snapshot invariant',
            'category' => '/api/categories/'.$this->category->getId(),
            'priority' => 'medium',
        ]);
        $this->assertResponseStatusCodeSame(201);
        $ticketData = $this->json();
        $ticketId = $ticketData['id'];
        $originalResolutionDueAt = $ticketData['resolutionDueAt'];
        $originalResponseDueAt = $ticketData['responseDueAt'];

        $this->assertNotNull($originalResolutionDueAt);
        $this->assertNotNull($originalResponseDueAt);

        // Change the SLA policy's hours dramatically
        $this->patch('/api/sla-policies/'.$this->sla->getId(), $this->managerToken, [
            'responseHours' => 999,
            'resolutionHours' => 9999,
        ]);
        $this->assertResponseIsSuccessful();

        // Verify the ticket's deadlines are unchanged (snapshot invariant)
        $this->request('GET', '/api/tickets/'.$ticketId, $this->reporterToken);
        $this->assertResponseIsSuccessful();
        $updatedTicket = $this->json();
        $this->assertEquals($originalResolutionDueAt, $updatedTicket['resolutionDueAt']);
        $this->assertEquals($originalResponseDueAt, $updatedTicket['responseDueAt']);
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

    /** @return array<array<mixed>> */
    private function members(): array
    {
        $data = $this->json();

        return $data['hydra:member'] ?? $data['member'] ?? $data;
    }
}
