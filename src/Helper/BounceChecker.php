<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;

class BounceChecker
{
    /** @var Registry */
    protected $doctrine;

    /** @var MailboxReader */
    protected $mailReader;

    /** @var array */
    protected $config;

    /**
     * BounceChecker constructor.
     *
     * @param Registry      $doctrine
     * @param MailboxReader $mailReader
     */
    public function __construct(Registry $doctrine, MailboxReader $mailReader)
    {
        $this->doctrine = $doctrine;
        $this->mailReader = $mailReader;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return BounceChecker
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    public function checkBounces()
    {
        if (!$this->mailReader) {
            return;
        }
        $count = 0;
        $messages = $this->mailReader->getMessages();

        foreach ($messages as $message) {
            $headers = $this->mailReader->parseBouncingHeaders($message);

            if (!isset($headers['Hg-Email-Id']) && !isset($headers['Hg-Message-Id'])) {
                continue;
            }
            $config = $this->config;
            $action = isset($config['after_process']) ? $config['after_process'] : 'leave_as_is';
            if (isset($headers['Hg-Message-Id'])) {
                $queue = $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:MessageQueue')->find($headers['Hg-Message-Id']);

                if ($queue) {
                    $queue->setStatus(QueueStatusEnum::STATUS_VISSZAPATTANT);

                    ++$count;
                }
            }

            if (isset($headers['Hg-Email-Id'])) {
                $queue = $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:EmailQueue')->find($headers['Hg-Email-Id']);

                if ($queue) {
                    $queue->setStatus(QueueStatusEnum::STATUS_VISSZAPATTANT);
                    ++$count;
                }
            }

            if ('delete' === $action) {
                $this->mailReader->deleteMessage($message);
            } elseif ('mark_as_read' === $action) {
                $this->mailReader->markMessageAsRead($message);
            }
        }

        $em = $this->doctrine->getManager();
        $em->flush();

        $this->mailReader->expunge();

        return $count;
    }
}
