<?php

namespace Hgabka\EmailBundle\Model;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Hgabka\EmailBundle\Annotation\TemplateVar;
use Hgabka\EmailBundle\Helper\MessageSender;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** @var ParameterBagInterface */
    protected $parameterBag;

    /** @var MessageSender */
    protected $messageSender;

    protected $variableCache;

    /** @var string */
    protected $locale;

    protected $priority;

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
    public function setMessageSender(MessageSender $messageSender)
    {
        $this->messageSender = $messageSender;
    }

    /**
     * @required
     */
    public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @required
     */
    public function setParameterBag(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
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
                $annotation = $this->getPropertyAnnotation($property, TemplateVar::class);
                if ($annotation) {
                    $usName = Container::underscore($property->getName());
                    $placeholder = $annotation->getPlaceholder() ?: $usName;
                    $label = $annotation->getLabel() ?: 'mail_template.' . $this->getKey() . '.variable.' . $usName;
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
                $annotation = $this->getMethodAnnotation($method, TemplateVar::class);
                if ($annotation) {
                    $usName = Container::underscore(str_replace('get', '', $method->getName()));
                    $placeholder = $annotation->getPlaceholder() ?: $usName;
                    $label = $annotation->getLabel() ?: 'mail_template.' . $this->getKey() . '.variable.' . $usName;
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

    public function getDefaultSubject(): string
    {
        return $this->defaultSubject;
    }

    public function getDefaultTextContent(): string
    {
        return $this->defaultTextContent;
    }

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
        return $this->translator->trans('mail_template.' . $this->getKey() . '.title');
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

    public function getDefaultCc()
    {
        return null;
    }

    public function getDefaultBcc()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return AbstractEmailTemplateType
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
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
     * @return AbstractEmailTemplateType
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    public function setParameters($paramArray)
    {
        if (!empty($paramArray)) {
            $accessor =
                PropertyAccess::createPropertyAccessorBuilder()
                              ->enableExceptionOnInvalidIndex()
                              ->getPropertyAccessor()
            ;
            foreach ($paramArray as $key => $value) {
                $accessor->setValue($this, $key, $value);
            }
        }
    }

    public function send($paramArray = [], $sendParams = [], $locale = null)
    {
        $this->setParameters($paramArray);

        return $this->messageSender->sendTemplateMail($this, [], $sendParams, $locale);
    }

    public function alterEmail(Email $email): void
    {
    }

    protected function getPropertyAnnotation(\ReflectionProperty $property, $name)
    {
        if ('annotation' === $this->parameterBag->get('hg_email.template_var_reader_type')) {
            $reader = $this->annotationReader;

            return $reader->getPropertyAnnotation($property, $name);
        }
        $attributes = $property->getAttributes($name);
        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if ($name === $attribute->getName()) {
                    return new $name(...$attribute->getArguments());
                }
            }
        }
    }

    protected function getMethodAnnotation(\ReflectionMethod $method, $name)
    {
        if ('annotation' === $this->parameterBag->get('hg_email.template_var_reader_type')) {
            $reader = $this->annotationReader;

            return $reader->getMethodAnnotation($method, $name);
        }
        $reader = new AttributeReader();
        $annotations = $reader->getMethodAnnotations($method);

        $attributes = $method->getAttributes($name);

        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if ($name === $attribute->getName()) {
                    return new $name(...$attribute->getArguments());
                }
            }
        }
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
     *
     * @return string
     */
    protected function trans($id, $params = [], $domain = 'messages')
    {
        return $this->translator->trans($id, $params, $domain, $this->locale);
    }
}
