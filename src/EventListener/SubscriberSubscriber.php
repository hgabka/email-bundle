<?php

namespace Hgabka\EmailBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Hgabka\EmailBundle\Entity\EmailQueue;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\QueueManager;
use Hgabka\EmailBundle\Helper\SubscriptionManager;

class SubscriberSubscriber implements EventSubscriber
{
    /** @var SubscriptionManager */
    protected $manager;

    /** @var QueueManager */
    protected $queueManager;

    /**
     * SubscriberSubscriber constructor.
     *
     * @param QueueManager $queueManager
     */
    public function __construct(SubscriptionManager $manager)
    {
        $this->manager = $manager;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::preRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if (!$object instanceof MessageSubscriber) {
            return;
        }

        $this->manager->generateSubscriberToken($object);
        $args->getObjectManager()->flush();
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if (!$object instanceof MessageSubscriber) {
            return;
        }

        $this->manager->generateSubscriberToken($object);
        $args->getObjectManager()->flush();
    }

    public function preRemove(LifecycleEventArgs $args)
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
