<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\MessageSubscriberRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Subscriber.
 *
 * @ORM\Table(name="hg_email_message_subscriber")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageSubscriberRepository")
 * @UniqueEntity("email")
 */
#[ORM\Table(name: 'hg_email_message_subscriber')]
#[ORM\Entity(repositoryClass: MessageSubscriberRepository::class)]
#[UniqueEntity('email')]
class MessageSubscriber
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
     * @var ArrayCollection|MessageListSubscription[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\MessageListSubscription", cascade={"all"}, mappedBy="subscriber", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    #[ORM\OneToMany(targetEntity: MessageListSubscription::class, cascade: ['all'], mappedBy: 'subscriber')]
    #[Assert\Valid]
    protected Collection|array|null $listSubscriptions = null;

    /**
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @Assert\Email()
     */
    #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
    #[Assert\Email]
    protected ?string $email = null;

    /**
     * @var string
     * @ORM\Column(name="locale", type="string", length=2, nullable=true)
     */
    #[ORM\Column(name: 'locale', type: 'string', length: 255, nullable: true)]
    protected ?string $locale = null;

    /**
     * @ORM\Column(name="token", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'token', type: 'string', length: 255, nullable: true)]
    protected ?string $token = null;

    /** @var array|MessageList[] */
    protected Collection|array|null $lists = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->listSubscriptions = new ArrayCollection();
    }

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
     * @return MessageSubscriber
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return MessageSubscriber
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     *
     * @return MessageSubscriber
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return MessageSubscriber
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     *
     * @return MessageSubscriber
     */
    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return MessageListSubscription[]
     */
    public function getListSubscriptions(): Collection|array|null
    {
        return $this->listSubscriptions;
    }

    /**
     * @param MessageListSubscription[] $listSubscriptions
     *
     * @return MessageSubscriber
     */
    public function setListSubscriptions(Collection|array|null $listSubscriptions): self
    {
        $this->listSubscriptions = $listSubscriptions;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return MessageSubscriber
     */
    public function addListSubscription(MessageListSubscription $listSubscription): self
    {
        if (!$this->listSubscriptions->contains($listSubscription)) {
            $this->listSubscriptions[] = $listSubscription;

            $listSubscription->setList($this);
        }

        return $this;
    }

    /**
     * Remove send list.
     */
    public function removeListSubscription(MessageListSubscription $listSubscription): void
    {
        $this->listSubscriptions->removeElement($listSubscription);
    }

    /**
     * @return array|MessageList[]
     */
    public function getLists(): Collection|array|null
    {
        return $this->lists;
    }

    /**
     * @param array|MessageList[] $lists
     *
     * @return MessageSubscriber
     */
    public function setLists(Collection|array|null $lists): self
    {
        $this->lists = $lists;

        return $this;
    }
}
