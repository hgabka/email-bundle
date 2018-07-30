<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\TranslatableInterface;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_template")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailTemplateRepository")
 */
class EmailTemplate implements TranslatableInterface
{
    use TranslatableTrait;
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Prezent\Translations(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplateTranslation")
     */
    protected $translations;

    /**
     * @ORM\Column(name="type", type="text", nullable=true)
     */
    protected $type;

    /**
     * @var EmailLayout
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailLayout", inversedBy="templates", cascade={"persist"})
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return EmailTemplate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->translate()->getName();
    }

    /**
     * @param string     $name
     * @param null|mixed $locale
     *
     * @return EmailTemplate
     */
    public function setName($name, $locale = null)
    {
        $this->translate($locale)->setName($name);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->translate()->getComment();
    }

    /**
     * @param mixed $comment
     *
     * @return EmailTemplate
     */
    public function setComment($comment)
    {
        $this->translate($locale)->setComment($comment);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return EmailTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

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
    public function isSystem()
    {
        return !empty($this->type);
    }

    public static function getTranslationEntityClass()
    {
        return EmailTemplateTranslation::class;
    }
}
