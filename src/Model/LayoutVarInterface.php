<?php

namespace Hgabka\EmailBundle\Model;

interface LayoutVarInterface
{
    public function getPlaceholder(): string;

    public function getLabel(): ?string;

    public function getValue(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        bool $webversion = false,
    );

    public function setPriority($priority): LayoutVarInterface;

    public function getPriority(): ?int;

    public function isEnabled(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        bool $webversion = false,
    ): bool;
}
