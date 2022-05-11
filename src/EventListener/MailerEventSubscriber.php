<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Event\MailerEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

class MailerEventSubscriber implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcher $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if ($message instanceof Email) {
            $mailerEvent = new MailerEvent();
            $mailerEvent->setMessage($message);

            $this->dispatcher->dispatch($mailerEvent, MailerEvent::EVENT_MAIL_SENT);
        }
    }
}
