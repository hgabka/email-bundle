<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\Entity\TranslationTrait;
use Prezent\Doctrine\Translatable\TranslationInterface;

/**
 * @ORM\Table(name="hg_kuma_email_email_template_translation")
 * @ORM\Entity
 */
class EmailTemplateTranslation extends AbstractEntity implements TranslationInterface
{
    use TranslationTrait;

    /** @var ArrayCollection |Attachment[] */
    protected $attachments;

    /**
     * @Prezent\Translatable(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\EmailTemplate")
     */
    protected $translatable;

    /**
     * @ORM\Column(name="subject", type="string", length=255)
     */
    protected $subject;

    /**
     * @ORM\Column(name="content_text", type="text")
     */
    protected $contentText = '';

    /**
     * @ORM\Column(name="content_html", type="text")
     */
    protected $contentHtml = '';

    /**
     * EmailTemplateTranslation constructor.
     */
    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     *
     * @return EmailTemplateTranslation
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
     * @return EmailTemplateTranslation
     */
    public function setContentText($contentText)
    {
        $this->contentHtml = $contentText;

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
     * @return EmailTemplateTranslation
     */
    public function setContentHtml($contentHtml)
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param mixed $attachments
     *
     * @return EmailTemplateTranslation
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function addAttachment(Attachment $attachment)
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }
    }

    public function removeAttachment(Attachment $attachment)
    {
        $this->attachments->removeElement($attachment);
    }

    /**
     * @return mixed
     */
    public function getTranslatable()
    {
        return $this->translatable;
    }
}
