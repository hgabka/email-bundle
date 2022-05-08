<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageQueueRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

#[ORM\Table(name: 'hg_email_message_queue')]
#[ORM\Entity(repositoryClass: MessageQueueRepository::class)]
class MessageQueue extends AbstractQueue
{
    use TimestampableEntity;

    #[ORM\Column(name: 'to_name', type: 'string', length: 255, nullable: true)]
    protected ?string $toName = null;

    #[ORM\Column(name: 'to_email', type: 'string', length: 255, nullable: true)]
    protected ?string $toEmail = null;

    #[ORM\Column(name: 'locale', type: 'string', length: 2, nullable: true)]
    protected ?string $locale = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Message $message = null;

    #[ORM\Column(name: 'parameters', type: 'text', nullable: true)]
    protected ?string $parameters = null;

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getToName(): ?string
    {
        return $this->toName;
    }

    public function setToName(?string $toName): self
    {
        $this->toName = $toName;

        return $this;
    }

    public function getToEmail(): ?string
    {
        return $this->toEmail;
    }

    public function setToEmail(?string $toEmail): self
    {
        $this->toEmail = $toEmail;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getParameters(): ?string
    {
        return $this->parameters;
    }

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
