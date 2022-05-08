<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'hg_email_message_translation')]
#[ORM\Entity]
class MessageTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'message')]
    protected Collection|array|null $attachments = null;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\Message")
     */
    #[Hgabka\Translatable(targetEntity: Message::class)]
    protected ?TranslatableInterface $translatable = null;

    #[ORM\Column(name: 'name', type: 'string')]
    #[Assert\NotBlank]
    protected ?string $name = null;

    #[ORM\Column(name: 'from_name', type: 'string', length: 255, nullable: true)]
    protected ?string $fromName = null;

    #[ORM\Column(name: 'from_email', type: 'string', length: 255, nullable: true)]
    #[Assert\Email]
    protected ?string $fromEmail = null;

    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank]
    protected ?string $subject = null;

    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $contentText = '';

    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $contentHtml = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

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
        $this->contentHtml = $contentText;

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

    public function getAttachments(): Collection|array|null
    {
        return $this->attachments;
    }

    public function setAttachments(Collection|array|null $attachments): self
    {
        $this->attachments = $attachments;

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
}
