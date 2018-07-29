<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\KunstmaanExtensionBundle\Entity\TranslatableTrait;
use Hgabka\KunstmaanExtensionBundle\Traits\TimestampableEntity;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\TranslatableInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_kuma_email_email_template")
 * @ORM\Entity(repositoryClass="Hgabka\KunstmaanEmailBundle\Repository\EmailTemplateRepository")
 */
class EmailTemplate extends AbstractEntity implements TranslatableInterface
{
    use TranslatableTrait;
    use TimestampableEntity;

    /**
     * @Prezent\Translations(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\EmailTemplateTranslation")
     */
    protected $translations;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $name;

    /**
     * @ORM\Column(name="slug", type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $slug;

    /**
     * @ORM\Column(name="comment", type="text")
     * @Assert\NotBlank()
     */
    protected $comment;

    /**
     * @ORM\Column(name="is_system", type="boolean")
     */
    protected $isSystem = false;

    /**
     * @var EmailLayout
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\EmailLayout", inversedBy="templates", cascade={"persist"})
     * @ORM\JoinColumn(name="email_layout_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $layout;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return EmailTemplate
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $comment
     *
     * @return EmailTemplate
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return EmailTemplate
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return EmailLayout
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param EmailLayout $layout
     *
     * @return EmailTemplate
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisSystem()
    {
        return $this->isSystem;
    }

    /**
     * @param mixed $isSystem
     *
     * @return EmailTemplate
     */
    public function setIsSystem($isSystem)
    {
        $this->isSystem = $isSystem;

        return $this;
    }

    public static function getTranslationEntityClass()
    {
        return EmailTemplateTranslation::class;
    }
}
