<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="hg_email_message_translation")
 * @ORM\Entity
 */
class MessageTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\Attachment", mappedBy="message")
     *
     * @var ArrayCollection|Attachment[]
     */
    protected $attachments;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\Message")
     */
    protected $translatable;

    /**
     * @ORM\Column(name="name", type="string")
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @ORM\Column(name="from_name", type="string", length=255, nullable=true)
     */
    protected $fromName;

    /**
     * @ORM\Column(name="from_email", type="string", length=255, nullable=true)
     * @Assert\Email()
     */
    protected $fromEmail;

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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return MessageTranslation
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return MessageTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

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

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     *
     * @return Message
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     *
     * @return Message
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }
}
