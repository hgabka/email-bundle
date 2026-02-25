<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\EmailQueueRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

#[ORM\Table(name: 'hg_email_email_queue')]
#[ORM\Entity(repositoryClass: EmailQueueRepository::class)]
class EmailQueue extends AbstractQueue
{
    use TimestampableEntity;

    #[ORM\Column(name: 'mail_from', type: 'text', nullable: true)]
    protected ?string $from = null;

    #[ORM\Column(name: 'mail_to', type: 'text', nullable: true)]
    protected ?string $to = null;

    #[ORM\Column(name: 'mail_cc', type: 'text', nullable: true)]
    protected ?string $cc = null;

    #[ORM\Column(name: 'mail_bcc', type: 'text', nullable: true)]
    protected ?string $bcc = null;

    #[ORM\Column(name: 'mail_return_path', type: 'text', nullable: true)]
    protected ?string $returnPath = null;

    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'message_embeds', type: 'json', nullable: true)]
    protected ?array $embeds = null;

    #[ORM\Column(name: 'mail_headers', type: 'json', nullable: true)]
    protected ?array $headers = null;

    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $contentText = null;

    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $contentHtml = null;

    #[ORM\Column(name: 'send_at', type: 'datetime', nullable: true)]
    protected ?\DateTime $sendAt = null;

    #[ORM\ManyToOne(targetEntity: EmailCampaign::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_campaign_id', nullable: true, referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?EmailCampaign $campaign = null;

    public function getFrom(): ?string
    {
        return $this->from;
    }

    public function setFrom(?string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getTo(): ?string
    {
        return $this->to;
    }

    public function setTo(?string $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getCc(): ?string
    {
        return $this->cc;
    }

    public function setCc(?string $cc): self
    {
        $this->cc = $cc;

        return $this;
    }

    public function getBcc(): ?string
    {
        return $this->bcc;
    }

    public function setBcc(?string $bcc): self
    {
        $this->bcc = $bcc;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContentText(): ?string
    {
        return $this->contentText;
    }

    public function setContentText(?string $contentText): self
    {
        $this->contentText = $contentText;

        return $this;
    }

    public function getContentHtml(): ?string
    {
        return $this->contentHtml;
    }

    public function setContentHtml(?string $contentHtml): self
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    public function getSendAt(): ?\DateTime
    {
        return $this->sendAt;
    }

    public function setSendAt(?\DateTime $sendAt): self
    {
        $this->sendAt = $sendAt;

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

    public function isForEmail($email): bool
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

    public function getEmbeds(): ?array
    {
        return $this->embeds;
    }

    public function setEmbeds(?array $embeds): self
    {
        $this->embeds = $embeds;

        return $this;
    }

    public function getReturnPath(): ?string
    {
        return $this->returnPath;
    }

    public function setReturnPath(?string $returnPath): self
    {
        $this->returnPath = $returnPath;

        return $this;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function setHeaders(?array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }
}
