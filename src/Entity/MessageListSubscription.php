<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\KunstmaanExtensionBundle\Traits\TimestampableEntity;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;

/**
 * MessageListSubscription.
 *
 * @ORM\Table(name="hg_kuma_email_message_list_subscription")
 * @ORM\Entity(repositoryClass="Hgabka\KunstmaanEmailBundle\Repository\MessageListSubscriptionRepository")
 */
class MessageListSubscription extends AbstractEntity
{
    use TimestampableEntity;

    /**
     * @var MessageList
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\MessageList", inversedBy="listSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $list;

    /**
     * @var MessageSubscriber
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\MessageSubscriber", inversedBy="listSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $subscriber;

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
