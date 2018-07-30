<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * MessageListSubscription.
 *
 * @ORM\Table(name="hg_email_message_list_subscription")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageListSubscriptionRepository")
 */
class MessageListSubscription
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
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageList", inversedBy="listSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $list;

    /**
     * @var MessageSubscriber
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageSubscriber", inversedBy="listSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $subscriber;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return MessageListSubscription
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
     * @return MessageListSubscription
     */
    public function setList($list)
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return MessageSubscriber
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    /**
     * @param MessageSubscriber $subscriber
     *
     * @return MessageListSubscription
     */
    public function setSubscriber($subscriber)
    {
        $this->subscriber = $subscriber;

        return $this;
    }
}
