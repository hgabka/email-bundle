<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\EmailCampaignMessageRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

#[ORM\Table(name: 'hg_email_email_campaign_message')]
#[ORM\Entity(repositoryClass: EmailCampaignMessageRepository::class)]
class EmailCampaignMessage
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EmailCampaign::class, inversedBy: 'messages', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_campaign_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?EmailCampaign $campaign = null;

    #[ORM\ManyToOne(targetEntity: EmailTemplate::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_template_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?EmailTemplate $template = null;

    #[ORM\Column(name: 'send_after', type: 'integer')]
    protected ?int $sendAfter = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getCampaign(): ?EmailCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(?EmailCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EmailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getSendAfter(): ?int
    {
        return $this->sendAfter;
    }

    public function setSendAfter(?int $sendAfter)
    {
        $this->sendAfter = $sendAfter;

        return $this;
    }
}
