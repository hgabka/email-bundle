<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Event\MailerEvent;
use Hgabka\EmailBundle\Logger\EmailLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MailerSubscriber implements EventSubscriberInterface
{
    /** @var EmailLogger */
    protected ?EmailLogger $logger = null;

    protected ?string $strategy = null;

    /**
     * MailerSubscriber constructor.
     */
    public function __construct(EmailLogger $logger, string $strategy)
    {
        $this->logger = $logger;
        $this->strategy = $strategy;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MailerEvent::EVENT_MAIL_SENT => 'onMailSent',
            MailerEvent::EVENT_ADD_HEADERS => 'onAddHeaders',
        ];
    }

    public function onMailSent(MailerEvent $event): void
    {
        $this->logger->logMessage($event);
    }

    public function onAddHeaders(MailerEvent $event): void
    {
        $event->setReturnValue($event->getParameter('configHeaders'));
    }
}
