<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;

#[ORM\Table(name: 'hg_email_email_template_translation')]
#[ORM\Entity]
class EmailTemplateTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'template')]
    protected Collection|array|null $attachments = null;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplate")
     */
    #[Hgabka\Translatable(targetEntity: EmailTemplate::class)]
    protected ?TranslatableInterface $translatable = null;

    #[ORM\Column(name: 'from_name', type: 'text', nullable: true)]
    protected ?string $fromName = null;

    #[ORM\Column(name: 'from_email', type: 'text', nullable: true)]
    protected ?string $fromEmail = null;

    #[ORM\Column(name: 'comment', type: 'text')]
    protected ?string $comment = null;

    #[ORM\Column(name: 'subject', type: 'string', length: 255)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $contentText = '';

    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $contentHtml = '';

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function setFromName(?string $fromName): self
    {
        $this->fromName = $fromName;

        return $this;
    }

    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    public function setFromEmail(?string $fromEmail): self
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getAttachments(): Collection|array|null
    {
        return $this->attachments;
    }

    public function setAttachments(Collection|array|null $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    public function addAttachment(Attachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
        }

        return $this;
    }

    public function removeAttachment(Attachment $attachment): self
    {
        $this->attachments->removeElement($attachment);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTranslatable(): ?TranslatableInterface
    {
        return $this->translatable;
    }
}
