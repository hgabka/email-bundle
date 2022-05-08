<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;

/**
 * @ORM\Table(name="hg_email_email_layout_translation")
 * @ORM\Entity
 */
#[ORM\Table(name: 'hg_email_email_layout_translation')]
#[ORM\Entity]
class EmailLayoutTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\EmailLayout")
     */
    #[Hgabka\Translatable(targetEntity: EmailLayout::class)]
    protected ?TranslatableInterface $translatable;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    protected ?string $name;

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text")
     */
    #[ORM\Column(name: 'content_html', type: 'text')]
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
     * @return EmailLayoutTranslation
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

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
     * @return EmailLayoutTranslation
     */
    public function setContentHtml(?string $contentHtml): self
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return EmailLayoutTranslation
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
