<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageSendListRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * MessageSendList.
 *
 * @ORM\Table(name="hg_email_message_send_list")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageSendListRepository")
 */
#[ORM\Table(name: 'hg_email_message_send_list')]
#[ORM\Entity(repositoryClass: MessageSendListRepository::class)]
class MessageSendList
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MessageList::class, inversedBy: 'sendLists', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MessageList $list = null;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'sendLists', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Message $message = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getList(): ?MessageList
    {
        return $this->list;
    }

    public function setList(?MessageList $list): self
    {
        $this->list = $list;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }
}
