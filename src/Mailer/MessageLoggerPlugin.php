<?php

namespace Hgabka\KunstmaanEmailBundle\Mailer;

use Hgabka\KunstmaanEmailBundle\Event\MailerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessageLoggerPlugin implements \Swift_Events_SendListener
{
    const
        LOGGED_HEADER = 'X-WtLogger-Logged';

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

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        $transport = $evt->getTransport();
        $headers = $message->getHeaders();
        // spool transport
        if ($transport instanceof \Swift_SpoolTransport) {
            $headers->addTextHeader(self::LOGGED_HEADER, '1');
        } elseif ($headers->has(self::LOGGED_HEADER)) {
            $headers->remove(self::LOGGED_HEADER);
            $message->loggedByHgabkaEmailBundle = true;
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        $transport = $evt->getTransport();

        $event = new MailerEvent();
        $event->setMessage($message);
        // spool transport
        if ($transport instanceof \Swift_SpoolTransport) {
            $this->dispatcher->dispatch(MailerEvent::EVENT_SEND_CALLED, $event);
        }
        // any other
        else {
            if (!isset($message->loggedByHgabkaEmailBundle)) {
                $this->dispatcher->dispatch(MailerEvent::EVENT_SEND_CALLED, $event);
            }
            $this->dispatcher->dispatch(MailerEvent::EVENT_MAIL_SENT, $event);
        }
    }
}
