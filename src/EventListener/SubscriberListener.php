<?php

namespace Hgabka\EmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Hgabka\EmailBundle\Entity\EmailQueue;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\SubscriptionManager;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preRemove)]
class SubscriberListener
{
    /** @var SubscriptionManager */
    protected SubscriptionManager $manager;

    /**
     * SubscriberSubscriber constructor.
     *
     * @param QueueManager $queueManager
     */
    public function __construct(SubscriptionManager $manager)
    {
        $this->manager = $manager;
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof MessageSubscriber) {
            return;
        }

        $this->manager->generateSubscriberToken($object);
        $args->getObjectManager()->flush();
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof MessageSubscriber) {
            return;
        }

        $this->manager->generateSubscriberToken($object);
        $args->getObjectManager()->flush();
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $object = $args->getObject();

        if (!$object instanceof MessageSubscriber) {
            return;
        }

        $em = $args->getObjectManager();
        $em->getRepository(MessageQueue::class)->deleteEmailFromQueue($object->getEmail());
        $queues = $em->getRepository(EmailQueue::class)->getQueues();
        foreach ($queues as $queue) {
            if ($queue->isForEmail($object->getEmail())) {
                $em->remove($queue);
            }
        }
        $em->flush();
    }
}
