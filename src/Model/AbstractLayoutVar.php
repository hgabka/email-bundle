<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractLayoutVar implements LayoutVarInterface
{
    protected ?TranslatorInterface $translator = null;

    protected ?string $placeholder = null;

    protected ?string $label = null;

    protected ?int $priority = null;

    #[Required]
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getPlaceholder(): string
    {
        $placeholder = $this->placeholder;
        if (empty($placeholder)) {
            $placeholder = $this->getKey();
        }

        return $placeholder;
    }

    public function getLabel(): string
    {
        $label = $this->label;
        if (empty($label)) {
            $label = 'hg_email.layout_var.' . $this->getKey();
        }

        return $this->translator->trans($label);
    }

    public function getValue(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        bool $webversion = false,
    ): ?string {
        return $params[$this->getPlaceholder()];
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority): LayoutVarInterface
    {
        $this->priority = $priority;

        return $this;
    }

    public function isEnabled(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        bool $webversion = false,
    ): bool {
        return true;
    }

    protected function getKey(): string
    {
        $fcqn = explode('\\', static::class);
        $class = array_pop($fcqn);

        return Container::underscore(str_replace(['Layout', 'Var'], '', $class));
    }
}
