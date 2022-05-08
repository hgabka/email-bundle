<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageListSubscriptionRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

#[ORM\Table(name: 'hg_email_message_list_subscription')]
#[ORM\Entity(repositoryClass: MessageListSubscriptionRepository::class)]
class MessageListSubscription
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MessageList::class, inversedBy: 'listSubscriptions', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MessageList $list = null;

    #[ORM\ManyToOne(targetEntity: MessageSubscriber::class, inversedBy: 'listSubscriptions', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'subscriber_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MessageSubscriber $subscriber = null;

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

    public function getSubscriber(): ?MessageSubscriber
    {
        return $this->subscriber;
    }

    public function setSubscriber(?MessageSubscriber $subscriber): self
    {
        $this->subscriber = $subscriber;

        return $this;
    }
}
