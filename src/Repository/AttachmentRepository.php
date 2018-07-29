<?php

namespace Hgabka\KunstmaanEmailBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Hgabka\KunstmaanEmailBundle\Entity\AbstractQueue;
use Hgabka\KunstmaanEmailBundle\Entity\EmailTemplate;
use Hgabka\KunstmaanEmailBundle\Entity\Message;

class AttachmentRepository extends EntityRepository
{
    public function getByQueue(AbstractQueue $queue)
    {
        return $this
            ->createQueryBuilder('a')
            ->where('a.type = :type')
            ->andWhere('a.ownerId = :queueId')
            ->setParameters(
                [
                    'type' => get_class($queue),
                    'queueId' => $queue->getId(),
                ]
            )
            ->getQuery()
            ->getResult()
        ;
    }

    public function getByTypeAndId($type, $id, $locale = null)
    {
        return
            $this
                ->createQueryBuilderByTypeAndId($type, $id, $locale)
                ->getQuery()
                ->getResult()
        ;
    }

    public function deleteByTypeAndId($type, $id, $locale = null)
    {
        $this
            ->createQueryBuilderByTypeAndId($type, $id, $locale)
            ->delete()
            ->getQuery()
            ->execute()
        ;
    }

    public function getByTemplate(EmailTemplate $template, $locale = null)
    {
        return $this->getByTypeAndId(EmailTemplate::class, $template->getId(), $locale);
    }

    public function getByMessage(Message $message, $locale = null)
    {
        return $this->getByTypeAndId(Message::class, $message->getId(), $locale);
    }

    protected function createQueryBuilderByTypeAndId($type, $id, $locale)
    {
        $qb = $this
            ->createQueryBuilder('a')
            ->where('a.type = :type')
            ->andWhere('a.ownerId = :typeid')
            ->setParameters(['type' => $type, 'typeid' => $id])
        ;

        if (!empty($locale)) {
            $qb
                ->andWhere('a.locale = :locale')
                ->setParameter('locale', $locale);
        }

        return $qb;
    }
}
