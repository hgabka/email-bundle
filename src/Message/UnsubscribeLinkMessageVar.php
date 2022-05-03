<?php

namespace Hgabka\EmailBundle\Message;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Symfony\Component\Mime\Address;

class UnsubscribeLinkMessageVar extends UnsubscribeUrlMessageVar
{
    public function getLabel(): string
    {
        return $this->translator->trans('hg_email.default_param_labels.unsubscribe_link');
    }

    public function getValue(?Message $message, ?Address $from = null, ?Address $to = null, ?string $locale = null, ?MessageQueue $queue = null): ?string
    {
        $url = parent::getValue($message, $from, $to, $locale, $queue);

        if (empty($url)) {
            return '';
        }

        $text = $this->translator->trans('hg_email.title.unsubscribe', [], 'messages', $locale);

        return sprintf('<a href="%s">%s</a>', $url, empty($text) ? $url : $text);
    }
}
