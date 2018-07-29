<?php

namespace Hgabka\KunstmaanEmailBundle\Mailer;

class AddRecipientsPlugin implements \Swift_Events_SendListener
{
    /** @var array */
    protected $config;

    /**
     * @return array
     */
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
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $message = $evt->getMessage();
        $recipients = $this->config;

        if (empty($recipients)) {
            return;
        }

        if (isset($recipients['cc']) && $recipients['cc']) {
            if (is_string($recipients['cc'])) {
                $message->addCc($recipients['cc']);
            }

            if (is_array($recipients['cc'])) {
                foreach ($recipients['cc'] as $to) {
                    $message->addCc($to);
                }
            }
        }

        if (isset($recipients['bcc']) && $recipients['bcc']) {
            if (is_string($recipients['bcc'])) {
                $message->addBcc($recipients['bcc']);
            }

            if (is_array($recipients['bcc'])) {
                foreach ($recipients['bcc'] as $to) {
                    $message->addBcc($to);
                }
            }
        }
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
    }
}
