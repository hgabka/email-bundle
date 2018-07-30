<?php

namespace Hgabka\EmailBundle\Model;

class AbstractEmailTemplateType implements EmailTemplateTypeInterface
{
    /** @var EmailTemplate */
    protected $entity;

    /** @var string */
    protected $name = '';

    /** @var string */
    protected $comment = '';

    /** @var string */
    protected $subject = '';

    /** @var array */
    protected $variables = [];

    /** @var string */
    protected $defaultSubject = '';

    /** @var string */
    protected $defaultTextContent = '';

    /** @var string */
    protected $defaultHtmlContent = '';

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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return AbstractEmailTemplateType
     */
    public function setName($name)
    {
        $this->name = $name;

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
    public function getVariables(): array
    {
        return $this->variables;
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
}
