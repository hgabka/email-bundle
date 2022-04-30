<?php

namespace Hgabka\EmailBundle\Logger;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\EmailLog;
use Hgabka\EmailBundle\Event\MailerEvent;
use Hgabka\EmailBundle\Helper\MailHelper;

class EmailLogger
{
    /** @var Registry */
    protected $doctrine;

    /** @var MailHelper */
    protected $mailHelper;

    /** @var bool */
    protected $useEmailLogging;

    /**
     * EmailLogger constructor.
     *
     * @param mixed $useEmailLogging
     */
    public function __construct(Registry $doctrine, MailHelper $mailHelper, bool $useEmailLogging)
    {
        $this->doctrine = $doctrine;
        $this->mailHelper = $mailHelper;
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
        $model->fromMessage($message, $this->mailHelper);

        $em = $this->doctrine->getManager();
        $em->persist($model);
        $em->flush($model);
    }
}
