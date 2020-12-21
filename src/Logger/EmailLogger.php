<?php

namespace Hgabka\EmailBundle\Logger;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\EmailLog;
use Hgabka\EmailBundle\Event\MailerEvent;

class EmailLogger
{
    /** @var Registry */
    protected $doctrine;

    /** @var bool */
    protected $useEmailLogging;

    /**
     * EmailLogger constructor.
     *
     * @param mixed $useEmailLogging
     */
    public function __construct(Registry $doctrine, $useEmailLogging)
    {
        $this->doctrine = $doctrine;
        $this->useEmailLogging = $useEmailLogging;
    }

    /**
     * Log message to DB.
     *
     * @param sfEvent $event
     */
    public function logMessage(MailerEvent $event)
    {
        if (!$this->useEmailLogging) {
            return;
        }

        $message = $event->getMessage();
        $model = new EmailLog();
        $model->fromMessage($message);

        $em = $this->doctrine->getManager();
        $em->persist($model);
        $em->flush($model);
    }
}
