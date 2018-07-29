<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\KunstmaanExtensionBundle\Traits\TimestampableEntity;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;

/**
 * MessageSendList.
 *
 * @ORM\Table(name="hg_kuma_email_message_send_list")
 * @ORM\Entity(repositoryClass="Hgabka\KunstmaanEmailBundle\Repository\MessageSendListRepository")
 */
class MessageSendList extends AbstractEntity
{
    use TimestampableEntity;

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
