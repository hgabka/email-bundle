<?php

namespace Hgabka\KunstmaanEmailBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Hgabka\KunstmaanEmailBundle\Entity\Message;
use Hgabka\KunstmaanEmailBundle\Entity\MessageList;

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
