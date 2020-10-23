<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_layout")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailLayoutRepository")
 */
class EmailLayout implements TranslatableInterface
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
     * @var ArrayCollection|EmailTemplate[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplate", cascade={"all"}, mappedBy="layout", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $templates;

    /**
     * @var ArrayCollection|Message[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\Message", cascade={"all"}, mappedBy="layout", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $messages;

    /**
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\EmailLayoutTranslation")
     */
    protected $translations;

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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return EmailLayout
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param null|mixed $locale
     *
     * @return string
     */
    public function getName($locale = null)
    {
        return $this->translate($locale)->getName();
    }

    /**
     * @param string     $name
     * @param null|mixed $locale
     *
     * @return EmailLayout
     */
    public function setName($name, $locale = null)
    {
        $this->translate($locale)->setName($name);

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

    /**
     * @return string
     */
    public static function getTranslationEntityClass()
    {
        return EmailLayoutTranslation::class;
    }
}
