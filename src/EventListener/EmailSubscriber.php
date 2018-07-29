<?php

namespace Hgabka\KunstmaanEmailBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Hgabka\KunstmaanEmailBundle\Entity\AbstractQueue;
use Hgabka\KunstmaanEmailBundle\Entity\EmailTemplate;
use Hgabka\KunstmaanEmailBundle\Entity\Message;

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
            $attachments = $manager->getRepository('HgabkaKunstmaanEmailBundle:Attachment')->getByTemplate($object);
        } elseif ($object instanceof AbstractQueue) {
            $attachments = $manager->getRepository('HgabkaKunstmaanEmailBundle:Attachment')->getByQueue($object);
        } elseif ($object instanceof Message) {
            $attachments = $manager->getRepository('HgabkaKunstmaanEmailBundle:Attachment')->getByMessage($object);
        }

        foreach ($attachments as $attachment) {
            $manager->remove($attachment);
        }
    }
}
