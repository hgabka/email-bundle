<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\EmailBundle\Repository\MessageListRepository;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MessageList.
 *
 * @ORM\Table(name="hg_email_message_list")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageListRepository")
 */
#[ORM\Table(name: 'hg_email_message_list')]
#[ORM\Entity(repositoryClass: MessageListRepository::class)]
class MessageList implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[Hgabka\Translations(targetEntity: MessageListTranslation::class)]
    protected Collection|array|null $translations = null;

    #[ORM\OneToMany(targetEntity: MessageListSubscription::class, cascade: ['all'], mappedBy: 'list', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $listSubscriptions = null;

    #[ORM\OneToMany(targetEntity: MessageSendList::class, cascade: ['all'], mappedBy: 'list', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $sendLists = null;

    #[ORM\OneToMany(targetEntity: EmailCampaign::class, cascade: ['all'], mappedBy: 'list', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $campaigns = null;

    #[ORM\Column(name: 'is_default', type: 'boolean')]
    protected ?bool $isDefault = false;

    #[ORM\Column(name: 'is_public', type: 'boolean')]
    protected ?bool $isPublic = true;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->listSubscriptions = new ArrayCollection();
        $this->sendLists = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName() ?: '';
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
     * @return MessageList
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param null|mixed $locale
     *
     * @return mixed
     */
    public function getName(?string $locale = null): ?string
    {
        return $this->translate($locale)->getName();
    }

    /**
     * @param mixed      $name
     * @param null|mixed $locale
     *
     * @return MessageList
     */
    public function setName(?string $name, ?string $locale = null): self
    {
        $this->translate($locale)->setName($name);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisDefault(): ?bool
    {
        return $this->isDefault;
    }

    /**
     * @param mixed $isDefault
     *
     * @return MessageList
     */
    public function setIsDefault(?bool $isDefault): self
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getIsPublic(): ?bool
    {
        return $this->isPublic;
    }

    /**
     * @param mixed $isPublic
     *
     * @return MessageList
     */
    public function setIsPublic(?bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return MessageSendList[]
     */
    public function getMessageSendLists(): Collection|array|null
    {
        return $this->sendLists;
    }

    /**
     * @param MessageSendList[] $sendLists
     *
     * @return MessageList
     */
    public function setSendLists(Collection|array|null $sendLists): self
    {
        $this->sendLists = $sendLists;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return MessageList
     */
    public function addSendList(MessageSendList $sendList): self
    {
        if (!$this->sendLists->contains($sendList)) {
            $this->sendLists[] = $sendList;

            $sendList->setList($this);
        }

        return $this;
    }

    /**
     * Remove send list.
     */
    public function removeSendList(MessageSendList $sendList): self
    {
        $this->sendLists->removeElement($sendList);

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
     * @return MessageList
     */
    public function setListSubscriptions(Collection|array|null $listSubscriptions): self
    {
        $this->listSubscriptions = $listSubscriptions;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return MessageList
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
    public function removeListSubscription(MessageListSubscription $listSubscription): self
    {
        $this->listSubscriptions->removeElement($listSubscription);

        return $this;
    }
}
