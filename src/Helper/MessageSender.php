<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailQueue;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Entity\MessageSendList;
use Hgabka\EmailBundle\Enum\MessageStatusEnum;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Translation\TranslatorInterface;

class MessageSender
{
    /** @var Registry */
    protected $doctrine;

    /** @var array */
    protected $config;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var QueueManager */
    protected $queueManager;

    /** @var bool */
    protected $forceLog = false;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var MailBuilder */
    protected $mailBuilder;

    /**
     * MailBuilder constructor.
     *
     * @param Registry            $doctrine
     * @param \Swift_Mailer       $mailer
     * @param QueueManager        $queueManager
     * @param TranslatorInterface $translator
     * @param HgabkaUtils         $hgabkaUtils  ,
     * @param MailBuilder         $mailBuilder
     */
    public function __construct(
        Registry $doctrine,
        \Swift_Mailer $mailer,
        QueueManager $queueManager,
        TranslatorInterface $translator,
        HgabkaUtils $hgabkaUtils,
        MailBuilder $mailBuilder
    ) {
        $this->doctrine = $doctrine;
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->queueManager = $queueManager;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->mailBuilder = $mailBuilder;
    }

    /**
     * @return bool
     */
    public function isForceLog(): bool
    {
        return $this->forceLog;
    }

