<?php

namespace Hgabka\EmailBundle\Message;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UnsubscribeUrlMessageVar extends AbstractUnsubscribeMessageVar
{
    public function getLabel(): string
    {
        return $this->translator->trans('hg_email.default_param_labels.unsubscribe_url');
    }

    public function getValue(?Message $message, ?Address $from = null, ?Address $to = null, ?string $locale = null, ?MessageQueue $queue = null): ?string
    {
        if (!$queue) {
            return '';
        }

        $params = \json_decode($queue->getParameters(), true);

        if (!isset($params['params']['token'])) {
            return '';
        }

        return $this->router->generate('hgabka_email_message_unsubscribe', ['token' => $params['params']['token']], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
