<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_campaign_message")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailCampaignMessageRepository")
 */
class EmailCampaignMessage
{
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var EmailCampaign
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailCampaign", inversedBy="messages", cascade={"persist"})
     * @ORM\JoinColumn(name="email_campaign_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $campaign;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplate", cascade={"persist"})
     * @ORM\JoinColumn(name="email_template_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $template;

    /**
     * @var int
     *
     * @ORM\Column(name="send_after", type="integer")
     */
    protected $sendAfter = 0;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return EmailCampaignMessage
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return EmailCampaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param EmailCampaign $campaign
     *
     * @return EmailCampaignMessage
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param EmailTemplate $template
     *
     * @return EmailCampaignMessage
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return int
     */
    public function getSendAfter()
    {
        return $this->sendAfter;
    }

    /**
     * @param int $sendAfter
     *
     * @return EmailCampaignMessage
     */
    public function setSendAfter($sendAfter)
    {
        $this->sendAfter = $sendAfter;

        return $this;
    }
}
