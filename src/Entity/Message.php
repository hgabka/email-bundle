<?php

namespace Hgabka\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Hgabka\Doctrine\Translatable\Annotation as Hgabka;
use Hgabka\Doctrine\Translatable\TranslatableInterface;
use Hgabka\EmailBundle\Enum\MessageStatusEnum;
use Hgabka\UtilsBundle\Entity\TranslatableTrait;
use Hgabka\UtilsBundle\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Email layout.
 *
 * @ORM\Table(name="hg_email_message")
 * @ORM\Entity(repositoryClass="Hgabka\EmailBundle\Repository\MessageRepository")
 */
class Message implements TranslatableInterface
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
     * @Hgabka\Translations(targetEntity="Hgabka\EmailBundle\Entity\MessageTranslation")
     */
    protected $translations;

    /**
     * @var ArrayCollection|MessageSendList[]
     *
     * @ORM\OneToMany(targetEntity="Hgabka\EmailBundle\Entity\MessageSendList", cascade={"all"}, mappedBy="message", orphanRemoval=true)
     *
     * @Assert\Valid()
     */
    protected $sendLists;

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
     * @var \DateTime
     * @ORM\Column(name="send_at", type="datetime", nullable=true)
     */
    protected $sendAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    protected $sentAt;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=20)
     */
    protected $status = MessageStatusEnum::STATUS_INIT;

    /**
     * @var EmailLayout
     *
     * @ORM\ManyToOne(targetEntity="Hgabka\EmailBundle\Entity\EmailLayout", inversedBy="messages", cascade={"persist"})
     * @ORM\JoinColumn(name="email_layout_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $layout;

    /**
     * @var int
     * @ORM\Column(name="sent_mail", type="integer")
     */
    protected $sentMail = 0;

    /**
     * @var int
     * @ORM\Column(name="sent_success", type="integer")
     */
    protected $sentSuccess = 0;

    /**
     * @var int
     * @ORM\Column(name="sent_fail", type="integer")
     */
    protected $sentFail = 0;

    /**
     * @ORM\Column(name="is_simple", type="boolean")
     */
    protected $isSimple = false;

    /**
     * @var string
     * @ORM\Column(name="locale", type="string", length=2, nullable=true)
     */
    protected $locale;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->sendLists = new ArrayCollection();
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
     * @return Message
     */
    public function setId($id)
    {
        $this->id = $id;

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
     * @return Message
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return Message
     */
    public function setType($status)
    {
        if (!\in_array($status, MessageStatusEnum::getAvailableStatuses(), true)) {
            throw new \InvalidArgumentException('Invalid type');
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * @param mixed $sendAt
     *
     * @return Message
     */
    public function setSendAt(\DateTime $sendAt = null)
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentMail()
    {
        return $this->sentMail;
    }

    /**
     * @param int $sentMail
     *
     * @return Message
     */
    public function setSentMail($sentMail)
    {
        $this->sentMail = $sentMail;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentSuccess()
    {
        return $this->sentSuccess;
    }

    /**
     * @param int $sentSuccess
     *
     * @return Message
     */
    public function setSentSuccess($sentSuccess)
    {
        $this->sentSuccess = $sentSuccess;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentFail()
    {
        return $this->sentFail;
    }

    /**
     * @param int $sentFail
     *
     * @return Message
     */
    public function setSentFail($sentFail)
    {
        $this->sentFail = $sentFail;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getisSimple()
    {
        return $this->isSimple;
    }

    /**
     * @param mixed $isSimple
     *
     * @return Message
     */
    public function setIsSimple($isSimple)
    {
        $this->isSimple = $isSimple;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return Message
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return MessageSendList[]
     */
    public function getSendLists()
    {
        return $this->sendLists;
    }

    /**
     * @param MessageSendList[] $sendLists
     *
     * @return Message
     */
    public function setSendLists($sendLists)
    {
        $this->sendLists = $sendLists;

        return $this;
    }

    /**
     * Add send list.
     *
     * @return Message
     */
    public function addSendList(MessageSendList $sendList)
    {
        if (!$this->sendLists->contains($sendList)) {
            $this->sendLists[] = $sendList;

            $sendList->setMessage($this);
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

    public static function getTranslationEntityClass()
    {
        return MessageTranslation::class;
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
     * @return Message
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
     * @return Message
     */
    public function setBccData($bccData)
    {
        $this->bccData = $bccData;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSentAt()
    {
        return $this->sentAt;
    }

    /**
     * @param \DateTime $sentAt
     *
     * @return Message
     */
    public function setSentAt($sentAt)
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function isPrepareable()
    {
        return MessageStatusEnum::STATUS_INIT === $this->getStatus() || null === $this->getId();
    }

    public function isUnprepareable()
    {
        return \in_array($this->getStatus(), [MessageStatusEnum::STATUS_KULDENDO, MessageStatusEnum::STATUS_FOLYAMATBAN], true);
    }

    public function isBeingSent()
    {
        return \in_array($this->getStatus(), [MessageStatusEnum::STATUS_ELKULDVE, MessageStatusEnum::STATUS_FOLYAMATBAN], true);
    }

    public function isPrepared()
    {
        return MessageStatusEnum::STATUS_KULDENDO === $this->getStatus();
    }

    public function getSendTime()
    {
        if (null === $this->sendAt) {
            return [
                'type' => 'now',
                'time' => null,
            ];
        }

        return [
                'type' => 'later',
                'time' => $this->sendAt,
            ];
    }

    public function setSendTime($data)
    {
        if (!isset($data['type']) || 'now' === $data['type']) {
            $this->setSendAt(null);
        } else {
            $this->setSendAt($data['time']);
        }

        return $this;
    }

    public function getSubject($locale = null)
    {
        return $this->translate($locale)->getSubject();
    }

    /**
     * @param string $status
     *
     * @return Message
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusDisplay()
    {
        $choices = MessageStatusEnum::getStatusTextChoices();

        return $choices[$this->getStatus()] ?? '';
    }

    public function getName($locale = null)
    {
        return $this->translate($locale)->getName();
    }

    public function getFromName($locale = null)
    {
        return $this->translate($locale)->getFromName();
    }

    public function getFromEmail($locale = null)
    {
        return $this->translate($locale)->getFromEmail();
    }
}
