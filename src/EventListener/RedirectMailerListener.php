<?php

namespace Hgabka\EmailBundle\EventListener;

use Hgabka\EmailBundle\Helper\MailHelper;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;

#[AsEventListener]
readonly class RedirectMailerListener
{
    public function __construct(
        private HgabkaUtils $hgabkaUtils,
        private MailHelper $mailHelper,
        private array $redirectConfig,
        private bool $debug
    )
    {
    }

    public function __invoke(MessageEvent $event): void
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
