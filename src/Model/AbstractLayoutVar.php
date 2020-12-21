<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractLayoutVar implements LayoutVarInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    protected $placeholder;

    protected $label;

    protected $priority;

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getPlaceholder()
    {
        $placeholder = $this->placeholder;
        if (empty($placeholder)) {
            $placeholder = $this->getKey();
        }

        return $placeholder;
    }

    public function getLabel()
    {
        $label = $this->label;
        if (empty($label)) {
            $label = 'hg_email.layout_var.'.$this->getKey();
        }

        return $this->translator->trans($label);
    }

    public function getValue($bodyHtml, $params, $locale)
    {
        return $params[$this->getPlaceholder()];
    }

    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param mixed $priority
     *
     * @return AbstractLayoutVar
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    protected function getKey()
    {
        $fcqn = explode('\\', static::class);
        $class = array_pop($fcqn);

        return Container::underscore(str_replace(['Layout', 'Var'], '', $class));
    }
}
