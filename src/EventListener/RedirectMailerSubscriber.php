<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Helper\MailHelper;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

class RedirectMailerSubscriber implements EventSubscriberInterface
{
    /** @var array */
    protected $redirectConfig;

    /** @var bool */
    protected $debug;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var MailHelper */
    protected $mailHelper;

    /**
     * @param array $redirectConfig
     */
    public function __construct(HgabkaUtils $hgabkaUtils, MailHelper $mailHelper, array $redirectConfig, bool $debug)
    {
        $this->redirectConfig = $redirectConfig;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->debug = $debug;
        $this->mailHelper = $mailHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }

    public function onMessage(MessageEvent $event)
    {
        if (!$this->isEnabled()) {
            return;
        }
        $message = $event->getMessage();

        if (!$message instanceof Email) {
            return;
        }

        $redirectConfig = $this->redirectConfig;

        if (isset($redirectConfig['subject_append']) && (true === $redirectConfig['subject_append'])) {
            $originalTo = $message->getTo();
            $originalCc = $message->getCc();
            $originalBcc = $message->getBcc();

            $message->subject(
                $message->getSubject()
                . ($originalTo ? (' - Eredeti to: ' . $this->mailHelper->displayAddresses($originalTo)) : '')
                . ($originalCc ? (' - Eredeti cc: ' . $this->mailHelper->displayAddresses($originalCc)) : '')
                . ($originalBcc ? (' - Eredeti bcc: ' . $this->mailHelper->displayAddresses($originalBcc)) : '')
            );
        }

        // Add each hard coded recipient
        $recipients = $this->redirectConfig['recipients'];

        if (is_array($recipients)) {
            $message->to(...$recipients);
        } else {
            $message->to($recipients);
        }
        $message->getHeaders()->remove('Cc');
        $message->getHeaders()->remove('Bcc');
    }

    protected function checkHost(): bool
    {
        $redirectConfig = $this->redirectConfig;
        $hosts = isset($redirectConfig['hosts']) ? (!\is_array($redirectConfig['hosts']) ? [$redirectConfig['hosts']] : $redirectConfig['hosts']) : [];

        $ch = $this->hgabkaUtils->getHost();

        $currentHost = strtolower($ch);

        $hostEnabled = false;
        foreach ($hosts as $host) {
            if ((false !== strpos($currentHost, $host))) {
                $hostEnabled = true;
            }
        }

        return $this->debug || $hostEnabled;
    }

    protected function isEnabled(): bool
    {
        return $this->redirectConfig['enable'] && $this->checkHost();
    }
}
