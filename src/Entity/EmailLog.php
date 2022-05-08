<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Helper\MailHelper;
use Hgabka\EmailBundle\Repository\EmailLogRepository;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Mime\Email;

/**
 * Email log.
 *
 * @ORM\Table(name="hg_email_email_log")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailLogRepository")
 */
#[ORM\Table(name: 'hg_email_email_log')]
#[ORM\Entity(repositoryClass: EmailLogRepository::class)]
class EmailLog
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
    protected ?int $id;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    protected ?string $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_from", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'mail_from', type: 'string', length: 255, nullable: true)]
    protected ?string $from;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_to", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'mail_to', type: 'string', length: 255, nullable: true)]
    protected ?string  $to;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_cc", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'mail_cc', type: 'string', length: 255, nullable: true)]
    protected ?string $cc;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_bcc", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'mail_bcc', type: 'string', length: 255, nullable: true)]
    protected ?string $bcc;

    /**
     * @var string
     *
     * @ORM\Column(name="content_text", type="text", nullable=true)
     */
    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $textBody;

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text", nullable=true)
     */
    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $htmlBody;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="text", nullable=true)
     */
    #[ORM\Column(name: 'attachment', type: 'text', nullable: true)]
    protected ?string $attachment;

    /**
     * @var string
     *
     * @ORM\Column(name="mime", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'mime', type: 'string', length: 255, nullable: true)]
    protected ?string $mime;

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
     * @return EmailLog
     */
    public function setId(?int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return EmailLog
     */
    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string $from
     *
     * @return EmailLog
     */
    public function setFrom(?string $from): self
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return EmailLog
     */
    public function setTo(?string $to): self
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function getCc(): ?string
    {
        return $this->cc;
    }

    /**
     * @param string $cc
     *
     * @return EmailLog
     */
    public function setCc(?string $cc): self
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * @return string
     */
    public function getBcc(): ?string
    {
        return $this->bcc;
    }

    /**
     * @param string $bcc
     *
     * @return EmailLog
     */
    public function setBcc(?string $bcc): self
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody(): ?string
    {
        return $this->textBody;
    }

    /**
     * @param string $textBody
     *
     * @return EmailLog
     */
    public function setTextBody(?string $textBody): self
    {
        $this->textBody = $textBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody(): ?string
    {
        return $this->htmlBody;
    }

    /**
     * @param string $htmlBody
     *
     * @return EmailLog
     */
    public function setHtmlBody(?string $htmlBody): self
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttachment(): ?string
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     *
     * @return EmailLog
     */
    public function setAttachment(?string $attachment): self
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return string
     */
    public function getMime(): ?string
    {
        return $this->mime;
    }

    /**
     * @param string $mime
     *
     * @return EmailLog
     */
    public function setMime(?string $mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * Populate fields with $message data.
     *
     * @param Email $message
     */
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
