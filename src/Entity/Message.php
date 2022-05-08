<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\EmailBundle\Enum\MessageStatusEnum;
use Hgabka\EmailBundle\Repository\MessageRepository;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_message")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageRepository")
 */
#[ORM\Table(name: 'hg_email_message')]
#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

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
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\MessageTranslation")
     */
    #[Hgabka\Translations(targetEntity: MessageTranslation::class)]
    protected Collection|array|null $translations = null;

    /**
     * @var ArrayCollection|MessageSendList[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\MessageSendList", cascade={"all"}, mappedBy="message", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    #[ORM\OneToMany(targetEntity: MessageSendList::class, cascade: ['all'], mappedBy: 'message', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $sendLists = null;

    /**
     * @ORM\Column(name="to_data", type="array", nullable=true)
     */
    #[ORM\Column(name: 'to_data', type: 'array', nullable: true)]
    protected ?array $toData = null;

    /**
     * @ORM\Column(name="cc_data", type="array", nullable=true)
     */
    #[ORM\Column(name: 'cc_data', type: 'array', nullable: true)]
    protected ?array $ccData = null;

    /**
     * @ORM\Column(name="bcc_data", type="array", nullable=true)
     */
    #[ORM\Column(name: 'bcc_data', type: 'array', nullable: true)]
    protected ?array $bccData = null;

    /**
     * @var \DateTime
     * @ORM\Column(name="send_at", type="datetime", nullable=true)
     */
    #[ORM\Column(name: 'send_at', type: 'datetime', nullable: true)]
    protected ?\DateTime $sendAt = null;

    /**
     * @var \DateTime
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    #[ORM\Column(name: 'sent_at', type: 'datetime', nullable: true)]
    protected ?\DateTime $sentAt = null;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=20)
     */
    #[ORM\Column(name: 'status', type: 'string', length: 20)]
    protected ?string $status = MessageStatusEnum::STATUS_INIT;

    /**
     * @var EmailLayout
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailLayout", inversedBy="messages", cascade={"persist"})
     * @ORM\JoinColumn(name="email_layout_id", referencedColumnName="id", onDelete="SET NULL")
     */
    #[ORM\ManyToOne(targetEntity: EmailLayout::class, inversedBy: 'messages', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_layout_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?EmailLayout $layout = null;

    /**
     * @var int
     * @ORM\Column(name="sent_mail", type="integer")
     */
    #[ORM\Column(name: 'sent_mail', type: 'integer')]
    protected ?int $sentMail = 0;

    /**
     * @var int
     * @ORM\Column(name="sent_success", type="integer")
     */
    #[ORM\Column(name: 'sent_success', type: 'integer')]
    protected ?int $sentSuccess = 0;

    /**
     * @var int
     * @ORM\Column(name="sent_fail", type="integer")
     */
    #[ORM\Column(name: 'sent_fail', type: 'integer')]
    protected ?int $sentFail = 0;

    /**
     * @ORM\Column(name="is_simple", type="boolean")
     */
    #[ORM\Column(name: 'is_simple', type: 'boolean')]
    protected ?bool $isSimple = false;

    /**
     * @var string
     * @ORM\Column(name="locale", type="string", length=2, nullable=true)
     */
    #[ORM\Column(name: 'locale', type: 'string', length: 2, nullable: true)]
    protected ?string $locale = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->sendLists = new ArrayCollection();
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
     * @return Message
     */
    public function setId(?int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return EmailLayout
     */
    public function getLayout(): ?EmailLayout
    {
        return $this->layout;
    }

    /**
     * @param EmailLayout $layout
     *
     * @return Message
     */
    public function setLayout(?EmailLayout $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Message
     */
    public function setType(?string $status)
    {
        if (!\in_array($status, MessageStatusEnum::getAvailableStatuses(), true)) {
            throw new \InvalidArgumentException('Invalid type');
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSendAt(): ?\DateTime
    {
        return $this->sendAt;
    }

    /**
     * @param mixed $sendAt
     *
     * @return Message
     */
    public function setSendAt(?\DateTime $sendAt = null): self
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentMail(): ?int
    {
        return $this->sentMail;
    }

    /**
     * @param int $sentMail
     *
     * @return Message
     */
    public function setSentMail(?int $sentMail): self
    {
        $this->sentMail = $sentMail;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentSuccess(): ?int
    {
        return $this->sentSuccess;
    }

    /**
     * @param int $sentSuccess
     *
     * @return Message
     */
    public function setSentSuccess(?int $sentSuccess): self
    {
        $this->sentSuccess = $sentSuccess;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentFail(): ?int
    {
        return $this->sentFail;
    }

    /**
     * @param int $sentFail
     *
     * @return Message
     */
    public function setSentFail(?int $sentFail): self
    {
        $this->sentFail = $sentFail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisSimple(): ?bool
    {
        return $this->isSimple;
    }

    /**
     * @param mixed $isSimple
     *
     * @return Message
     */
    public function setIsSimple(?bool $isSimple): self
    {
        $this->isSimple = $isSimple;

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
     * @return Message
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return MessageSendList[]
     */
    public function getSendLists(): Collection|array|null
    {
        return $this->sendLists;
    }

    /**
     * @param MessageSendList[] $sendLists
     *
     * @return Message
     */
    public function setSendLists(Collection|array|null $sendLists): self
    {
        $this->sendLists = $sendLists;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return Message
     */
    public function addSendList(MessageSendList $sendList): self
    {
        if (!$this->sendLists->contains($sendList)) {
            $this->sendLists[] = $sendList;

            $sendList->setMessage($this);
        }

        return $this;
    }

    /**
     * Remove send list.
     */
    public function removeSendList(MessageSendList $sendList): void
    {
        $this->sendLists->removeElement($sendList);
    }

    public static function getTranslationEntityClass(): string
    {
        return MessageTranslation::class;
    }

    /**
     * @return mixed
     */
    public function getToData(): ?array
    {
        return $this->toData;
    }

    /**
     * @param mixed $toData
     *
     * @return EmailTemplate
     */
    public function setToData(?array $toData): self
    {
        $this->toData = $toData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCcData(): ?array
    {
        return $this->ccData;
    }

    /**
     * @param mixed $ccData
     *
     * @return Message
     */
    public function setCcData(?array $ccData): self
    {
        $this->ccData = $ccData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBccData(): ?array
    {
        return $this->bccData;
    }

    /**
     * @param mixed $bccData
     *
     * @return Message
     */
    public function setBccData(?array $bccData)
    {
        $this->bccData = $bccData;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSentAt(): ?\DateTime
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTime $sentAt
     *
     * @return Message
     */
    public function setSentAt(?\DateTime $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isPrepareable(): bool
    {
        return MessageStatusEnum::STATUS_INIT === $this->getStatus() || null === $this->getId();
    }

    public function isUnprepareable(): bool
    {
        return \in_array($this->getStatus(), [MessageStatusEnum::STATUS_KULDENDO, MessageStatusEnum::STATUS_FOLYAMATBAN], true);
    }

    public function isBeingSent(): bool
    {
        return \in_array($this->getStatus(), [MessageStatusEnum::STATUS_ELKULDVE, MessageStatusEnum::STATUS_FOLYAMATBAN], true);
    }

    public function isPrepared(): bool
    {
        return MessageStatusEnum::STATUS_KULDENDO === $this->getStatus();
    }

    public function getSendTime(): array
    {
        if (null === $this->sendAt) {
            return [
                'type' => 'now',
                'time' => null,
            ];
        }

        return [
                'type' => 'later',
                'time' => $this->sendAt,
            ];
    }

    public function setSendTime(?array $data): self
    {
        if (!isset($data['type']) || 'now' === $data['type']) {
            $this->setSendAt(null);
        } else {
            $this->setSendAt($data['time']);
        }

        return $this;
    }

    public function getSubject(?string $locale = null): ?string
    {
        return $this->translate($locale)->getSubject();
    }

    /**
     * @param string $status
     *
     * @return Message
     */
    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusDisplay(): string
    {
        $choices = MessageStatusEnum::getStatusTextChoices();

        return $choices[$this->getStatus()] ?? '';
    }

    public function getName(?string $locale = null): ?string
    {
        return $this->translate($locale)->getName();
    }

    public function getFromName(?string $locale = null): ?string
    {
        return $this->translate($locale)->getFromName();
    }

    public function getFromEmail(?string $locale = null): ?string
    {
        return $this->translate($locale)->getFromEmail();
    }
}
