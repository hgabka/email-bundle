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

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    /**
     * @var MessageList
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageList", inversedBy="sendLists", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: MessageList::class, inversedBy: 'sendLists', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MessageList $list = null;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\Message", inversedBy="sendLists", cascade={"persist"})
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'sendLists', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Message $message = null;

    /**
     * @return mixed
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return MessageSendList
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return MessageList
     */
    public function getList(): ?MessageList
    {
        return $this->list;
    }

    /**
     * @param MessageList $list
     *
     * @return MessageSendList
     */
    public function setList(?MessageList $list): self
    {
        $this->list = $list;

        return $this;
    }

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
     * @return MessageSendList
     */
    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }
}
