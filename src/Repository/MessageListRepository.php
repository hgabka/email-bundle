<?php

namespace Hgabka\EmailBundle\Repository;

use Doctrine\ORM\EntityRepository;

class MessageListRepository extends EntityRepository
{
    public function getDefaultList()
    {
        return $this
            ->createQueryBuilder('l')
            ->where('l.isPublic = 1')
            ->andWhere('l.isDefault = 1')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
