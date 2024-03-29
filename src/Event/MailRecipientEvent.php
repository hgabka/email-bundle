<?php

namespace Hgabka\EmailBundle\Event;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Symfony\Contracts\EventDispatcher\Event;

class MailRecipientEvent extends Event
{
    /** @var MailBuilder */
    protected $builder;

    /** @var array */
    protected $recipientData;

    /**
     * MailRecipientEvent constructor.
     */
    public function __construct(MailBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return array
     */
    public function getRecipientData()
    {
        return $this->recipientData;
    }

    /**
     * @param array $recipientData
     *
     * @return MailRecipientEvent
     */
    public function setRecipientData($recipientData)
    {
        $this->recipientData = $recipientData;

        return $this;
    }
}
