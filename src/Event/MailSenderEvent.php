<?php

namespace Hgabka\EmailBundle\Event;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Symfony\Component\EventDispatcher\Event;

class MailSenderEvent extends Event
{
    /** @var MailBuilder */
    protected $builder;

    /** @var array */
    protected $senderData;

    /**
     * MailSenderEvent constructor.
     *
     * @param MailBuilder $builder
     */
    public function __construct(MailBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return mixed
     */
    public function getSenderData()
    {
        return $this->senderData;
    }

    /**
     * @param mixed $senderData
     *
     * @return MailSenderEvent
     */
    public function setSenderData($senderData)
    {
        $this->senderData = $senderData;

        return $this;
    }
}
