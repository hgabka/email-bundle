<?php

namespace Hgabka\EmailBundle\Event;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Model\MessageRecipientTypeInterface;
use Symfony\Component\EventDispatcher\Event;

class BuildMessageMailEvent extends Event
{
    /** @var MailBuilder */
    protected $builder;

    /** @var Message */
    protected $message;

    /** @var MessageRecipientTypeInterface */
    protected $recipientType;

    /** @var string */
    protected $body;

    /** @var array */
    protected $params;

    /** @var string */
    protected $locale;

    /**
     * @return MailBuilder
     */
    public function getBuilder(): MailBuilder
    {
        return $this->builder;
    }

    /**
     * @param MailBuilder $builder
     *
     * @return BuildMessageMailEvent
     */
    public function setBuilder($builder)
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return BuildMessageMailEvent
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     *
     * @return BuildMessageMailEvent
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param mixed $params
     *
     * @return BuildMessageMailEvent
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return MessageRecipientTypeInterface
     */
    public function getRecipientType(): MessageRecipientTypeInterface
    {
        return $this->recipientType;
    }

    /**
     * @param MessageRecipientTypeInterface $recipientType
     *
     * @return BuildMessageMailEvent
     */
    public function setRecipientType($recipientType)
    {
        $this->recipientType = $recipientType;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return BuildMessageMailEvent
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }
}
