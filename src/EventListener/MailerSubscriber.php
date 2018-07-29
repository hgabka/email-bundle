<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Event\MailerEvent;
use Hgabka\EmailBundle\Logger\EmailLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailerSubscriber implements EventSubscriberInterface
{
    /** @var EmailLogger */
    protected $logger;

    /** @var string */
    protected $strategy;

    /**
     * MailerSubscriber constructor.
     *
     * @param EmailLogger $logger
     * @param string      $strategy
     */
    public function __construct(EmailLogger $logger, string $strategy)
    {
        $this->logger = $logger;
        $this->strategy = $strategy;
    }

    public static function getSubscribedEvents()
    {
        return [
            MailerEvent::EVENT_SEND_CALLED => 'onSendCalled',
            MailerEvent::EVENT_MAIL_SENT => 'onMailSent',
            MailerEvent::EVENT_ADD_HEADERS => 'onAddHeaders',
        ];
    }

    /**
     * @param MailerEvent $event
     */
    public function onSendCalled(MailerEvent $event)
    {
        if ('mailer_send' === $this->strategy) {
            $this->logger->logMessage($event);
        }
    }

    /**
     * @param MailerEvent $event
     */
    public function onMailSent(MailerEvent $event)
    {
        if ('mailer_send' !== $this->strategy) {
            $this->logger->logMessage($event);
        }
    }

    /**
     * @param MailerEvent $event
     */
    public function onAddHeaders(MailerEvent $event)
    {
        $event->setReturnValue($event->getParameter('configHeaders'));
    }
}
