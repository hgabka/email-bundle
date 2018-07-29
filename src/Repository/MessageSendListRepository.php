<?php

namespace Hgabka\EmailBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageList;

class MessageSendListRepository extends EntityRepository
{
    public function deleteMessageFromAllLists(Message $message)
    {
        $this
            ->createQueryBuilder('s')
            ->delete()
            ->where('s.message = :message')
            ->setParameter('message', $message)
            ->getQuery()
            ->execute()
        ;
    }

    public function findForMessageAndList(Message $message, MessageList $list)
    {
        return $this
            ->createQueryBuilder('l')
            ->where('l.list = :list')
            ->andWhere('l.message = :message')
            ->setParameters([
                'list' => $list,
                'message' => $message,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
