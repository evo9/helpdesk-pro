<?php

declare(strict_types=1);

namespace App\Tests\Unit\Sla\Domain\Enum;

use App\Sla\Domain\Enum\TicketPriority;
use PHPUnit\Framework\TestCase;

final class TicketPriorityTest extends TestCase
{
    public function testCasesHaveCorrectValues(): void
    {
        $this->assertSame('low', TicketPriority::LOW->value);
        $this->assertSame('medium', TicketPriority::MEDIUM->value);
        $this->assertSame('high', TicketPriority::HIGH->value);
        $this->assertSame('critical', TicketPriority::CRITICAL->value);
    }
}
