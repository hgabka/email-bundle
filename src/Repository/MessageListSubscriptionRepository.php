<?php

namespace Hgabka\KunstmaanEmailBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Hgabka\KunstmaanEmailBundle\Entity\MessageList;
use Hgabka\KunstmaanEmailBundle\Entity\MessageSubscriber;

class MessageListSubscriptionRepository extends EntityRepository
{
    public function findForSubscriberAndList(MessageSubscriber $subscriber, MessageList $list)
    {
        if (empty($subscriber->getId()) || empty($list->getId())) {
            return null;
        }

        return $this
            ->createQueryBuilder('l')
            ->where('l.subscriber = :subscr')
            ->andWhere('l.list = :list')
            ->setParameters([
                'subscr' => $subscriber,
                'list' => $list,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
