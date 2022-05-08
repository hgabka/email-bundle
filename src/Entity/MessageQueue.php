<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageQueueRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * MessageQueue.
 *
 * @ORM\Table(name="hg_email_message_queue")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageQueueRepository")
 */
#[ORM\Table(name: 'hg_email_message_queue')]
#[ORM\Entity(repositoryClass: MessageQueueRepository::class)]
class MessageQueue extends AbstractQueue
{
    use TimestampableEntity;

    /**
     * @var string
     *
     * @ORM\Column(name="to_name", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'to_name', type: 'string', length: 255, nullable: true)]
    protected ?string $toName = null;

    /**
     * @var string
     *
     * @ORM\Column(name="to_email", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'to_email', type: 'string', length: 255, nullable: true)]
    protected ?string $toEmail = null;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=2, nullable=true)
     */
    #[ORM\Column(name: 'locale', type: 'string', length: 2, nullable: true)]
    protected ?string $locale = null;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\Message")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Message $message = null;

    /**
     * @var string
     *
     * @ORM\Column(name="parameters", type="text", nullable=true)
     */
    #[ORM\Column(name: 'parameters', type: 'text', nullable: true)]
    protected ?string $parameters = null;

    /**
     * @return Message
     */
    public function getMessage(): ?Message
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return MessageQueue
     */
    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getToName(): ?string
    {
        return $this->toName;
    }

    /**
     * @param string $toName
     *
     * @return MessageQueue
     */
    public function setToName(?string $toName): self
    {
        $this->toName = $toName;

        return $this;
    }

    /**
     * @return string
     */
    public function getToEmail(): ?string
    {
        return $this->toEmail;
    }

    /**
     * @param string $toEmail
     *
     * @return MessageQueue
     */
    public function setToEmail(?string $toEmail): self
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return MessageQueue
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getParameters(): ?string
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     *
     * @return MessageQueue
     */
    public function setParameters(?string $parameters): self
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function getHash(): string
    {
        return md5($this->id . $this->getToEmail() . $this->getMessage()->getId());
    }
}
