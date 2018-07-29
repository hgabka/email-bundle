<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\KunstmaanExtensionBundle\Traits\TimestampableEntity;

/**
 * MessageSendList.
 *
 * @ORM\Table(name="hg_email_message_send_list")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageSendListRepository")
 */
class MessageSendList
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var MessageList
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\MessageList", inversedBy="sendLists", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $list;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\Message", inversedBy="sendLists", cascade={"persist"})
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $message;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return MessageSendList
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return MessageList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param MessageList $list
     *
     * @return MessageSendList
     */
    public function setList($list)
    {
        $this->list = $list;

        return $this;
    }

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
     * @return MessageSendList
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }
}
