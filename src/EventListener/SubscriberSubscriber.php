<?php

namespace Hgabka\KunstmaanEmailBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Hgabka\KunstmaanEmailBundle\Entity\MessageSubscriber;
use Hgabka\KunstmaanEmailBundle\Helper\QueueManager;
use Hgabka\KunstmaanEmailBundle\Helper\SubscriptionManager;

class SubscriberSubscriber implements EventSubscriber
{
    /** @var SubscriptionManager */
    protected $manager;

    /** @var QueueManager */
    protected $queueManager;

    /**
     * SubscriberSubscriber constructor.
     *
     * @param SubscriptionManager $manager
     * @param QueueManager        $queueManager
     */
    public function __construct(SubscriptionManager $manager)
    {
        $this->manager = $manager;
    }

    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'preRemove',
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
        $em->getRepository('HgabkaKunstmaanEmailBundle:MessageQueue')->deleteEmailFromQueue($object->getEmail());
        $queues = $em->getRepository('HgabkaKunstmaanEmailBundle:EmailQueue')->getQueues();
        foreach ($queues as $queue) {
            if ($queue->isForEmail($object->getEmail())) {
                $em->remove($queue);
            }
        }
        $em->flush();
    }
}
