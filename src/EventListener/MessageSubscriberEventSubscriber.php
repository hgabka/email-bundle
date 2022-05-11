<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Event\BuildMessageMailEvent;
use Hgabka\EmailBundle\Event\MailBuilderEvents;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MessageSubscriberEventSubscriber implements EventSubscriberInterface
{
    /** @var RecipientManager */
    protected RecipientManager $recipientManager;

    /**
     * MessageSubscriberEventSubscriber constructor.
     */
    public function __construct(RecipientManager $recipientManager)
    {
        $this->recipientManager = $recipientManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MailBuilderEvents::BUILD_MESSAGE_MAIL => 'onBuildMessageMail',
        ];
    }

    public function onBuildMessageMail(BuildMessageMailEvent $event): BuildMessageMailEvent
    {
        $message = $event->getMessage();
        $html = $event->getBody();
        $recType = $event->getRecipientType();
        if ($recType) {
            $altered = $recType->alterHtmlBody($html, $event->getParams(), $event->getLocale());
        }

        if (!empty($altered)) {
            $event->setBody($altered);
        }

        return $event;
    }
}
