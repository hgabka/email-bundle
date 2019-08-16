<?php

namespace Hgabka\EmailBundle\Logger;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\EmailLog;
use Hgabka\EmailBundle\Event\MailerEvent;

class EmailLogger
{
    /** @var Registry */
    protected $doctrine;

    /**
     * EmailLogger constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Log message to DB.
     *
     * @param sfEvent $event
     */
    public function logMessage(MailerEvent $event)
    {
        $message = $event->getMessage();
        $model = new EmailLog();
        $model->fromMessage($message);

        $em = $this->doctrine->getManager();
        $em->persist($model);
        $em->flush($model);
    }
}
