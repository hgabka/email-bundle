<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

/**
 * Email log.
 *
 * @ORM\Table(name="hg_email_email_queue")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailQueueRepository")
 */
class EmailQueue extends AbstractQueue
{
    use TimestampableEntity;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_from", type="text", nullable=true)
     */
    protected $from;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_to", type="text", nullable=true)
     */
    protected $to;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_cc", type="text", nullable=true)
     */
    protected $cc;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_bcc", type="text", nullable=true)
     */
    protected $bcc;

    /**
     * @var null|string
     *
     * @ORM\Column(name="mail_return_path", type="text", nullable=true)
     */
    protected $returnPath;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    protected $subject;

    /**
     * @var null|array
     *
     * @ORM\Column(name="message_embeds", type="array", nullable=true)
     */
    protected $embeds;

    /**
     * @var null|array
     *
     * @ORM\Column(name="mail_headers", type="array", nullable=true)
     */
    protected $headers;

    /**
     * @var string
     *
     * @ORM\Column(name="content_text", type="text", nullable=true)
     */
    protected $contentText;

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text", nullable=true)
     */
    protected $contentHtml;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_at", type="datetime", nullable=true)
     */
    protected $sendAt;

    /**
     * @var EmailCampaign
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailCampaign", cascade={"persist"})
     * @ORM\JoinColumn(name="email_campaign_id", nullable=true, referencedColumnName="id", onDelete="CASCADE")
     */
    protected $campaign;

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string $from
     *
     * @return EmailQueue
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return EmailQueue
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param string $cc
     *
     * @return EmailQueue
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param string $bcc
     *
     * @return EmailQueue
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return EmailQueue
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentText()
    {
        return $this->contentText;
    }

    /**
     * @param string $contentText
     *
     * @return EmailQueue
     */
    public function setContentText($contentText)
    {
        $this->contentText = $contentText;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentHtml()
    {
        return $this->contentHtml;
    }

    /**
     * @param string $contentHtml
     *
     * @return EmailQueue
     */
    public function setContentHtml($contentHtml)
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * @param \DateTime $sendAt
     *
     * @return EmailQueue
     */
    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;

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
     * @return EmailQueue
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function isForEmail($email)
    {
        $to = $this->getTo();
        if (empty($to)) {
            return false;
        }

        $to = unserialize($to);
        if (!\is_array($to)) {
            return $to === $email;
        }

        foreach ($to as $mail => $name) {
            if (!\is_array($name) && $mail === $email) {
                return true;
            } elseif (\is_array($name)) {
                if (\in_array($email, $name, true) || \array_key_exists($email, $name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return null|array
     */
    public function getEmbeds(): ?array
    {
        return $this->embeds;
    }

    /**
     * @param null|array $embeds
     *
     * @return EmailQueue
     */
    public function setEmbeds(?array $embeds): self
    {
        $this->embeds = $embeds;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getReturnPath(): ?string
    {
        return $this->returnPath;
    }

    /**
     * @param null|string $returnPath
     *
     * @return EmailQueue
     */
    public function setReturnPath(?string $returnPath): self
    {
        $this->returnPath = $returnPath;

        return $this;
    }

    /**
     * @return null|array
     */
    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    /**
     * @param null|array $headers
     *
     * @return EmailQueue
     */
    public function setHeaders(?array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }
}
