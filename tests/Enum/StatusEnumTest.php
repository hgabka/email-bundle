<?php

namespace Hgabka\EmailBundle\Tests\Enum;

use Hgabka\EmailBundle\Enum\MessageStatusEnum;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;
use PHPUnit\Framework\TestCase;

class StatusEnumTest extends TestCase
{
    public function testMessageStatuses(): void
    {
        $this->assertSame(['init', 'kuldendo', 'folyamatban', 'elkuldve'], MessageStatusEnum::getAvailableStatuses());
        $this->assertSame('hg_email.statuses.kuldendo', MessageStatusEnum::getStatusName('kuldendo'));
        $this->assertSame('', MessageStatusEnum::getStatusName(''));
        $this->assertSame('Unknown type (x)', MessageStatusEnum::getStatusName('x'));
        $this->assertSame(
            ['init' => 'hg_email.statuses.init', 'kuldendo' => 'hg_email.statuses.kuldendo', 'folyamatban' => 'hg_email.statuses.folyamatban', 'elkuldve' => 'hg_email.statuses.elkuldve'],
            MessageStatusEnum::getStatusTextChoices()
        );
    }

    public function testQueueStatuses(): void
    {
        $this->assertSame(['init', 'elkuldve', 'hiba', 'sikertelen', 'visszapattant'], QueueStatusEnum::getAvailableStatuses());
        $this->assertSame('Hiba', QueueStatusEnum::getStatusName('hiba'));
        $this->assertSame('Unknown type (nope)', QueueStatusEnum::getStatusName('nope'));
    }
}
