<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageListSubscriptionRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * MessageListSubscription.
 *
 * @ORM\Table(name="hg_email_message_list_subscription")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageListSubscriptionRepository")
 */
#[ORM\Table(name: 'hg_email_message_list_subscription')]
#[ORM\Entity(repositoryClass: MessageListSubscriptionRepository::class)]
class MessageListSubscription
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
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageList", inversedBy="listSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: MessageList::class, inversedBy: 'listSubscriptions', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MessageList $list = null;

    /**
     * @var MessageSubscriber
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageSubscriber", inversedBy="listSubscriptions", cascade={"persist"})
     * @ORM\JoinColumn(name="subscriber_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: MessageSubscriber::class, inversedBy: 'listSubscriptions', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'subscriber_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MessageSubscriber $subscriber = null;

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
     * @return MessageListSubscription
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
     * @return MessageListSubscription
     */
    public function setList(?MessageList $list): self
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return MessageSubscriber
     */
    public function getSubscriber(): ?MessageSubscriber
    {
        return $this->subscriber;
    }

    /**
     * @param MessageSubscriber $subscriber
     *
     * @return MessageListSubscription
     */
    public function setSubscriber(?MessageSubscriber $subscriber): self
    {
        $this->subscriber = $subscriber;

        return $this;
    }
}
