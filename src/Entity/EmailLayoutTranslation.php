<?php

namespace Hgabka\KunstmaanEmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kunstmaan\AdminBundle\Entity\AbstractEntity;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\Entity\TranslationTrait;
use Prezent\Doctrine\Translatable\TranslationInterface;

/**
 * @ORM\Table(name="hg_kuma_email_email_layout_translation")
 * @ORM\Entity
 */
class EmailLayoutTranslation extends AbstractEntity implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @Prezent\Translatable(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\EmailLayout")
     */
    protected $translatable;

    /**
     * @var string
     *
     * @ORM\Column(name="content_html", type="text")
     */
    protected $contentHtml = '';

    /**
     * @return string
     */
    public function getContentHtml()
    {
        return $this->contentHtml;
    }

    /**
     * @param string $contentHtml
     *
     * @return EmailLayoutTranslation
     */
    public function setContentHtml($contentHtml)
    {
        $this->contentHtml = $contentHtml;

        return $this;
    }
}
