<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
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

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\Column(type: Types::STRING, name: 'content_type', nullable: true)]
    protected ?string $contentType = null;

    #[ORM\Column(type: Types::STRING, name: 'type', length: 255)]
    protected ?string $type = null;

    #[ORM\Column(type: Types::INTEGER, name: 'owner_id', nullable: true)]
    protected ?int  $ownerId = null;

    #[ORM\ManyToOne(targetEntity: Media::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id')]
    #[Assert\NotNull]
    protected ?Media $media = null;

    #[ORM\ManyToOne(targetEntity: EmailTemplateTranslation::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id')]
    protected ?EmailTemplateTranslation $template = null;

    #[ORM\ManyToOne(targetEntity: MessageTranslation::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id')]
    protected ?MessageTranslation $message = null;

    #[ORM\Column(name: 'locale', type: Types::STRING, length: 2, nullable: true)]
    protected ?string $locale = null;

    #[ORM\Column(name: 'filename', type: Types::STRING, length: 512, nullable: true)]
    protected ?string $filename = null;

    #[ORM\Column(name: 'content', type: 'hg_utils_longblob', nullable: true)]
    protected $content;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function setOwnerId(?int $ownerId): self
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    public function getMedia(): ?Media
    {
        return $this->media;
    }

    public function setMedia(?Media $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }
}
