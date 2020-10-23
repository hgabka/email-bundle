<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;

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
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplateTranslation")
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
     * @ORM\Column(name="to_data", type="array", nullable=true)
     */
    protected $toData;

    /**
     * @ORM\Column(name="cc_data", type="array", nullable=true)
     */
    protected $ccData;

    /**
     * @ORM\Column(name="bcc_data", type="array", nullable=true)
     */
    protected $bccData;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function __toString()
    {
        return 'Email sablon';
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
