<?php

namespace Hgabka\EmailBundle\Model;

use Doctrine\Common\Annotations\Reader;
use Hgabka\EmailBundle\Annotation\TemplateVar;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

class AbstractEmailTemplateType implements EmailTemplateTypeInterface
{
    /** @var EmailTemplate */
    protected $entity;

    /** @var string */
    protected $comment = '';

    /** @var array */
    protected $variables = [];

    /** @var string */
    protected $defaultSubject = '';

    /** @var string */
    protected $defaultTextContent = '';

    /** @var string */
    protected $defaultHtmlContent = '';

    protected $defaultFromName;

    protected $defaultFromEmail;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var Reader */
    protected $annotationReader;

    protected $variableCache;

    /** @var string */
    protected $culture;

    /**
     * @required
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @required
     */
    public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @return EmailTemplate
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param EmailTemplate $entity
     *
     * @return AbstractEmailTemplateType
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return AbstractEmailTemplateType
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @param string $htmlContent
     *
     * @return AbstractEmailTemplateType
     */
    public function setHtmlContent($htmlContent)
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        if (empty($this->variableCache)) {
            $variables = [];
            $refl = new \ReflectionObject($this);
            foreach ($refl->getProperties() as $property) {
                $annotation = $this->annotationReader->getPropertyAnnotation($property, TemplateVar::class);
                if ($annotation) {
                    $usName = Container::underscore($property->getName());
                    $placeholder = $annotation->getPlaceholder() ?: $usName;
                    $label = $annotation->getLabel() ?: 'mail_template.'.$this->getKey().'.variable.'.$usName;
                    $params = [
                        'label' => $label,
                        'value' => $property->getName(),
                    ];

                    if ('block' === $annotation->getType()) {
                        $params['type'] = 'block';
                    }

                    $variables[$placeholder] = $params;
                }
            }

            foreach ($refl->getMethods() as $method) {
                $annotation = $this->annotationReader->getMethodAnnotation($method, TemplateVar::class);
                if ($annotation) {
                    $usName = Container::underscore(str_replace('get', '', $method->getName()));
                    $placeholder = $annotation->getPlaceholder() ?: $usName;
                    $label = $annotation->getLabel() ?: 'mail_template.'.$this->getKey().'.variable.'.$usName;
                    $params = [
                        'label' => $label,
                        'value' => [$this, $method->getName()],
                    ];

                    if ('block' === $annotation->getType()) {
                        $params['type'] = 'block';
                    }

                    $variables[$placeholder] = $params;
                }
            }

            $this->variableCache = empty($this->variables) ? $variables : array_merge($variables, $this->variables);
        }

        return $this->variableCache;
    }

    /**
     * @param mixed $onlyNames
     *
     * @return array
     */
    public function getVariableValues()
    {
        $vars = [];
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getVariables() as $key => $varData) {
            $vars[$key] = $varData;
            if (isset($vars[$key]['value'])) {
                $v = $vars[$key]['value'];
                $vars[$key]['value'] = (string) (\is_callable($v) ? \call_user_func($v) : $accessor->getValue($this, $v));
            }
        }

        return $vars;
    }

    /**
     * @return string
     */
    public function getDefaultSubject(): string
    {
        return $this->defaultSubject;
    }

    /**
     * @return string
     */
    public function getDefaultTextContent(): string
    {
        return $this->defaultTextContent;
    }

    /**
     * @return string
     */
    public function getDefaultHtmlContent(): string
    {
        return $this->defaultHtmlContent;
    }

    /**
     * @return mixed
     */
    public function getDefaultFromName()
    {
        return $this->defaultFromName;
    }

    /**
     * @param mixed $fromName
     *
     * @return AbstractEmailTemplateType
     */
    public function setDefaultFromName($fromName)
    {
        $this->defaultFromName = $fromName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultFromEmail()
    {
        return $this->defaultFromEmail;
    }

    /**
     * @param mixed $fromEmail
     *
     * @return AbstractEmailTemplateType
     */
    public function setDefaultFromEmail($fromEmail)
    {
        $this->defaultFromEmail = $fromEmail;

        return $this;
    }

    public function getDefaultRecipients()
    {
        return [];
    }

    public function getTitle()
    {
        return $this->translator->trans('mail_template.'.$this->getKey().'.title');
    }

    public function isPublic()
    {
        return true;
    }

    public function isToEditable()
    {
        return true;
    }

    public function isCcEditable()
    {
        return true;
    }

    public function isBccEditable()
    {
        return true;
    }

    public function isSenderEditable()
    {
        return true;
    }

    public function getSenderText()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getCulture()
    {
        return $this->culture;
    }

    /**
     * @param string $culture
     *
     * @return AbstractEmailTemplateType
     */
    public function setCulture($culture)
    {
        $this->culture = $culture;

        return $this;
    }

    protected function getKey()
    {
        $fcqn = explode('\\', static::class);
        $class = array_pop($fcqn);

        return Container::underscore(str_replace(['Email', 'Template', 'Type'], '', $class));
    }

    /**
     * @param        $id
     * @param array  $params
     * @param string $domain
     * @return string
     */
    protected function trans($id, $params = [], $domain = 'messages')
    {
        return $this->translator->trans($id, $params, $domain, $this->culture);
    }
}
