<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\Entity\TranslationTrait;
use Prezent\Doctrine\Translatable\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="hg_kuma_email_message_translation")
 * @ORM\Entity
 */
class MessageTranslation extends AbstractEntity implements TranslationInterface
{
    use TranslationTrait;

    /** @var ArrayCollection |Attachment[] */
    protected $attachments;

    /**
     * @Prezent\Translatable(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\Message")
     */
    protected $translatable;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    protected $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="content_text", type="text", nullable=true)
     */
    protected $contentText = '';

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text", nullable=true)
     */
    protected $contentHtml = '';

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
     * @return MessageTranslation
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
     * @return MessageTranslation
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
     * @return MessageTranslation
     */
    public function setContentHtml($contentHtml)
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * @param mixed $attachments
     *
     * @return MessageTranslation
     */
    public function setAttachments($attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }
}
