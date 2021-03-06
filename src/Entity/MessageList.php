<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MessageList.
 *
 * @ORM\Table(name="hg_email_message_list")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageListRepository")
 */
class MessageList implements TranslatableInterface
{
    use TimestampableEntity;
    use TranslatableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\MessageListTranslation")
     */
    protected $translations;

    /**
     * @var ArrayCollection|MessageListSubscription[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\MessageListSubscription", cascade={"all"}, mappedBy="list", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $listSubscriptions;

    /**
     * @var ArrayCollection|MessageSendList[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\MessageSendList", cascade={"all"}, mappedBy="list", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $sendLists;

    /**
     * @var ArrayCollection|EmailCampaign[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\EmailCampaign", cascade={"all"}, mappedBy="list", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $campaigns;

    /**
     * @ORM\Column(name="is_default", type="boolean")
     */
    protected $isDefault = false;

    /**
     * @ORM\Column(name="is_public", type="boolean")
     */
    protected $isPublic = true;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->listSubscriptions = new ArrayCollection();
        $this->sendLists = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName() ?: '';
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
     * @return MessageList
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param null|mixed $locale
     *
     * @return mixed
     */
    public function getName($locale = null)
    {
        return $this->translate($locale)->getName();
    }

    /**
     * @param mixed      $name
     * @param null|mixed $locale
     *
     * @return MessageList
     */
    public function setName($name, $locale = null)
    {
        $this->translate($locale)->setName($name);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param mixed $isDefault
     *
     * @return MessageList
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisPublic()
    {
        return $this->isPublic;
    }

    /**
     * @param mixed $isPublic
     *
     * @return MessageList
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return MessageSendList[]
     */
    public function getMessageSendLists()
    {
        return $this->sendLists;
    }

    /**
     * @param MessageSendList[] $sendLists
     *
     * @return MessageList
     */
    public function setSendLists($sendLists)
    {
        $this->sendLists = $sendLists;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return MessageList
     */
    public function addSendList(MessageSendList $sendList)
    {
        if (!$this->sendLists->contains($sendList)) {
            $this->sendLists[] = $sendList;

            $sendList->setList($this);
        }

        return $this;
    }

    /**
     * Remove send list.
     */
    public function removeSendList(MessageSendList $sendList)
    {
        $this->sendLists->removeElement($sendList);
    }

    /**
     * @return MessageListSubscription[]
     */
    public function getListSubscriptions()
    {
        return $this->listSubscriptions;
    }

    /**
     * @param MessageListSubscription[] $listSubscriptions
     *
     * @return MessageList
     */
    public function setListSubscriptions($listSubscriptions)
    {
        $this->listSubscriptions = $listSubscriptions;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return MessageList
     */
    public function addListSubscription(MessageListSubscription $listSubscription)
    {
        if (!$this->listSubscriptions->contains($listSubscription)) {
            $this->listSubscriptions[] = $listSubscription;

            $listSubscription->setList($this);
        }

        return $this;
    }

    /**
     * Remove send list.
     */
    public function removeListSubscription(MessageListSubscription $listSubscription)
    {
        $this->listSubscriptions->removeElement($listSubscription);
    }
}
