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
 * @ORM\Table(name="hg_email_message_translation")
 * @ORM\Entity
 */
#[ORM\Table(name: 'hg_email_message_translation')]
#[ORM\Entity]
class MessageTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\Attachment", mappedBy="message")
     *
     * @var ArrayCollection|Attachment[]
     */
    #[ORM\OneToMany(targetEntity: Attachment::class, mappedBy: 'message')]
    protected Collection|array|null $attachments = null;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\Message")
     */
    #[Hgabka\Translatable(targetEntity: Message::class)]
    protected ?TranslatableInterface $translatable = null;

    /**
     * @ORM\Column(name="name", type="string")
     * @Assert\NotBlank()
     */
    #[ORM\Column(name: 'name', type: 'string')]
    #[Assert\NotBlank]
    protected ?string $name = null;

    /**
     * @ORM\Column(name="from_name", type="string", length=255, nullable=true)
     */
    #[ORM\Column(name: 'from_name', type: 'string', length: 255, nullable: true)]
    protected ?string $fromName = null;

    /**
     * @ORM\Column(name="from_email", type="string", length=255, nullable=true)
     * @Assert\Email()
     */
    #[ORM\Column(name: 'from_email', type: 'string', length: 255, nullable: true)]
    #[Assert\Email]
    protected ?string $fromEmail = null;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255, nullable=true)
     * @Assert\NotBlank()
     */
    #[ORM\Column(name: 'subject', type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank]
    protected ?string $subject = null;

    /**
     * @var string
     *
     * @ORM\Column(name="content_text", type="text", nullable=true)
     */
    #[ORM\Column(name: 'content_text', type: 'text', nullable: true)]
    protected ?string $contentText = '';

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text", nullable=true)
     */
    #[ORM\Column(name: 'content_html', type: 'text', nullable: true)]
    protected ?string $contentHtml = '';

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
     * @return MessageTranslation
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return MessageTranslation
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     *
     * @return MessageTranslation
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
     * @return MessageTranslation
     */
    public function setContentText(?string $contentText): self
    {
        $this->contentHtml = $contentText;

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
     * @return MessageTranslation
     */
    public function setContentHtml(?string $contentHtml): string
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    /**
     * @param mixed $attachments
     *
     * @return MessageTranslation
     */
    public function setAttachments(Collection $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     *
     * @return Message
     */
    public function setFromName(?string $fromName): self
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromEmail(): ?string
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     *
     * @return Message
     */
    public function setFromEmail(?string $fromEmail): self
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }
}
