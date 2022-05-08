<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\EmailCampaignMessageRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_campaign_message")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailCampaignMessageRepository")
 */
#[ORM\Table(name: 'hg_email_email_campaign_message')]
#[ORM\Entity(repositoryClass: EmailCampaignMessageRepository::class)]
class EmailCampaignMessage
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
     * @var EmailCampaign
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailCampaign", inversedBy="messages", cascade={"persist"})
     * @ORM\JoinColumn(name="email_campaign_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: EmailCampaign::class, inversedBy: 'messages', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_campaign_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?EmailCampaign $campaign = null;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplate", cascade={"persist"})
     * @ORM\JoinColumn(name="email_template_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: EmailTemplate::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_template_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: true)]
    protected ?EmailTemplate $template = null;

    /**
     * @var int
     *
     * @ORM\Column(name="send_after", type="integer")
     */
    #[ORM\Column(name: 'send_after', type: 'integer')]
    protected ?int $sendAfter = 0;

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
     * @return EmailCampaignMessage
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return EmailCampaign
     */
    public function getCampaign(): ?EmailCampaign
    {
        return $this->campaign;
    }

    /**
     * @param EmailCampaign $campaign
     *
     * @return EmailCampaignMessage
     */
    public function setCampaign(?EmailCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return EmailTemplate
     */
    public function getTemplate(): ?EmailTemplate
    {
        return $this->template;
    }

    /**
     * @param EmailTemplate $template
     *
     * @return EmailCampaignMessage
     */
    public function setTemplate(?EmailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return int
     */
    public function getSendAfter(): ?int
    {
        return $this->sendAfter;
    }

    /**
     * @param int $sendAfter
     *
     * @return EmailCampaignMessage
     */
    public function setSendAfter(?int $sendAfter)
    {
        $this->sendAfter = $sendAfter;

        return $this;
    }
}
