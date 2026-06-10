<?php

declare(strict_types=1);

namespace App\Tests\Functional\Ticket;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use App\Ticket\Domain\Entity\Comment;
use App\Ticket\Domain\Entity\Ticket;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CommentsTest extends WebTestCase
{
    private KernelBrowser $client;
    private User $reporter;
    private User $agent;
    private User $manager;
    private Category $category;
    private Ticket $ticket;
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

        $this->reporter = new User('reporter@comments.helpdesk', '', 'Reporter', UserRole::REPORTER);
        $this->reporter->updatePassword($hasher->hashPassword($this->reporter, 'Password1!'));
        $em->persist($this->reporter);

        $this->agent = new User('agent@comments.helpdesk', '', 'Agent', UserRole::AGENT);
        $this->agent->updatePassword($hasher->hashPassword($this->agent, 'Password1!'));
        $em->persist($this->agent);

        $this->manager = new User('manager@comments.helpdesk', '', 'Manager', UserRole::MANAGER);
        $this->manager->updatePassword($hasher->hashPassword($this->manager, 'Password1!'));
        $em->persist($this->manager);

        $this->category = new Category('Support');
        $em->persist($this->category);

        $sla = new SlaPolicy($this->category, TicketPriority::MEDIUM, 4, 24);
        $em->persist($sla);

        $this->ticket = new Ticket(
            'Broken keyboard',
            'Keys not working',
            TicketPriority::MEDIUM,
            $this->category,
            $this->reporter,
            null, null, null,
        );
        $this->ticket->assignTo($this->agent);
        $em->persist($this->ticket);

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
            ->setParameter('p', '%@comments.helpdesk')
            ->execute();
        parent::tearDown();
    }

    // ======= GET COLLECTION =======

    public function testReporterSeesPublicCommentsButNotInternal(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new Comment($this->ticket, $this->agent, 'Public reply', false));
        $em->persist(new Comment($this->ticket, $this->agent, 'Internal note', true));
        $em->flush();

        $this->request('GET', '/api/tickets/'.$this->ticket->getId().'/comments', $this->reporterToken);

        $this->assertResponseIsSuccessful();
        $members = $this->members();
        $this->assertCount(1, $members);
        $this->assertEquals('Public reply', $members[0]['body']);
    }

    public function testAgentSeesAllCommentsIncludingInternal(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new Comment($this->ticket, $this->agent, 'Public reply', false));
        $em->persist(new Comment($this->ticket, $this->agent, 'Internal note', true));
        $em->flush();

        $this->request('GET', '/api/tickets/'.$this->ticket->getId().'/comments', $this->agentToken);

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $this->members());
    }

    public function testManagerSeesAllCommentsIncludingInternal(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(new Comment($this->ticket, $this->agent, 'Public reply', false));
        $em->persist(new Comment($this->ticket, $this->agent, 'Internal note', true));
        $em->flush();

        $this->request('GET', '/api/tickets/'.$this->ticket->getId().'/comments', $this->managerToken);

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $this->members());
    }

    public function testUnauthenticatedCannotListComments(): void
    {
        $this->request('GET', '/api/tickets/'.$this->ticket->getId().'/comments', null);

        $this->assertResponseStatusCodeSame(401);
    }

    // ======= POST =======

    public function testReporterCanAddPublicComment(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->reporterToken, [
            'body' => 'Please help!',
            'isInternal' => false,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertEquals('Please help!', $data['body']);
        $this->assertFalse($data['isInternal']);
    }

    public function testReporterCannotAddInternalComment(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->reporterToken, [
            'body' => 'Trying to mark as internal',
            'isInternal' => true,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertFalse($this->json()['isInternal']);
    }

    public function testAgentCanAddInternalComment(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->agentToken, [
            'body' => 'Agent internal note',
            'isInternal' => true,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($this->json()['isInternal']);
    }

    public function testManagerCanAddInternalComment(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->managerToken, [
            'body' => 'Manager note',
            'isInternal' => true,
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($this->json()['isInternal']);
    }

    public function testUnassignedAgentCannotComment(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $otherAgent = new User('otheragent@comments.helpdesk', '', 'Other Agent', UserRole::AGENT);
        $otherAgent->updatePassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($otherAgent, 'Password1!')
        );
        $em->persist($otherAgent);
        $em->flush();
        $otherToken = static::getContainer()->get(JWTTokenManagerInterface::class)->create($otherAgent);

        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $otherToken, [
            'body' => 'I should not be able to post',
            'isInternal' => false,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnauthenticatedCannotAddComment(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', null, [
            'body' => 'Test',
            'isInternal' => false,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // ======= responded_at =======

    public function testFirstPublicAgentCommentSetsRespondedAt(): void
    {
        $this->assertNull($this->ticket->getRespondedAt());

        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->agentToken, [
            'body' => 'First agent reply',
            'isInternal' => false,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $ticket = $em->find(Ticket::class, $this->ticket->getId());
        $this->assertNotNull($ticket->getRespondedAt());
    }

    public function testInternalAgentCommentDoesNotSetRespondedAt(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->agentToken, [
            'body' => 'Internal note',
            'isInternal' => true,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $ticket = $em->find(Ticket::class, $this->ticket->getId());
        $this->assertNull($ticket->getRespondedAt());
    }

    public function testSecondPublicAgentCommentDoesNotOverrideRespondedAt(): void
    {
        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->agentToken, [
            'body' => 'First reply',
            'isInternal' => false,
        ]);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $ticket = $em->find(Ticket::class, $this->ticket->getId());
        $firstRespondedAt = $ticket->getRespondedAt();
        $this->assertNotNull($firstRespondedAt);

        // Need to re-assign ticket in context after clear
        $this->ticket = $ticket;

        $this->request('POST', '/api/tickets/'.$this->ticket->getId().'/comments', $this->agentToken, [
            'body' => 'Second reply',
            'isInternal' => false,
        ]);

        $em->clear();
        $ticket = $em->find(Ticket::class, $this->ticket->getId());
        $this->assertEquals($firstRespondedAt, $ticket->getRespondedAt());
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
