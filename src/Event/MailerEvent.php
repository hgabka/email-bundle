<?php

namespace Hgabka\EmailBundle\Event;

use Symfony\Component\Mime\Email;
use Symfony\Contracts\EventDispatcher\Event;

class MailerEvent extends Event
{
    public const EVENT_SEND_CALLED = 'email.log.mail_send_called';
    public const EVENT_MAIL_SENT = 'email.log.mail_sent';
    public const EVENT_ADD_HEADERS = 'email.add_headers';

    /** @var Email */
    private $message;

    /** @var array */
    private $parameters;

    /** @var mixed */
    private $returnValue;

    public function getMessage(): Email
    {
        return $this->message;
    }

    /**
     * @return MailerEvent
     */
    public function setMessage(Email $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter($key)
    {
        $params = $this->getParameters();

        return $params[$key] ?? null;
    }

    /**
     * @param array $parameters
     *
     * @return MailerEvent
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReturnValue()
    {
        return $this->returnValue;
    }

    /**
     * @param mixed $returnValue
     *
     * @return MailerEvent
     */
    public function setReturnValue($returnValue)
    {
        $this->returnValue = $returnValue;

        return $this;
    }
}
