<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sla\Domain\Entity;

use App\Sla\Domain\Entity\Category;
use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testCreatesCategoryWithRequiredFields(): void
    {
        $category = new Category(name: 'Network Issues', description: 'All networking problems');

        $this->assertSame('Network Issues', $category->getName());
        $this->assertSame('All networking problems', $category->getDescription());
        $this->assertTrue($category->isActive());
        $this->assertNotNull($category->getId());
    }

    public function testCategoryWithNullDescription(): void
    {
        $category = new Category(name: 'Hardware');

        $this->assertNull($category->getDescription());
    }

    public function testDeactivate(): void
    {
        $category = new Category('Software');
        $category->deactivate();

        $this->assertFalse($category->isActive());
    }
}
