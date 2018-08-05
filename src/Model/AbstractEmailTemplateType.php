<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;

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
        return $this->variables;
    }

    /**
     * @param mixed $onlyNames
     *
     * @return array
     */
    public function getVariableValues()
    {
        $vars = [];
        foreach ($this->variables as $key => $varData) {
            $vars[$key] = $varData;
            if (isset($vars[$key]['value'])) {
                $v = $vars[$key]['value'];
                if (is_callable($v)) {
                    $vars[$key]['value'] = call_user_func($vars[$key]['value']);
                } else {
                    $accessor = PropertyAccess::createPropertyAccessor();
                    $vars[$key]['value'] = $accessor->getValue($this, $vars[$key]['value']);
                }
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
        return '';
    }
}
