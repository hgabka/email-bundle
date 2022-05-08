<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageSubscriberRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'hg_email_message_subscriber')]
#[ORM\Entity(repositoryClass: MessageSubscriberRepository::class)]
#[UniqueEntity('email')]
class MessageSubscriber
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\OneToMany(targetEntity: MessageListSubscription::class, cascade: ['all'], mappedBy: 'subscriber')]
    #[Assert\Valid]
    protected Collection|array|null $listSubscriptions = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    #[Assert\Email]
    protected ?string $email = null;

    #[ORM\Column(name: 'locale', type: 'string', length: 2, nullable: true)]
    protected ?string $locale = null;

    #[ORM\Column(name: 'token', type: 'string', length: 255, nullable: true)]
    protected ?string $token = null;

    protected Collection|array|null $lists = null;

    public function __construct()
    {
        $this->listSubscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getListSubscriptions(): Collection|array|null
    {
        return $this->listSubscriptions;
    }

    public function setListSubscriptions(Collection|array|null $listSubscriptions): self
    {
        $this->listSubscriptions = $listSubscriptions;

        return $this;
    }

    public function addListSubscription(MessageListSubscription $listSubscription): self
    {
        if (!$this->listSubscriptions->contains($listSubscription)) {
            $this->listSubscriptions[] = $listSubscription;

            $listSubscription->setList($this);
        }

        return $this;
    }

    public function removeListSubscription(MessageListSubscription $listSubscription): self
    {
        $this->listSubscriptions->removeElement($listSubscription);

        return $this;
    }

    public function getLists(): Collection|array|null
    {
        return $this->lists;
    }

    public function setLists(Collection|array|null $lists): self
    {
        $this->lists = $lists;

        return $this;
    }
}
