<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\EmailBundle\Repository\AttachmentRepository;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Attachment.
 *
 * @ORM\Table(name="hg_email_attachment")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\AttachmentRepository")
 */
#[ORM\Table(name: 'hg_email_attachment')]
#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
class Attachment
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
    protected ?int $id = null;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="content_type", nullable=true)
     */
    #[ORM\Column(type: 'string', name: 'content_type', nullable: true)]
    protected ?string $contentType = null;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    #[ORM\Column(type: 'string', name: 'type', length: 255)]
    protected ?string $type = null;

    /**
     * @var int
     *
     * @ORM\Column(name="owner_id", type="bigint", nullable=true)
     */
    #[ORM\Column(type: 'bigint', name: 'owner_id', nullable: true)]
    protected ?int  $ownerId = null;

    /**
     * @var Media
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\MediaBundle\Entity\Media", cascade={"persist"})
     * @ORM\JoinColumn(name="media_id", referencedColumnName="id")
     * @Assert\NotNull()
     */
    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id')]
    #[Assert\NotNull]
    protected ?Media $media = null;

    /**
     * @var EmailTemplateTranslation
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplateTranslation", inversedBy="attachments")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: EmailTemplateTranslation::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id')]
    protected ?EmailTemplateTranslation $template = null;

    /**
     * @var MessageTranslation
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageTranslation", inversedBy="attachments")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     */
    #[ORM\ManyToOne(targetEntity: MessageTranslation::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id')]
    protected ?MessageTranslation $message = null;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", length=2, nullable=true)
     */
    #[ORM\Column(name: 'locale', type: 'string', length: 2, nullable: true)]
    protected ?string $locale = null;

    /**
     * @var string
     *
     * @ORM\Column(name="filename", type="string", length=512, nullable=true)
     */
    #[ORM\Column(name: 'filename', type: 'string', length: 512, nullable: true)]
    protected ?string $filename = null;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="hg_utils_longblob", nullable=true)
     */
    #[ORM\Column(name: 'content', type: 'hg_utils_longblob', nullable: true)]
    protected $content;

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
     * @return Attachment
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Attachment
     */
    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    /**
     * @param int $ownerId
     *
     * @return Attachment
     */
    public function setOwnerId(?int $ownerId): self
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    /**
     * @return Media
     */
    public function getMedia(): ?Media
    {
        return $this->media;
    }

    /**
     * @param Media $media
     *
     * @return Attachment
     */
    public function setMedia(?Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return Attachment
     */
    public function setLocale(?string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return Attachment
     */
    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Attachment
     */
    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     *
     * @return Attachment
     */
    public function setContentType(?string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }
}
