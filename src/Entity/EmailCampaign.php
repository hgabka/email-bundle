<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\EmailCampaignRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'hg_email_email_campaign')]
#[ORM\Entity(repositoryClass: EmailCampaignRepository::class)]
class EmailCampaign
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MessageList::class, inversedBy: 'campaigns', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'message_list_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?MessageList $list = null;

    #[ORM\OneToMany(targetEntity: EmailCampaignMessage::class, cascade: ['all'], mappedBy: 'campaign', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $messages = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255, nullable: true)]
    protected ?string $name = null;

    #[ORM\Column(name: 'from_name', type: 'string', length: 255, nullable: true)]
    protected ?string $fromName = null;

    #[ORM\Column(name: 'from_email', type: 'string', length: 255, nullable: true)]
    protected ?string $fromEmail = null;

    #[ORM\Column(name: 'is_active', type: 'boolean')]
    protected ?bool $isActive = true;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->messages = new ArrayCollection();
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

    public function getList(): ?MessageList
    {
        return $this->list;
    }

    public function setList(?MessageList $list): self
    {
        $this->list = $list;

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

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): self
    {
        $this->fromName = $fromName;

        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(?string $fromEmail): self
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    public function getisActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getMessages(): Collection|array|null
    {
        return $this->messages;
    }

    public function setMessages(Collection|array|null $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    public function addMessage(EmailCampaignMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;

            $message->setCampaign($this);
        }

        return $this;
    }

    public function removeMessage(EmailCampaignMessage $message): self
    {
        $this->messages->removeElement($message);

        return $this;
    }
}
