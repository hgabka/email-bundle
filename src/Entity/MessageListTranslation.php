<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\Entity\TranslationTrait;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\Doctrine\Translatable\TranslationInterface;

#[ORM\Table(name: 'hg_email_message_list_translation')]
#[ORM\Entity]
class MessageListTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @Hgabka\Translatable(targetEntity="Hgabka\EmailBundle\Entity\MessageList")
     */
    #[Hgabka\Translatable]
    protected ?TranslatableInterface $translatable = null;

    #[ORM\Column(name: 'name', type: 'string')]
    protected ?string $name = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }
}
