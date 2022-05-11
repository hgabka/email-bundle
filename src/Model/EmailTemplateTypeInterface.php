<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\Mime\Email;

interface EmailTemplateTypeInterface
{
    public function getComment(): ?string;

    public function getDefaultSubject(): ?string;

    public function getDefaultTextContent(): ?string;

    public function getDefaultHtmlContent(): ?string;

    public function getVariables(): ?array;

    public function getVariableValues(): ?array;

    public function getDefaultFromName(): ?string;

    public function getDefaultFromEmail(): ?string;

    public function getTitle(): ?string;

    public function getDefaultRecipients(): ?array;

    public function isPublic(): bool;

    public function isToEditable(): bool;

    public function isCcEditable(): bool;

    public function isBccEditable(): bool;

    public function isSenderEditable(): bool;

    public function getSenderText(): ?string;

    public function setLocale(?string $locale): self;

    public function getPriority(): ?int;

    public function setPriority(?int $priority): self;

    public function getDefaultCc(): mixed;

    public function getDefaultBcc(): mixed;

    public function setParameters(?array $paramArray): self;

    public function alterEmail(Email $email): void;
}
