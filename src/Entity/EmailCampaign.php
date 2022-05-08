<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\EmailCampaignRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_campaign")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailCampaignRepository")
 */
#[ORM\Table(name: 'hg_email_email_campaign')]
#[ORM\Entity(repositoryClass: EmailCampaignRepository::class)]
class EmailCampaign
{
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
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageList", inversedBy="campaigns", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: MessageList::class, inversedBy: 'campaigns', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_list_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?MessageList $list = null;

    /**
     * @var ArrayCollection|EmailCampaignMessage[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\EmailCampaignMessage", cascade={"all"}, mappedBy="campaign", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    #[ORM\OneToMany(targetEntity: EmailCampaignMessage::class, cascade: ['all'], mappedBy: 'campaign', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $messages = null;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * @var string
     *
     * @ORM\Column(name="from_name", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'from_name', type: 'string', length: 255, nullable: true)]
    protected ?string $fromName = null;

    /**
     * @var string
     *
     * @ORM\Column(name="from_email", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'from_email', type: 'string', length: 255, nullable: true)]
    protected ?string $fromEmail = null;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    #[ORM\Column(name: 'is_active', type: 'boolean')]
    protected ?bool $isActive = true;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->messages = new ArrayCollection();
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
     * @return EmailCampaign
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
     * @return EmailCampaign
     */
    public function setList(?MessageList $list): self
    {
        $this->list = $list;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return EmailCampaign
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     *
     * @return EmailCampaign
     */
    public function setFromName(?string $fromName): self
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     *
     * @return EmailCampaign
     */
    public function setFromEmail(?string $fromEmail): self
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * @param mixed $isActive
     *
     * @return EmailCampaign
     */
    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return EmailCampaignMessage[]
     */
    public function getMessages(): Collection|array|null
    {
        return $this->messages;
    }

    /**
     * @param EmailCampaignMessage[] $messages
     *
     * @return EmailCampaign
     */
    public function setMessages(Collection|array|null $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Add message.
     *
     * @return EmailCampaign
     */
    public function addMessage(EmailCampaignMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;

            $message->setCampaign($this);
        }

        return $this;
    }

    /**
     * Remove message.
     */
    public function removeMessage(EmailCampaignMessage $message): void
    {
        $this->messages->removeElement($message);
    }
}
