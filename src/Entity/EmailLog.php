<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Helper\MailHelper;
use Hgabka\EmailBundle\Repository\EmailLogRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Mime\Email;

#[ORM\Table(name: 'hg_email_email_log')]
#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
class EmailLog
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'mail_from', type: 'string', length: 255, nullable: true)]
    protected ?string $from = null;

    #[ORM\Column(name: 'mail_to', type: 'string', length: 255, nullable: true)]
    protected ?string  $to = null;

    #[ORM\Column(name: 'mail_cc', type: 'string', length: 255, nullable: true)]
    protected ?string $cc = null;

    #[ORM\Column(name: 'mail_bcc', type: 'string', length: 255, nullable: true)]
    protected ?string $bcc = null;

    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $textBody = null;

    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $htmlBody = null;

    #[ORM\Column(name: 'attachment', type: 'text', nullable: true)]
    protected ?string $attachment = null;

    #[ORM\Column(name: 'mime', type: 'string', length: 255, nullable: true)]
    protected ?string $mime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

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

    public function getTextBody(): ?string
    {
        return $this->textBody;
    }

    public function setTextBody(?string $textBody): self
    {
        $this->textBody = $textBody;

        return $this;
    }

    public function getHtmlBody(): ?string
    {
        return $this->htmlBody;
    }

    public function setHtmlBody(?string $htmlBody): self
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    public function getMime(): ?string
    {
        return $this->mime;
    }

    public function setMime(?string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    public function fromMessage(Email $message, MailHelper $mailHelper): void
    {
        $this->setFrom($mailHelper->displayAddresses($message->getFrom()));
        $this->setTo($mailHelper->displayAddresses($message->getTo()));
        $this->setSubject($message->getSubject());
        $this->setTextBody($message->getTextBody());
        $this->setCc($mailHelper->displayAddresses($message->getCc()));
        $this->setBcc($mailHelper->displayAddresses($message->getBcc()));
        $this->setHtmlBody($message->getHtmlBody());

        foreach ($message->getAttachments() as $attachmentPart) {
            $attachment = (string) $this->getAttachment();
            $this->setAttachment((empty($attachment) ? '' : ($attachment . ',')) . $attachmentPart->asDebugString());
        }
    }
}
