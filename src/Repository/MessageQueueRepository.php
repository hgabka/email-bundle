<?php

namespace Hgabka\EmailBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;

class MessageQueueRepository extends EntityRepository
{
    /**
     * @return mixed
     */
    public function deleteMessageFromQueue(Message $message)
    {
        return $this
            ->createQueryBuilder('q')
            ->delete()
            ->where('q.message = :message')
            ->setParameter('message', $message)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param $email
     *
     * @return mixed
     */
    public function deleteEmailFromQueue($email)
    {
        return $this
            ->createQueryBuilder('q')
            ->delete()
            ->where('q.toEmail = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param $days
     *
     * @return mixed
     */
    public function clearQueue($days)
    {
        $q = $this
            ->createQueryBuilder('q')
            ->delete()
            ->where('q.status = :st')
            ->setParameter('st', QueueStatusEnum::STATUS_ELKULDVE)
        ;

        if (!empty($days)) {
            $q
                ->andWhere('q.updatedAt <= :date')
                ->setParameter('date', new \DateTime('-' . $days . 'days'))
            ;
        }

        return $q->getQuery()->execute();
    }

    /**
     * @return array
     */
    public function getSendDataForMessage(Message $message)
    {
        return $this->createQueryBuilder('q')
                    ->select('q.status AS status', 'COUNT(q.id) AS num')
                    ->where('q.message = :message')
                    ->groupBy('q.status')
                    ->setParameter('message', $message)
                    ->getQuery()
                    ->getArrayResult()
        ;
    }

    /**
     * @param $limit
     *
     * @return array
     */
    public function getErrorQueuesForSend($limit)
    {
        $q = $this
            ->createQueryBuilder('q')
            ->where('q.status = :status')
            ->setParameter('status', QueueStatusEnum::STATUS_HIBA)
            ->orderBy('q.createdAt', 'DESC')
        ;

        if (!empty($limit)) {
            $q->setMaxResults($limit);
        }

        return $q->getQuery()->getResult();
    }

    /**
     * @param $limit
     *
     * @return array
     */
    public function getNotSentQueuesForSend($limit)
    {
        $q = $this->createQueryBuilder('q')
                  ->where('q.status = :status')
                  ->setParameter('status', QueueStatusEnum::STATUS_INIT)
                  ->orderBy('q.createdAt', 'DESC')
        ;

        if (!empty($limit)) {
            $q->setMaxResults($limit);
        }

        return $q->getQuery()->getResult();
    }

    public function getForMessageAndEmail(Message $message, $email)
    {
        return
            $this
                ->createQueryBuilder('q')
                ->where('q.message = :msg')
                ->andWhere('q.toEmail = :email')
                ->setParameters([
                    'msg' => $message,
                    'email' => $email,
                ])
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;
    }
}
