<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="hg_email_email_template_translation")
 * @ORM\Entity
 */
#[ORM\Table(name: 'hg_email_email_template_translation')]
#[ORM\Entity]
class EmailTemplateTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\Attachment", mappedBy="template")
     *
     * @var ArrayCollection|Attachment[]
     */
    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'template')]
    protected Collection $attachments;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplate")
     */
    #[Hgabka\Translatable(targetEntity: EmailTemplate::class)]
    protected ?TranslatableInterface $translatable;

    /**
     * @ORM\Column(name="from_name", type="text", nullable=true)
     */
    #[ORM\Column(name: 'from_name', type: 'text', nullable: true)]
    protected ?string $fromName;

    /**
     * @ORM\Column(name="from_email", type="text", nullable=true)
     */
    #[ORM\Column(name: 'from_email', type: 'text', nullable: true)]
    protected ?string $fromEmail;

    /**
     * @ORM\Column(name="comment", type="text")
     * @Assert\NotBlank()
     */
    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    protected ?string $comment;

    /**
     * @ORM\Column(name="subject", type="string", length=255)
     */
    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    protected ?string $subject;

    /**
     * @ORM\Column(name="content_text", type="text", nullable=true)
     */
    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $contentText = '';

    /**
     * @ORM\Column(name="content_html", type="text", nullable=true)
     */
    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $contentHtml = '';

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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return EmailTemplateTranslation
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    /**
     * @param mixed $fromName
     *
     * @return EmailTemplateTranslation
     */
    public function setFromName(?string $fromName): self
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    /**
     * @param mixed $fromEmail
     *
     * @return EmailTemplateTranslation
     */
    public function setFromEmail(?string $fromEmail): self
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     *
     * @return EmailTemplateTranslation
     */
    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentText(): ?string
    {
        return $this->contentText;
    }

    /**
     * @param string $contentText
     *
     * @return EmailTemplateTranslation
     */
    public function setContentText(?string $contentText): self
    {
        $this->contentText = $contentText;

        return $this;
    }

    /**
     * @return string
     */
    public function getContentHtml(): ?string
    {
        return $this->contentHtml;
    }

    /**
     * @param string $contentHtml
     *
     * @return EmailTemplateTranslation
     */
    public function setContentHtml(?string $contentHtml): self
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     *
     * @return EmailTemplateTranslation
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /**
     * @param mixed $attachments
     *
     * @return EmailTemplateTranslation
     */
    public function setAttachments(Collection $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function addAttachment(Attachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }

        return $this
    }

    public function removeAttachment(Attachment $attachment): void
    {
        $this->attachments->removeElement($attachment);
    }

    /**
     * @return mixed
     */
    public function getTranslatable(): ?TranslatableInterface
    {
        return $this->translatable;
    }
}
