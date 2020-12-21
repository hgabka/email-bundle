<?php

namespace Hgabka\EmailBundle\Mailer;

use Hgabka\EmailBundle\Event\MailerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AddHeadersPlugin implements \Swift_Events_SendListener
{
    /** @var array */
    protected $config;

    /** @var EventDispatcher */
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @param EventDispatcher $dispatcher An event dispatcher instance
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return AddHeadersPlugin
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Invoked immediately before the Message is sent.
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        if (!($headers = $this->config)) {
            return;
        }

        $messageHeaders = $message->getHeaders();
        $event = new MailerEvent();
        $event->setMessage($message);
        $event->setParameters(['messageHeaders' => $messageHeaders, 'configHeaders' => $this->config]);

        $this->dispatcher->dispatch(MailerEvent::EVENT_ADD_HEADERS, $event);
        $headers = $event->getReturnValue();

        if (!\is_array($headers)) {
            return;
        }

        foreach ($headers as $name => $value) {
            $messageHeaders->addTextHeader($name, $value);
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
    }
}
