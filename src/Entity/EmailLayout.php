<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\EmailBundle\Repository\EmailLayoutRepository;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_layout")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailLayoutRepository")
 */
#[ORM\Table(name: 'hg_email_email_layout')]
#[ORM\Entity(repositoryClass: EmailLayoutRepository::class)]
class EmailLayout implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

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
     * @var ArrayCollection|EmailTemplate[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\EmailTemplate", cascade={"all"}, mappedBy="layout", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    #[ORM\OneToMany(targetEntity: EmailTemplate::class, cascade: ['all'], mappedBy: 'layout', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $templates = null;

    /**
     * @var ArrayCollection|Message[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\Message", cascade={"all"}, mappedBy="layout", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    #[ORM\OneToMany(targetEntity: Message::class, cascade: ['all'], mappedBy: 'layout', orphanRemoval: true)]
    #[Assert\Valid]
    protected Collection|array|null $messages = null;

    /**
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\EmailLayoutTranslation")
     */
    #[Hgabka\Translations(targetEntity: EmailLayoutTranslation::class)]
    protected Collection|array|null $translations = null;

    /**
     * @var string
     *
     * @ORM\Column(name="styles", type="text")
     */
    #[ORM\Column(name: 'styles', type: 'text')]
    protected ?string $styles = null;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->templates = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

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
     * @return EmailLayout
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param null|mixed $locale
     *
     * @return string
     */
    public function getName(?string $locale = null): ?string
    {
        return $this->translate($locale)->getName();
    }

    /**
     * @param string     $name
     * @param null|mixed $locale
     *
     * @return EmailLayout
     */
    public function setName(?string $name, ?string $locale = null): self
    {
        $this->translate($locale)->setName($name);

        return $this;
    }

    /**
     * @return string
     */
    public function getStyles(): ?string
    {
        return $this->styles;
    }

    /**
     * @param string $styles
     *
     * @return EmailLayout
     */
    public function setStyles(?string $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    /**
     * @return EmailTemplate[]
     */
    public function getTemplates(): Collection
    {
        return $this->templates;
    }

    /**
     * @param EmailTemplate[] $templates
     *
     * @return EmailLayout
     */
    public function setTemplates(Collection $templates): self
    {
        $this->templates = $templates;

        return $this;
    }

    /**
     * Add template.
     *
     * @return EmailLayout
     */
    public function addTemplate(EmailTemplate $template): self
    {
        if (!$this->templates->contains($template)) {
            $this->templates[] = $template;

            $template->setLayout($this);
        }

        return $this;
    }

    /**
     * Remove template.
     */
    public function removeTemplate(EmailTemplate $template): void
    {
        $this->templates->removeElement($template);
    }

    /**
     * @return Message[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     *
     * @return EmailLayout
     */
    public function setMessages(Collection $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Add message.
     *
     * @return EmailLayout
     */
    public function addMessage(Message $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;

            $message->setLayout($this);
        }

        return $this;
    }

    /**
     * Remove message.
     */
    public function removeMessage(Message $message): void
    {
        $this->messages->removeElement($message);
    }

    /**
     * @return string
     */
    public static function getTranslationEntityClass(): string
    {
        return EmailLayoutTranslation::class;
    }
}
