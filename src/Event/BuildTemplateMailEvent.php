<?php

namespace Hgabka\EmailBundle\Event;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\ParamSubstituter;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Symfony\Component\EventDispatcher\Event;

class BuildTemplateMailEvent extends Event
{
    /** @var MailBuilder */
    protected $builder;

    /** @var ParamSubstituter */
    protected $paramSubstituter;

    /** @var Message */
    protected $message;

    /** @var EmailTemplateTypeInterface */
    protected $templateType;

    /** @var string */
    protected $body;

    /** @var string */
    protected $locale;

    /** @var array */
    protected $params;

    /**
     * @return MailBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * @param MailBuilder $builder
     *
     * @return BuildTemplateMailEvent
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return BuildTemplateMailEvent
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return EmailTemplateTypeInterface
     */
    public function getTemplateType()
    {
        return $this->templateType;
    }

    /**
     * @param EmailTemplateTypeInterface $templateType
     *
     * @return BuildTemplateMailEvent
     */
    public function setTemplateType($templateType)
    {
        $this->templateType = $templateType;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return BuildTemplateMailEvent
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
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
     * @return BuildTemplateMailEvent
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param array $params
     *
     * @return BuildTemplateMailEvent
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    public function getParamSubstituter(): ParamSubstituter
    {
        return $this->paramSubstituter;
    }

    /**
     * @param ParamSubstituter $paramSubstituter
     *
     * @return BuildTemplateMailEvent
     */
    public function setParamSubstituter($paramSubstituter)
    {
        $this->paramSubstituter = $paramSubstituter;

        return $this;
    }
}
