<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractMessageVar implements MessageVarInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    protected $placeholder;

    protected $label;

    protected $priority;

    protected $type;

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getLabel(): string
    {
        $label = $this->label;

        if (empty($label)) {
            $label = $this->translator->trans('message_var_label.' . $this->getKey());
        }

        return $label;
    }

    /**
     * @return mixed
     */
    public function getPriority(): ?int
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     *
     * @return AbstractLayoutVar
     */
    public function setPriority(?int $priority)
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPlaceholder(): string
    {
        $placeholder = $this->placeholder;
        if (empty($placeholder)) {
            $placeholder = $this->getKey();
        }

        return $placeholder;
    }

    public function getType(): string
    {
        if (empty($this->type)) {
            return 'inline';
        }

        return $this->type;
    }

    protected function getKey(): string
    {
        $fcqn = explode('\\', static::class);
        $class = array_pop($fcqn);

        return Container::underscore(str_replace(['Message', 'Var'], '', $class));
    }
}
