<?php

namespace Hgabka\EmailBundle\Model;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Hgabka\EmailBundle\Annotation\TemplateVar;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Helper\MessageSender;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractEmailTemplateType implements EmailTemplateTypeInterface
{
    /** @var EmailTemplate */
    protected ?EmailTemplate $entity = null;

    /** @var string */
    protected string $comment = '';

    /** @var array */
    protected array $variables = [];

    /** @var string */
    protected string $defaultSubject = '';

    /** @var string */
    protected string $defaultTextContent = '';

    /** @var string */
    protected string $defaultHtmlContent = '';

    protected ?string $defaultFromName = null;

    protected ?string $defaultFromEmail = null;

    /** @var TranslatorInterface */
    protected ?TranslatorInterface $translator = null;

    /** @var Reader */
    protected ?Reader $annotationReader = null;

    /** @var ParameterBagInterface */
    protected ?ParameterBagInterface $parameterBag = null;

    /** @var MessageSender */
    protected ?MessageSender $messageSender = null;

    protected ?array $variableCache = null;

    /** @var string */
    protected ?string $locale = null;

    protected ?int $priority = null;

    #[Required]
    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    #[Required]
    public function setMessageSender(MessageSender $messageSender): self
    {
        $this->messageSender = $messageSender;

        return $this;
    }

    #[Required]
    public function setAnnotationReader(Reader $annotationReader): self
    {
        $this->annotationReader = $annotationReader;

        return $this;
    }

    #[Required]
    public function setParameterBag(ParameterBagInterface $parameterBag): self
    {
        $this->parameterBag = $parameterBag;

        return $this;
    }

    /**
     * @return EmailTemplate
     */
    public function getEntity(): ?EmailTemplate
    {
        return $this->entity;
    }

    /**
     * @param EmailTemplate $entity
     *
     * @return AbstractEmailTemplateType
     */
    public function setEntity(?EmailTemplate $entity): self
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
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @param string $htmlContent
     *
     * @return AbstractEmailTemplateType
     */
    public function setHtmlContent(?string $htmlContent): self
    {
        $this->htmlContent = $htmlContent;

        return $this;
    }

    /**
     * @return array
     */
    public function getVariables(): ?array
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
    public function getVariableValues(): ?array
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
    public function getDefaultFromName(): ?string
    {
        return $this->defaultFromName;
    }

    /**
     * @param mixed $fromName
     *
     * @return AbstractEmailTemplateType
     */
    public function setDefaultFromName(?string $fromName): self
    {
        $this->defaultFromName = $fromName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultFromEmail(): ?string
    {
        return $this->defaultFromEmail;
    }

    /**
     * @param mixed $fromEmail
     *
     * @return AbstractEmailTemplateType
     */
    public function setDefaultFromEmail(?string $fromEmail): self
    {
        $this->defaultFromEmail = $fromEmail;

        return $this;
    }

    public function getDefaultRecipients(): ?array
    {
        return [];
    }

    public function getTitle(): string
    {
        return $this->translator->trans('mail_template.' . $this->getKey() . '.title');
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isToEditable(): bool
    {
        return true;
    }

    public function isCcEditable(): bool
    {
        return true;
    }

    public function isBccEditable(): bool
    {
        return true;
    }

    public function isSenderEditable(): bool
    {
        return true;
    }

    public function getSenderText(): ?string
    {
        return null;
    }

    public function getDefaultCc(): mixed
    {
        return null;
    }

    public function getDefaultBcc(): mixed
    {
        return null;
    }

    /**
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return AbstractEmailTemplateType
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
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
     * @return AbstractEmailTemplateType
     */
    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function setParameters(?array $paramArray): self
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

        return $this;
    }

    public function send(array $paramArray = [], array $sendParams = [], ?string $locale = null): int|false
    {
        $this->setParameters($paramArray);

        return $this->messageSender->sendTemplateMail($this, [], $sendParams, $locale);
    }

    public function alterEmail(Email $email): void
    {
    }

    protected function getPropertyAnnotation(\ReflectionProperty $property, string $name): ?object
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

        return null;
    }

    protected function getMethodAnnotation(\ReflectionMethod $method, string $name): ?object
    {
        if ('annotation' === $this->parameterBag->get('hg_email.template_var_reader_type')) {
            $reader = $this->annotationReader;

            return $reader->getMethodAnnotation($method, $name);
        }
        $reader = new AttributeReader();
        $annotations = $reader->getMethodAttributes($method);

        $attributes = $method->getAttributes($name);

        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                if ($name === $attribute->getName()) {
                    return new $name(...$attribute->getArguments());
                }
            }
        }

        return null;
    }

    protected function getKey(): string
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
    protected function trans($id, $params = [], $domain = 'messages'): string
    {
        return $this->translator->trans($id, $params, $domain, $this->locale);
    }
}
