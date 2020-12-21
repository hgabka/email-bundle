<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_email_campaign")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\EmailCampaignRepository")
 */
class EmailCampaign
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var MessageList
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\MessageList", inversedBy="campaigns", cascade={"persist"})
     * @ORM\JoinColumn(name="message_list_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $list;

    /**
     * @var ArrayCollection|EmailCampaignMessage[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\EmailCampaignMessage", cascade={"all"}, mappedBy="campaign", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $messages;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="from_name", type="string", length=255, nullable=true)
     */
    protected $fromName;

    /**
     * @var string
     *
     * @ORM\Column(name="from_email", type="string", length=255, nullable=true)
     */
    protected $fromEmail;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $isActive = true;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->messages = new ArrayCollection();
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
     * @return EmailCampaign
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return MessageList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param MessageList $list
     *
     * @return EmailCampaign
     */
    public function setList($list)
    {
        $this->list = $list;

        return $this;
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
     * @return EmailCampaign
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     *
     * @return EmailCampaign
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     *
     * @return EmailCampaign
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisActive()
    {
        return $this->isActive;
    }

    /**
     * @param mixed $isActive
     *
     * @return EmailCampaign
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return EmailCampaignMessage[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param EmailCampaignMessage[] $messages
     *
     * @return EmailCampaign
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Add message.
     *
     * @return EmailCampaign
     */
    public function addMessage(EmailCampaignMessage $message)
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;

            $message->setCampaign($this);
        }

        return $this;
    }

    /**
     * Remove message.
     */
    public function removeMessage(EmailCampaignMessage $message)
    {
        $this->messages->removeElement($message);
    }
}