    /**
     * @param bool $forceLog
     *
     * @return MessageSender
     */
    public function setForceLog($forceLog)
    {
        $this->forceLog = $forceLog;

        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param $message
     */
    public function deleteMessageFromQueue($message)
    {
        if (!$message) {
            return;
        }

        $this->queueManager->deleteMessageFromQueue($message);
    }

    public function addMessageToQueue($message)
    {
        if (!$message) {
            return;
        }

        $this->queueManager->addMessageToQueue($message, $this->getRecipientsForMessage($message));
        $message->setStatus(MessageStatusEnum::STATUS_FOLYAMATBAN);
        $this->updateMessageSendData($message);
    }

    public function getDefinedListRecipients($lists)
    {
        $lists = $this->getDefinedRecipientLists($lists);

        if (empty($lists)) {
            return [];
        }

        $recs = [];
        $recipientsConfig = $this->config['pre_defined_message_recipients'];

        foreach ($lists as $list) {
            $config = $recipientsConfig[$list];

            if (!isset($config['data']) || !is_callable($config['data'])) {
                continue;
            }

            $data = call_user_func($config['data']);

            if (!is_array($data)) {
                continue;
            }
            $realData = [];
            foreach ($data as $row) {
                $oneRow = [];
                if (isset($row['to'])) {
                    $oneRow['to'] = $row['to'];
                } elseif (isset($row['email'])) {
                    if (isset($row['name'])) {
                        $oneRow['to'] = [$row['email'] => $row['name']];
                    } else {
                        $oneRow['to'] = $row['email'];
                    }
                }

                if (!isset($oneRow['to'])) {
                    continue;
                }

                if (!isset($oneRow['culture'])) {
                    $oneRow['culture'] = $this->hgabkaUtils->getDefaultLocale();
                }

                foreach (array_keys($row) as $other) {
                    if (!in_array($other, ['to', 'culture', 'email', 'name'], true)) {
                        $oneRow[$other] = $row[$other];
                    }
                }

                $realData[] = $oneRow;
            }

            $recs = array_merge($recs, $realData);
        }

        return $recs;
    }

    public function getRecipientsForMessage($message)
    {
        $recs = [];
        foreach ($this->hgabkaUtils->getAvailableLocales() as $culture) {
            $tos = $this->getTos($message->getTo(), $culture);
            foreach ($tos as $to) {
                $recs[] = $to;
            }
        }

        $definedListRecipients = $this->getDefinedListRecipients($message->getToType());
        if (!empty($definedListRecipients)) {
            foreach ($definedListRecipients as $listRec) {
                $recs[] = $listRec;
            }
        }

        $listRecipients = $this->getListRecipientsForMessage($message);
        if (!empty($listRecipients)) {
            foreach ($listRecipients as $listRec) {
                $recs[] = $listRec;
            }
        }

        return $recs;
    }

    public function getListsForMessage($message)
    {
        $lists = [];
        $sendLists = $message->getSendLists();
        foreach ($sendLists as $sendList) {
            $lists[] = $sendList->getList()->getName();
        }

        return $lists;
    }

    public function getListRecipientsForMessage(Message $message)
    {
        $recs = [];

        $sendLists = $message->getSendLists();
        $emails = [];
        foreach ($sendLists as $sendList) {
            /** @var MessageSendList $sendList */
            foreach ($sendList->getList()->getListSubscriptions() as $listSubscription) {
                $subscriber = $listSubscription->getSubscriber();
                if (!$subscriber) {
                    $this->doctrine->getManager()->remove($listSubscription);
                    $this->doctrine->getManager()->flush();

                    continue;
                }

                if (!in_array($subscriber->getEmail(), $emails, true)) {
                    $ar = $this->hgabkaUtils->entityToArray($subscriber, 0);
                    $recs[] = array_merge($ar, [
                        'to' => [$subscriber->getEmail() => $subscriber->getName()],
                        'culture' => $subscriber->getLocale() ? $subscriber->getLocale() : $this->hgabkaUtils->getDefaultLocale(),
                    ]);
                    $emails[] = $subscriber->getEmail();
                }
            }
        }

        return $recs;
    }

    public function sendMessage(Message $message, $forceSend = false)
    {
        $sendAt = $message->getSendAt();
        $sentAt = $message->getSentAt();
        $sent = 0;
        $fail = 0;

        if (((null !== $sendAt && $sendAt > date('Y-m-d H:i:s')) || !empty($sentAt)) && !$forceSend) {
            return;
        }

        $mailer = $this->mailer;
        $recs = $this->getRecipientsForMessage($message);

        foreach ($recs as $rec) {
            if (!isset($rec['to'])) {
                continue;
            }
            $to = $rec['to'];
            $culture = isset($rec['culture']) ? $rec['culture'] : $this->hgabkaUtils->getDefaultLocale();

            $email = is_array($to) ? key($to) : $to;

            try {
                $mail = $this->mailBuilder->createMessageMail($message, $to, $culture, true, $rec);
                if ($mailer->send($mail)) {
                    $this->log('Email kuldese sikeres. Email: '.$email);

                    $message->setSentAt(new \DateTime());
                    ++$sent;
                } else {
                    $this->log('Email kuldes sikertelen. Email: '.$email);
                    ++$fail;
                }
            } catch (\Exception $e) {
                $this->log('Email kuldes sikertelen. Email: '.$email.' Hiba: '.$e->getMessage());

                ++$fail;
            }
        }

        $em = $this->doctrine->getManager();
        $em->flush();

        return ['sent' => $sent, 'fail' => $fail, 'total' => $sent + $fail];
    }

    public function log($message)
    {
        $this->queueManager->log($message, $this->forceLog);
    }

    public function prepareMessage($message)
    {
        $sendAt = $message->getSendAt();
        $message->setStatus(MessageStatusEnum::STATUS_KULDENDO);

        if (empty($sendAt) || $sendAt < new \DateTime()) {
            $this->addMessageToQueue($message);
        } else {
            $this->doctrine->getManager()->flush();
        }
    }

    public function updateMessageSendData($message)
    {
        if (!$message || MessageStatusEnum::STATUS_FOLYAMATBAN !== $message->getStatus()) {
            return;
        }

        $sendData = $this->getSendDataForMessage($message);

        $message
            ->setSentMail($sendData['sum'])
            ->setSentSuccess($sendData[QueueStatusEnum::STATUS_ELKULDVE])
            ->setSentFail($sendData[QueueStatusEnum::STATUS_SIKERTELEN])
        ;

        if ($sendData[QueueStatusEnum::STATUS_ELKULDVE] + $sendData[QueueStatusEnum::STATUS_SIKERTELEN] + $sendData[QueueStatusEnum::STATUS_VISSZAPATTANT] === $sendData['sum']) {
            $message->setStatus(MessageStatusEnum::STATUS_ELKULDVE);
            $days = $this->config['delete_sent_messages_after'];

            if (empty($days)) {
                $this->deleteMessageFromQueue($message);
            }
        }

        $em = $this->doctrine->getManager();
        $em->flush();
    }

    public function prepareMessages()
    {
        $messages = $this->getMessageRepository()->getMessagesToQueue();

        foreach ($messages as $message) {
            $this->prepareMessage($message);
        }
    }

    public function updateMessages()
    {
        $messages = $this->getMessageRepository()->getMessagesToUpdate();

        foreach ($messages as $message) {
            $this->updateMessageSendData($message);
        }
    }

    /**
     * @param null|int $limit
     *
     * @return array
     */
    public function sendEmailQueue($limit = null)
    {
        return $this->queueManager->sendEmails($limit);
    }

    /**
     * @param null|int $limit
     *
     * @return array
     */
    public function sendMessageQueue($limit = null)
    {
        $this->prepareMessages();

        $result = $this->queueManager->sendMessages($limit);

        $this->updateMessages();

        return $result;
    }

    public function unPrepareMessage($message)
    {
        if (!$message) {
            return;
        }

        $this->deleteMessageFromQueue($message);
        $message
            ->setSentMail(0)
            ->setSentSuccess(0)
            ->setSentFail(0)
            ->setStatus(MessageStatusEnum::STATUS_INIT)
        ;

        $em = $this->doctrine->getManager();
        $em->flush();
    }

    public function getTos($tos, $culture = null)
    {
        $toArray = explode("\r\n", trim($tos, "\r\n"));

        $recs = [];
        $culture = $this->hgabkaUtils->getCurrentLocale($culture);

        foreach ($toArray as $oneTo) {
            $oneTo = trim($oneTo, "\r\n");

            if (false !== strpos($oneTo, ':')) {
                $parts = explode(':', $oneTo);
                $email = trim($parts[1]);
                $to = [$email => trim($parts[0])];
            } else {
                $to = trim($oneTo);
            }

            if (!empty($to)) {
                $recs[] = ['to' => $to, 'culture' => $culture];
            }
        }

        return $recs;
    }

    public function getDefinedRecipientLists($lists)
    {
        $recipientsConfig = $this->config['pre_defined_message_recipients'];
        $lists = explode("\r\n", $lists);

        if (empty($recipientsConfig) || !is_array($recipientsConfig) || empty($lists)) {
            return [];
        }

        return array_intersect(array_keys($recipientsConfig), $lists);
    }

    public function sendMessages($limit = null)
    {
        if (empty($limit)) {
            $limit = $this->config['send_limit'];
        }

        $this->log('Uzenetek kuldese (limit: '.$limit.')');
        $messages = $this->getMessageRepository()->getMessagesToSend();

        $sent = 0;
        $fail = 0;

        foreach ($messages as $message) {
            $result = $this->sendMessage($message);

            $sent += $result['sent'];
            $fail += $result['fail'];

            if ($sent >= $limit) {
                $this->log('Limit elerve, kuldes vege');

                return ['sent' => $sent, 'fail' => $fail, 'total' => $sent + $fail];
            }
        }

        $this->log('Nincs tobb kuldendo email, kuldes vege');

        return ['sent' => $sent, 'fail' => $fail, 'total' => $sent + $fail];
    }

    public function getDefinedRecipientListChoices()
    {
        $recipientsConfig = $this->config['pre_defined_message_recipients'];

        if (empty($recipientsConfig) || !is_array($recipientsConfig)) {
            return [];
        }

        $choices = [];
        foreach ($recipientsConfig as $key => $config) {
            $choices[$key] = isset($config['label']) ? $this->translator->trans($config['label']) : $key;
        }

        return $choices;
    }

    /**
     * @param EmailTemplateTypeInterface|string $class
     * @param array                             $params
     * @param null                              $culture
     * @param null                              $sendAt
     * @param bool                              $campaign
     *
     * @return bool|mixed
     */
    public function enqueueTemplateMessage($class, $params = [], $culture = null, $sendAt = null, $campaign = false)
    {
        $culture = $this->hgabkaUtils->getCurrentLocale($culture);

        $message = $this->mailBuilder->createTemplateMessage($class, $params, $culture);
        if (!$message) {
            return false;
        }
        $attachments = $this->doctrine->getRepository(Attachment::class)->getByTemplate($template, $culture);

        return $this->queueManager->addEmailMessageToQueue($message, $attachments, $sendAt, $campaign);
    }

    /**
     * @param EmailTemplateTypeInterface|string $class
     * @param array                             $params
     * @param null                              $culture
     *
     * @return bool|int|mixed
     */
    public function sendTemplateMail($class, $params = [], $culture = null)
    {
        if ($this->config['force_queueing']) {
            return $this->enqueueTemplateMessage($class, $params, $culture, null);
        }

        $message = $this->mailBuilder->createTemplateMessage($class, $params, $culture);

        if (!$message) {
            return false;
        }

        return $this->mailer->send($message);
    }

    public function getSendDataForMessage(Message $message)
    {
        $data = $this->doctrine
            ->getRepository(MessageQueue::class)
            ->getSendDataForMessage($message);

        $sum = 0;
        $res = [
            QueueStatusEnum::STATUS_INIT => 0,
            QueueStatusEnum::STATUS_ELKULDVE => 0,
            QueueStatusEnum::STATUS_HIBA => 0,
            QueueStatusEnum::STATUS_SIKERTELEN => 0,
            QueueStatusEnum::STATUS_VISSZAPATTANT => 0,
        ];

        foreach ($data as $row) {
            $res[$row['status']] = $row['num'];
            $sum += $row['num'];
        }

        $res['sum'] = $sum;

        return $res;
    }

    protected function getQueueRepository()
    {
        return $this->doctrine->getRepository(MessageQueue::class);
    }

    protected function getEmailQueueRepository()
    {
        return $this->doctrine->getRepository(EmailQueue::class);
    }

    protected function getMessageRepository()
    {
        return $this->doctrine->getRepository(Message::class);
    }
}
