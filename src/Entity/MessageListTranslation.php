<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Prezent\Doctrine\Translatable\Annotation as Prezent;
use Prezent\Doctrine\Translatable\Entity\TranslationTrait;
use Prezent\Doctrine\Translatable\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="hg_email_message_list_translation")
 * @ORM\Entity
 */
class MessageListTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Prezent\Translatable(targetEntity="Hgabka\EmailBundle\Entity\MessageList")
     */
    protected $translatable;

    /**
     * @ORM\Column(name="name", type="string")
     * @Assert\NotBlank()
     */
    protected $name;

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
     * @return MessageTranslation
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return MessageTranslation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
