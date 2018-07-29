<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\KunstmaanExtensionBundle\Traits\TimestampableEntity;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;

/**
 * Email log.
 *
 * @ORM\Table(name="hg_kuma_email_email_log")
 * @ORM\Entity(repositoryClass="Hgabka\KunstmaanEmailBundle\Repository\EmailLogRepository")
 */
class EmailLog extends AbstractEntity
{
    use TimestampableEntity;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_from", type="string", length=255, nullable=true)
     */
    protected $from;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_to", type="string", length=255, nullable=true)
     */
    protected $to;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_cc", type="string", length=255, nullable=true)
     */
    protected $cc;

    /**
     * @var string
     *
     * @ORM\Column(name="mail_bcc", type="string", length=255, nullable=true)
     */
    protected $bcc;

    /**
     * @var string
     *
     * @ORM\Column(name="content_text", type="text", nullable=true)
     */
    protected $textBody;

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text", nullable=true)
     */
    protected $htmlBody;

    /**
     * @var string
     *
     * @ORM\Column(name="attachment", type="string", length=255, nullable=true)
     */
    protected $attachment;

    /**
     * @var string
     *
     * @ORM\Column(name="mime", type="string", length=255, nullable=true)
     */
    protected $mime;

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
     * @return EmailLog
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

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
     * @return EmailLog
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
     * @return EmailLog
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
     * @return EmailLog
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
     * @return EmailLog
     */
    public function setBcc($bcc)
    {
        $this->bcc = $bcc;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->textBody;
    }

    /**
     * @param string $textBody
     *
     * @return EmailLog
     */
    public function setTextBody($textBody)
    {
        $this->textBody = $textBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->htmlBody;
    }

    /**
     * @param string $htmlBody
     *
     * @return EmailLog
     */
    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;

        return $this;
    }

    /**
     * @return string
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * @param string $attachment
     *
     * @return EmailLog
     */
    public function setAttachment($attachment)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * @return string
     */
    public function getMime()
    {
        return $this->mime;
    }

    /**
     * @param string $mime
     *
     * @return EmailLog
     */
    public function setMime($mime)
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * Populate fields with $message data.
     *
     * @param Swift_Message $message
     */
    public function fromMessage(\Swift_Message $message)
    {
        $this->setFrom($this->addressToString($message->getFrom()));
        $this->setTo($this->addressToString($message->getTo()));
        $this->setSubject($message->getSubject());
        $this->setTextBody($message->getBody());
        $this->setCc($this->addressToString($message->getCc()));
        $this->setBcc($this->addressToString($message->getBcc()));

        $children = $message->getChildren();
        foreach ($children as $child) {
            if ('text/html' === $child->getContentType()) {
                $this->setHtmlBody($child->getBody());
            } elseif ($child instanceof Swift_Attachment) {
                $this->setAttachment($child->getFilename());
            }
        }
        $this->setMime($message->getContentType());
    }

    /**
     * Convert address or addresses to string.
     *
     * @param array $addr
     *
     * @return string
     */
    protected function addressToString($addr)
    {
        if (empty($addr)) {
            return '';
        }

        if (is_string($addr)) {
            return $addr;
        }

        $str = '';
        foreach ($addr as $key => $val) {
            $to = trim($val);
            if (empty($to)) {
                $str .= ($key.', ');
            } else {
                $str .= sprintf('%s <%s>, ', $val, $key);
            }
        }

        return trim(substr($str, 0, -2));
    }
}
