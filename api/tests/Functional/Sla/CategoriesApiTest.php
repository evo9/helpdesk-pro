<?php

declare(strict_types=1);

namespace App\Tests\Functional\Sla;

use App\Sla\Domain\Entity\Category;
use App\User\Domain\Entity\User;
use App\User\Domain\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CategoriesApiTest extends WebTestCase
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
        $jwt = static::getContainer()->get(JWTTokenManagerInterface::class);

        $this->reporter = new User('reporter@cat.helpdesk', '', 'Reporter', UserRole::REPORTER);
        $this->reporter->updatePassword($hasher->hashPassword($this->reporter, 'Password1!'));
        $em->persist($this->reporter);

        $this->agent = new User('agent@cat.helpdesk', '', 'Agent', UserRole::AGENT);
        $this->agent->updatePassword($hasher->hashPassword($this->agent, 'Password1!'));
        $em->persist($this->agent);

        $this->manager = new User('manager@cat.helpdesk', '', 'Manager', UserRole::MANAGER);
        $this->manager->updatePassword($hasher->hashPassword($this->manager, 'Password1!'));
        $em->persist($this->manager);

        $this->category = new Category('Support', 'General support');
        $em->persist($this->category);

        $em->flush();

        $this->reporterToken = $jwt->create($this->reporter);
        $this->agentToken = $jwt->create($this->agent);
        $this->managerToken = $jwt->create($this->manager);
    }

    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Sla\Domain\Entity\SlaPolicy p')->execute();
        $em->createQuery('DELETE FROM App\Sla\Domain\Entity\Category c')->execute();
        $em->createQuery("DELETE FROM App\User\Domain\Entity\User u WHERE u.email LIKE :p")
            ->setParameter('p', '%@cat.helpdesk')
            ->execute();
        parent::tearDown();
    }

    // ======= GET COLLECTION =======

    public function testReporterCanListCategories(): void
    {
        $this->request('GET', '/api/categories', $this->reporterToken);
        $this->assertResponseIsSuccessful();
        $members = $this->members();
        $this->assertIsArray($members);
        $ids = array_column($members, 'id');
        $this->assertContains((string) $this->category->getId(), $ids);
    }

    public function testAgentCanListCategories(): void
    {
        $this->request('GET', '/api/categories', $this->agentToken);
        $this->assertResponseIsSuccessful();
    }

    public function testManagerCanListCategories(): void
    {
        $this->request('GET', '/api/categories', $this->managerToken);
        $this->assertResponseIsSuccessful();
    }

    public function testUnauthenticatedCannotListCategories(): void
    {
        $this->request('GET', '/api/categories', null);
        $this->assertResponseStatusCodeSame(401);
    }

    // ======= GET ITEM =======

    public function testReporterCanGetCategory(): void
    {
        $this->request('GET', '/api/categories/'.$this->category->getId(), $this->reporterToken);
        $this->assertResponseIsSuccessful();
        $data = $this->json();
        $this->assertEquals((string) $this->category->getId(), $data['id']);
        $this->assertEquals('Support', $data['name']);
        $this->assertEquals('General support', $data['description']);
        $this->assertTrue($data['isActive']);
    }

    public function testNonExistentCategoryReturns404(): void
    {
        $this->request('GET', '/api/categories/00000000-0000-7000-8000-000000000000', $this->managerToken);
        $this->assertResponseStatusCodeSame(404);
    }

    // ======= CREATE =======

    public function testManagerCanCreateCategory(): void
    {
        $this->request('POST', '/api/categories', $this->managerToken, [
            'name' => 'Hardware',
            'description' => 'Hardware issues',
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $this->json();
        $this->assertEquals('Hardware', $data['name']);
        $this->assertEquals('Hardware issues', $data['description']);
        $this->assertTrue($data['isActive']);
        $this->assertNotNull($data['id']);
    }

    public function testReporterCannotCreateCategory(): void
    {
        $this->request('POST', '/api/categories', $this->reporterToken, ['name' => 'Test']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotCreateCategory(): void
    {
        $this->request('POST', '/api/categories', $this->agentToken, ['name' => 'Test']);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= UPDATE =======

    public function testManagerCanRenameCategory(): void
    {
        $this->patch('/api/categories/'.$this->category->getId(), $this->managerToken, [
            'name' => 'IT Support',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertEquals('IT Support', $this->json()['name']);
    }

    public function testManagerCanDeactivateCategory(): void
    {
        $this->patch('/api/categories/'.$this->category->getId(), $this->managerToken, [
            'isActive' => false,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->json()['isActive']);
    }

    public function testReporterCannotUpdateCategory(): void
    {
        $this->patch('/api/categories/'.$this->category->getId(), $this->reporterToken, ['name' => 'Hack']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAgentCannotUpdateCategory(): void
    {
        $this->patch('/api/categories/'.$this->category->getId(), $this->agentToken, ['name' => 'Hack']);
        $this->assertResponseStatusCodeSame(403);
    }

    // ======= DELETE =======

    public function testManagerCanDeleteCategory(): void
    {
        $fresh = new Category('ToDelete');
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist($fresh);
        $em->flush();

        $this->client->request('DELETE', '/api/categories/'.$fresh->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->managerToken,
        ]);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testReporterCannotDeleteCategory(): void
    {
        $this->client->request('DELETE', '/api/categories/'.$this->category->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->reporterToken,
        ]);
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

    /** @return array<array<mixed>> */
    private function members(): array
    {
        $data = $this->json();

        return $data['hydra:member'] ?? $data['member'] ?? $data;
    }
}
