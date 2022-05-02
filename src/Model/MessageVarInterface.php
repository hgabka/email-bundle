<?php

namespace Hgabka\EmailBundle\Model;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Symfony\Component\Mime\Address;

interface MessageVarInterface
{
    public function getPlaceholder(): string;

    public function getLabel(): string;

    public function getValue(?Message $message, ?Address $from = null, ?Address $to = null, ?string $locale = null, ?MessageQueue $queue = null): ?string;

    public function isEnabled(?Message $message): bool;

    public function setPriority(?int $priority);

    public function getPriority(): ?int;

    public function getType(): string;
}
