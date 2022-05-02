<?php

namespace Hgabka\EmailBundle\Model;

use Hgabka\EmailBundle\Entity\Message;

interface MessageVarInterface
{
    public function getPlaceholder(): string;

    public function getLabel(): string;

    public function getValue(Message $message, ?string $locale): string;

    public function setPriority(?int $priority);

    public function getPriority(): ?int;

    public function getType(): string;
}
