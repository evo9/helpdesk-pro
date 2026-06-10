<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sla\Domain\Entity;

use App\Sla\Domain\Entity\Category;
use App\Sla\Domain\Entity\SlaPolicy;
use App\Sla\Domain\Enum\TicketPriority;
use PHPUnit\Framework\TestCase;

final class SlaPolicyTest extends TestCase
{
    public function testCreatesSlaPolicyWithRequiredFields(): void
    {
        $category = new Category('Hardware');
        $policy = new SlaPolicy(
            category: $category,
            priority: TicketPriority::HIGH,
            responseHours: 2,
            resolutionHours: 8,
        );

        $this->assertSame($category, $policy->getCategory());
        $this->assertSame(TicketPriority::HIGH, $policy->getPriority());
        $this->assertSame(2, $policy->getResponseHours());
        $this->assertSame(8, $policy->getResolutionHours());
        $this->assertNotNull($policy->getId());
    }
}
