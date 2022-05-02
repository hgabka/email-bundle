<?php

namespace Hgabka\EmailBundle\Message;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Model\AbstractMessageVar;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebversionMessageVar extends AbstractMessageVar
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /**
     * @param UrlGenerator $router
     */
    public function __construct(UrlGeneratorInterface $router)
    {
        $this->router = $router;
    }

    public function getLabel(): string
    {
        return $this->translator->trans('hg_email.default_param_labels.webversion');
    }

    public function getValue(?Message $message, ?Address $from = null, ?Address $to = null, ?string $locale = null, ?MessageQueue $queue = null): ?string
    {
        if ($queue && $queue->getId()) {
            return $this->router->generate('hgabka_email_message_webversion', ['id' => $queue->getId(), 'hash' => $queue->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->translator->trans('hg_email.default_param_labels.webversion');
    }
}
