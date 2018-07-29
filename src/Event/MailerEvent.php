<?php

namespace Hgabka\KunstmaanEmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MailerEvent extends Event
{
    const EVENT_SEND_CALLED = 'email.log.mail_send_called';
    const EVENT_MAIL_SENT = 'email.log.mail_sent';
    const EVENT_ADD_HEADERS = 'email.add_headers';

    /** @var \Swift_Message */
    private $message;

    /** @var array */
    private $parameters;

    /** @var mixed */
    private $returnValue;

    /**
     * @return \Swift_Message
     */
    public function getMessage(): \Swift_Message
    {
        return $this->message;
    }

    /**
     * @param \Swift_Message $message
     *
     * @return MailerEvent
     */
    public function setMessage(\Swift_Message $message): MailerEvent
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter($key)
    {
        $params = $this->getParameters();

        return isset($params[$key]) ? $params[$key] : null;
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
