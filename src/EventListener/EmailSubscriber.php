<?php

namespace Hgabka\EmailBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Hgabka\EmailBundle\Entity\AbstractQueue;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;

class EmailSubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'preRemove',
        ];
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();
        $manager = $args->getObjectManager();
        $attachments = [];

        if ($object instanceof EmailTemplate) {
            $attachments = $manager->getRepository(Attachment::class)->getByTemplate($object);
        } elseif ($object instanceof AbstractQueue) {
            $attachments = $manager->getRepository(Attachment::class)->getByQueue($object);
        } elseif ($object instanceof Message) {
            $attachments = $manager->getRepository(Attachment::class)->getByMessage($object);
        }

        foreach ($attachments as $attachment) {
            $manager->remove($attachment);
        }
    }
}
