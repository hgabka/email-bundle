<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Helper\MailHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

class AddHeadersMailerSubscriber implements EventSubscriberInterface
{
    /** @var array */
    protected $headersConfig;

    /** @var MailHelper */
    protected $mailHelper;

    /**
     * @param MailHelper $mailHelper
     * @param array      $headersConfig
     */
    public function __construct(MailHelper $mailHelper, array $headersConfig)
    {
        $this->mailHelper = $mailHelper;
        $this->headersConfig = $headersConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event): void
    {
        if (empty($this->headersConfig)) {
            return;
        }

        $message = $event->getMessage();

        if (!$message instanceof Email) {
            return;
        }

        $this->mailHelper->addHeadersFromArray($message, $this->headersConfig);
    }
}
