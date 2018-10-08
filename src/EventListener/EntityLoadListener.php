<?php

namespace Hgabka\EmailBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplateTranslation;
use Hgabka\EmailBundle\Entity\MessageTranslation;

class EntityLoadListener
{
    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $obj = $eventArgs->getEntity();
        $em = $eventArgs->getEntityManager();

        if ($obj instanceof EmailTemplateTranslation) {
            $productReflProp = $em->getClassMetadata(\get_class($obj))->reflClass->getProperty('attachments');
            $productReflProp->setAccessible(true);

            $collection = new ArrayCollection();
            $attachments = $em->getRepository(Attachment::class)->getByTemplate($obj->getTranslatable(), $obj->getLocale());
            foreach ($attachments as $att) {
                $collection->add($att);
            }

            $productReflProp->setValue($obj, $collection);
        }

        if ($obj instanceof MessageTranslation) {
            $productReflProp = $em->getClassMetadata(\get_class($obj))->reflClass->getProperty('attachments');
            $productReflProp->setAccessible(true);

            $collection = new ArrayCollection();
            $attachments = $em->getRepository(Attachment::class)->getByMessage($obj->getTranslatable(), $obj->getLocale());
            foreach ($attachments as $att) {
                $collection->add($att);
            }

            $productReflProp->setValue($obj, $collection);
        }
    }
}
