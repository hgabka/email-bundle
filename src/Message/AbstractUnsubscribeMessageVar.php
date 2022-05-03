<?php

namespace Hgabka\EmailBundle\Message;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Model\AbstractMessageVar;
use Hgabka\EmailBundle\Recipient\SubscriberRecipientTypeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

abstract class AbstractUnsubscribeMessageVar extends AbstractMessageVar
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var RecipientManager */
    protected $recipientManager;

    /**
     * @param UrlGenerator $router
     */
    public function __construct(UrlGeneratorInterface $router, RecipientManager $recipientManager)
    {
        $this->router = $router;
        $this->recipientManager = $recipientManager;
    }

    public function isEnabled(?Message $message): bool
    {
        if (!$message || empty($message->getToData())) {
            return false;
        }

        foreach ($message->getToData() as $recipientTypeData) {
            if (empty($recipientTypeData['type']) || !($recType = $this->recipientManager->getMessageRecipientType($recipientTypeData['type']))) {
                continue;
            }

            if ($recType instanceof SubscriberRecipientTypeInterface) {
                return true;
            }
        }
    }
}
