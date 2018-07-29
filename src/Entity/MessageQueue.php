<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * MessageQueue.
 *
 * @ORM\Table(name="hg_email_message_queue")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageQueueRepository")
 */
class MessageQueue extends AbstractQueue
{
    use TimestampableEntity;

    /**
     * @var string
     *
     * @ORM\Column(name="to_name", type="string", length=255, nullable=true)
     */
    protected $toName;

    /**
     * @var string
     *
     * @ORM\Column(name="to_email", type="string", length=255, nullable=true)
     */
    protected $toEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=2, nullable=true)
     */
    protected $locale;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\Message")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    protected $parameters;

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return MessageQueue
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getToName(): string
    {
        return $this->toName;
    }

    /**
     * @param string $toName
     *
     * @return MessageQueue
     */
    public function setToName($toName)
    {
        $this->toName = $toName;

        return $this;
    }

    /**
     * @return string
     */
    public function getToEmail(): string
    {
        return $this->toEmail;
    }

    /**
     * @param string $toEmail
     *
     * @return MessageQueue
     */
    public function setToEmail($toEmail)
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return MessageQueue
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     *
     * @return MessageQueue
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }
}
