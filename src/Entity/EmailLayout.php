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
 * @ORM\Table(name="hg_kuma_email_email_layout")
 * @ORM\Entity(repositoryClass="Hgabka\KunstmaanEmailBundle\Repository\EmailLayoutRepository")
 */
class EmailLayout extends AbstractEntity implements TranslatableInterface
{
    use TranslatableTrait;
    use TimestampableEntity;

    /**
     * @var ArrayCollection|EmailTemplate[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\EmailTemplate", cascade={"all"}, mappedBy="layout", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $templates;

    /**
     * @var ArrayCollection|Message[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\Message", cascade={"all"}, mappedBy="layout", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $messages;

    /**
     * @Prezent\Translations(targetEntity="Hgabka\KunstmaanEmailBundle\Entity\EmailLayoutTranslation")
     */
    protected $translations;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="styles", type="text")
     */
    protected $styles;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
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
     * @return EmailLayout
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getStyles()
    {
        return $this->styles;
    }

    /**
     * @param string $styles
     *
     * @return EmailLayout
     */
    public function setStyles($styles)
    {
        $this->styles = $styles;

        return $this;
    }

    /**
     * @return EmailTemplate[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param EmailTemplate[] $templates
     *
     * @return EmailLayout
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Add template.
     *
     * @param EmailTemplate $template
     *
     * @return EmailLayout
     */
    public function addTemplate(EmailTemplate $template)
    {
        if (!$this->templates->contains($template)) {
            $this->templates[] = $template;

            $template->setLayout($this);
        }

        return $this;
    }

    /**
     * Remove template.
     *
     * @param EmailTemplate $template
     */
    public function removeTemplate(EmailTemplate $template)
    {
        $this->templates->removeElement($template);
    }

    /**
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     *
     * @return EmailLayout
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Add message.
     *
     * @param Message $message
     *
     * @return EmailLayout
     */
    public function addMessage(Message $message)
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;

            $message->setLayout($this);
        }

        return $this;
    }

    /**
     * Remove message.
     *
     * @param Message $message
     */
    public function removeMessage(Message $message)
    {
        $this->messages->removeElement($message);
    }

    public function getDecoratedHtml($culture, $subject = '', $layoutFile = false)
    {
        if (!empty($layoutFile)) {
            $layoutFile = strtr($layoutFile, ['%culture%' => $culture]);
            $html = @file_get_contents($layoutFile);
        } else {
            $html = null;
        }
        $content = $this->translate($culture)->getContentHtml();
        if (empty($html)) {
            return $content;
        }

        $styles = $this->getStyles();

        return strtr($html, ['%%styles%%' => $styles, '%%title%%' => $subject, '%%content%%' => $content]);
    }

    /**
     * @return string
     */
    public static function getTranslationEntityClass()
    {
        return EmailLayoutTranslation::class;
    }
}
