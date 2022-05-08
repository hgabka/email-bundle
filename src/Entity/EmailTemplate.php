<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\EmailBundle\Repository\EmailTemplateRepository;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;

#[ORM\Table(name: 'hg_email_email_template')]
#[ORM\Entity(repositoryClass: EmailTemplateRepository::class)]
class EmailTemplate implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    /**
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplateTranslation")
     */
    #[Hgabka\Translations(targetEntity: EmailTemplateTranslation::class)]
    protected Collection|array|null $translations = null;

    #[ORM\Column(name: 'type', type: 'text', nullable: true)]
    protected ?string $type = null;

    #[ORM\ManyToOne(targetEntity: EmailLayout::class, inversedBy: 'templates', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'email_layout_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?EmailLayout $layout = null;

    #[ORM\Column(name: 'to_data', type: 'array', nullable: true)]
    protected ?array $toData = null;

    #[ORM\Column(name: 'cc_data', type: 'array', nullable: true)]
    protected ?array $ccData = null;

    #[ORM\Column(name: 'bcc_data', type: 'array', nullable: true)]
    protected ?array $bccData = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'Email sablon';
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

    public function getComment(?string $locale = null): ?string
    {
        return $this->translate($locale)->getComment();
    }

    public function setComment(?string $comment, ?string $locale = null): self
    {
        $this->translate($locale)->setComment($comment);

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

    public function getLayout(): ?EmailLayout
    {
        return $this->layout;
    }

    public function setLayout(?EmailLayout $layout): self
    {
        $this->layout = $layout;

        return $this;
    }

    public function isSystem(): bool
    {
        return !empty($this->type);
    }

    public static function getTranslationEntityClass(): string
    {
        return EmailTemplateTranslation::class;
    }

    public function getFromName($lang = null)
    {
        return $this->translate($lang)->getFromName();
    }

    public function getFromEmail($lang = null)
    {
        return $this->translate($lang)->getFromEmail();
    }

    /**
     * @return mixed
     */
    public function getToData()
    {
        return $this->toData;
    }

    /**
     * @param mixed $toData
     *
     * @return EmailTemplate
     */
    public function setToData($toData)
    {
        $this->toData = $toData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCcData()
    {
        return $this->ccData;
    }

    /**
     * @param mixed $ccData
     *
     * @return EmailTemplate
     */
    public function setCcData($ccData)
    {
        $this->ccData = $ccData;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBccData()
    {
        return $this->bccData;
    }

    /**
     * @param mixed $bccData
     *
     * @return EmailTemplate
     */
    public function setBccData($bccData)
    {
        $this->bccData = $bccData;

        return $this;
    }
}
