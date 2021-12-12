<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\AbstractQueue;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailQueue;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;
use Hgabka\EmailBundle\Logger\MessageLogger;
use Hgabka\MediaBundle\Entity\Media;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class QueueManager
{
    /** @var Registry */
    protected $doctrine;

    protected $lastError;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var MessageLogger */
    protected $logger;

    /** @var bool */
    protected $forceLog = false;

    /** @var bool */
    protected $loggingEnabled = false;

    /** @var array */
    protected $bounceConfig;

    /** @var int */
    protected $maxRetries;

    /** @var int */
    protected $sendLimit;

    /** @var int */
    protected $deleteSentMessagesAfter;

    /** @var MailBuilder */
    protected $mailBuilder;

    /** @var RecipientManager */
    protected $recipientManager;

    /** @var RouterInterface */
    protected $router;

    /** @var string */
    protected $messageReturnPath;

    /** @var string */
    protected $emailReturnPath;

    public function __construct(Registry $doctrine, \Swift_Mailer $mailer, MessageLogger $logger, RecipientManager $recipientManager, RouterInterface $router, array $bounceConfig, int $maxRetries, int $sendLimit, bool $loggingEnabled, int $deleteSentMessagesAfter, $messageReturnPath, $emailReturnPath)
    {
        $this->doctrine = $doctrine;
        $this->mailer = $mailer;
        $this->bounceConfig = $bounceConfig;
        $this->maxRetries = $maxRetries;
        $this->sendLimit = $sendLimit;
        $this->logger = $logger;
        $this->loggingEnabled = $loggingEnabled;
        $this->deleteSentMessagesAfter = $deleteSentMessagesAfter;
        $this->recipientManager = $recipientManager;
        $this->router = $router;
        $this->messageReturnPath = $messageReturnPath;
        $this->emailReturnPath = $emailReturnPath;
    }

    /**
     * @return QueueManager
     */
    public function setMailBuilder(MailBuilder $mailBuilder)
    {
        $this->mailBuilder = $mailBuilder;

        return $this;
    }

    /**
     * @param $message
     * @param mixed $force
     *
     * @return bool
     */
    public function log($message, $force = false)
    {
        if (!$this->forceLog && !$this->loggingEnabled && !$force) {
            return false;
        }

        $this->logger->getLogger()->info($message);
    }

    public function isForceLog(): bool
    {
        return $this->forceLog;
    }

    /**
     * @param bool $forceLog
     */
    public function setForceLog($forceLog): MailBuilder
    {
        $this->forceLog = $forceLog;

        return $this;
    }

    public function sendEmailQueue(EmailQueue $queue)
    {
        $to = unserialize($queue->getTo());
        $from = unserialize($queue->getFrom());
        $cc = $queue->getCc();
        if (!empty($cc)) {
            $cc = unserialize($cc);
        }

        $bcc = $queue->getBcc();
        if (!empty($bcc)) {
            $bcc = unserialize($bcc);
        }

        try {
            $message =
                (new \Swift_Message($queue->getSubject()))
                    ->setFrom($from)
                    ->setTo($to)
            ;

            if (!empty($cc)) {
                $message->setCc($cc);
            }

            if (!empty($bcc)) {
                $message->setBcc($bcc);
            }

            $contentText = $queue->getContentText();
            $contentHtml = $this->mailBuilder->embedImages($queue->getContentHtml(), $message);

            if (!empty($contentText)) {
                $message->setBody($contentText);
            }
            if (!empty($contentHtml)) {
                $message->addPart($contentHtml, 'text/html');
            }

            foreach ($this->getAttachments($queue) as $attachment) {
                $content = $attachment->getContent();

                if ($content) {
                    $message->attach(
                        (new \Swift_Attachment())
                            ->setBody($content)
                            ->setFilename($attachment->getFilename())
                            ->setContentType($attachment->getContentType())
                            ->setSize(\strlen($content))
                    );
                }
            }

            $headers = $message->getHeaders();
            $headers->addTextHeader('Hg-Email-Id', $queue->getId());

            if (isset($this->bounceConfig['account']['address'])) {
                $message->setReturnPath($this->bounceConfig['account']['address']);
            } else {
                if (null === $this->emailReturnPath) {
                    $message->setReturnPath(\is_string($from) ? $from : key($from));
                } elseif (\is_string($this->emailReturnPath)) {
                    $message->setReturnPath($this->emailReturnPath);
                }
            }

            if ($this->mailer->send($message) < 1) {
                $this->setError('Sikertelen küldés', $queue);
                $this->doctrine->getManager()->flush();

                return false;
            }
            $queue->setStatus(QueueStatusEnum::STATUS_ELKULDVE);
            $this->doctrine->getManager()->flush();

            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage(), $queue);
            $this->doctrine->getManager()->flush();

            return false;
        }
    }

    public function sendMessageQueue(MessageQueue $queue)
    {
        $mailer = $this->mailer;
        $message = $queue->getMessage();

        if (!$message) {
            $queue->setStatus(QueueStatusEnum::STATUS_SIKERTELEN);
            $this->doctrine->getManager()->flush();
            $this->lastError = 'Hianyzo uzenet';
        }

        if (\in_array($queue->getStatus(), [QueueStatusEnum::STATUS_SIKERTELEN, QueueStatusEnum::STATUS_VISSZAPATTANT], true)) {
            return false;
        }

        $toName = $queue->getToName();
        $toEmail = $queue->getToEmail();

        $to = empty($toName) ? $toEmail : [$toEmail => $toName];

        $bounceConfig = $this->bounceConfig;

        try {
            $params = json_decode($queue->getParameters(), true);

            if (!isset($params['type'])) {
                return false;
            }
            $recType = $this->recipientManager->getMessageRecipientType($params['type']);
            if (!$recType) {
                return false;
            }
            $recType->setParams($params['typeParams'] ?? []);
            if (!isset($params['vars'])) {
                $params['vars'] = [];
            }
            $params['vars']['webversion'] = $this->router->generate('hgabka_email_message_webversion', ['id' => $queue->getId(), 'hash' => $queue->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

            ['mail' => $mail] = $this->mailBuilder->createMessageMail($message, $to, $queue->getLocale(), true, $params, $recType);
            /** @var \Swift_Message $mail */
            $headers = $mail->getHeaders();
            $headers->addTextHeader('Hg-Message-Id', $message->getId());

            if (isset($bounceConfig['account']['address'])) {
                $mail->setReturnPath($bounceConfig['account']['address']);
            } else {
                if (null === $this->messageReturnPath) {
                    $from = $mail->getFrom();
                    $mail->setReturnPath(\is_string($from) ? $from : key($from));
                } elseif (\is_string($this->messageReturnPath)) {
                    $mail->setReturnPath($this->messageReturnPath);
                }
            }

            if ($mailer->send($mail) < 1) {
                $queue->setError('Sikertelen kuldes');
                $this->doctrine->getManager()->flush();

                return false;
            }
            $queue->setStatus(QueueStatusEnum::STATUS_ELKULDVE);
            $this->doctrine->getManager()->flush();

            return true;
        } catch (\Exception $e) {
            $this->setError($e->getMessage(), $queue);
            $this->doctrine->getManager()->flush();

            return false;
        }
    }

    public function getAttachments(AbstractQueue $queue)
    {
        return $this->doctrine->getRepository(Attachment::class)->getByQueue($queue);
    }

    public function setError(string $message, AbstractQueue $queue)
    {
        $retries = $queue->getRetries();
        $this->lastError = $message;
        if (empty($retries)) {
            $retries = 0;
        }

        if (++$retries > $this->maxRetries) {
            $queue->setStatus(QueueStatusEnum::STATUS_SIKERTELEN);
            $this->lastError .= "\n{$this->maxRetries} probalkozas elerve, sikertelen statusz beallitva";
        } else {
            $queue->setRetries($retries)->setStatus(QueueStatusEnum::STATUS_HIBA);
        }
    }

    public function addMessageToQueue(Message $message)
    {
        $this->deleteMessageFromQueue($message);

        if (!$message) {
            return false;
        }

        $recipients = [];
        foreach ($message->getToData() as $toData) {
            $recType = $this->recipientManager->getMessageRecipientType($toData['type']);
            $recType->setParams($toData);
            if (!$recType) {
                continue;
            }
            $recTypeRecipients = $recType->getRecipients();
            if (!\is_array($recTypeRecipients)) {
                $recTypeRecipients = [['to' => $recTypeRecipients, 'locale' => null]];
            } elseif (isset($recTypeRecipients['to'])) {
                $recTypeRecipients = [$recTypeRecipients];
            }

            foreach ($recTypeRecipients as $recData) {
                $recipients[] = array_merge(['type' => \get_class($recType), 'typeParams' => $toData], $recData);
            }
        }

        foreach ($recipients as $recipient) {
            if (!isset($recipient['to'])) {
                continue;
            }

            $to = $recipient['to'];
            $locale = $recipient['locale'] ?? null;

            $existing = $this
                ->doctrine
                ->getRepository(MessageQueue::class)
                ->getForMessageAndEmail($message, \is_array($to) ? key($to) : $to)
            ;

            if ($existing) {
                continue;
            }
            $queue = new MessageQueue();
            $queue->setMessage($message);
            if (\is_array($to)) {
                $queue->setToEmail(key($to));
                $queue->setToName(current($to));
            } else {
                $queue->setToEmail($to);
            }

            $params = [];

            $queue->setLocale($locale);
            $queue->setParameters(json_encode($recipient));
            $queue->setRetries(0);
            $queue->setStatus(QueueStatusEnum::STATUS_INIT);

            $em = $this->doctrine->getManager();
            $em->persist($queue);
            $em->flush();
        }
    }

    public function addEmailMessageToQueue($message, $attachments, $sendAt = null, $campaign = false)
    {
        if (!$message) {
            return;
        }

        $queue = new EmailQueue();
        $queue->setSendAt($sendAt);
        $queue->setFrom(serialize($message->getFrom()));
        $queue->setTo(serialize($message->getTo()));
        $queue->setCc($message->getCc());
        $queue->setBcc($message->getBcc());
        $queue->setSubject($message->getSubject());
        $queue->setContentText($message->getBody());

        if ($campaign instanceof EmailCampaign) {
            $queue->setCampaign($campaign);
        }

        $children = $message->getChildren();
        foreach ($children as $child) {
            if ('text/html' === $child->getContentType()) {
                $queue->setContentHtml($child->getBody());
            }
        }

        $em = $this->doctrine->getManager();
        $em->persist($queue);
        $em->flush();

        foreach ($attachments as $attachment) {
            $newAttachment = new Attachment();
            $newAttachment->setType(\get_class($queue));
            $newAttachment->setOwnerId($queue->getId());
            /** @var Media $media */
            $media = $attachment->getMedia();
            $newAttachment->setFilename($media->getOriginalFilename());
            $newAttachment->setContent($this->mailBuilder->getMediaContent($media));
            $newAttachment->setContentType($media->getContentType());
            $newAttachment->setLocale($attachment->getLocale());

            $em->persist($newAttachment);
        }

        $em->flush();

        return $message;
    }

    /**
     * @return mixed
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param null|int $limit
     */
    public function sendEmails($limit = null): array
    {
        if (empty($limit)) {
            $limit = $this->sendLimit;
        }

        $this->log('Uzenetek kuldese (limit: ' . $limit . ')');

        $count = $sent = $fail = 0;

        $queueRepo = $this->doctrine->getRepository(EmailQueue::class);
        $errorQueues = $queueRepo->getErrorQueuesForSend($limit);

        foreach ($errorQueues as $queue) {
            ++$count;
            $to = unserialize($queue->getTo());

            $email = \is_array($to) ? key($to) : $to;

            if ($this->sendEmailQueue($queue)) {
                $this->log('Sikertelen kuldes ujra. Email kuldese sikeres. Email: ' . $email);
                $this->doctrine->getManager()->remove($queue);
                ++$sent;
            } else {
                $this->log('Sikertelen kuldes ujra. Email kuldes sikertelen. Email: ' . $email . ' Hiba: ' . $queue->getLastError());
                ++$fail;
            }
        }

        if ($sent >= $limit) {
            $this->log('Limit elerve, kuldes vege');

            return ['total' => $count, 'sent' => $sent, 'fail' => $fail];
        }

        $queues = $queueRepo->getNotSentQueuesForSend($limit - $sent);

        foreach ($queues as $queue) {
            ++$count;
            $to = unserialize($queue->getTo());

            $email = \is_array($to) ? key($to) : $to;
            if ($this->sendEmailQueue($queue)) {
                $this->log('Email kuldese sikeres. Email: ' . $email);

                $days = $this->deleteSentMessagesAfter;

                if (empty($days)) {
                    $queue->delete();
                }
                ++$sent;
            } else {
                $this->log('Email kuldes sikertelen. Email: ' . $email . ' Hiba: ' . $this->getLastError());
                ++$fail;
            }
        }

        if ($count >= $limit) {
            $this->log('Limit elerve, kuldes vege');
        } else {
            $this->log('Nincs tobb kuldendo email, kuldes vege');
        }

        return ['total' => $count, 'sent' => $sent, 'fail' => $fail];
    }

    /**
     * @param null|int $limit
     */
    public function sendMessages($limit = null): array
    {
        if (empty($limit)) {
            $limit = $this->sendLimit;
        }

        $this->log('Uzenetek kuldese (limit: ' . $limit . ')');

        $count = $sent = $fail = 0;

        $queueRepo = $this->doctrine->getRepository(MessageQueue::class);
        $errorQueues = $queueRepo->getErrorQueuesForSend($limit);

        foreach ($errorQueues as $queue) {
            ++$count;
            $email = $queue->getToEmail();

            if ($this->sendMessageQueue($queue)) {
                $this->log('Sikertelen kuldes ujra. Email kuldese sikeres. Email: ' . $email);
                ++$sent;
            } else {
                $this->log('Sikertelen kuldes ujra. Email kuldes sikertelen. Email: ' . $email . ' Hiba: ' . $this->getLastError());
                ++$fail;
            }
        }

        if ($sent >= $limit) {
            $this->log('Limit elerve, kuldes vege');

            return ['total' => $count, 'sent' => $sent, 'fail' => $fail];
        }

        $queues = $queueRepo->getNotSentQueuesForSend($limit - $sent);

        foreach ($queues as $queue) {
            ++$count;
            $email = $queue->getToEmail();
            if ($this->sendMessageQueue($queue)) {
                $this->log('Email kuldese sikeres. Email: ' . $email);
                ++$sent;
            } else {
                $this->log('Email kuldes sikertelen. Email: ' . $email . ' Hiba: ' . $this->getLastError());
                ++$fail;
            }
        }

        if ($count >= $limit) {
            $this->log('Limit elerve, kuldes vege');
        } else {
            $this->log('Nincs tobb kuldendo email, kuldes vege');
        }

        return ['total' => $count, 'sent' => $sent, 'fail' => $fail];
    }

    public function deleteMessageFromQueue($message)
    {
        if (!$message) {
            return;
        }

        $this->doctrine->getRepository(MessageQueue::class)->deleteMessageFromQueue($message);
    }

    public function deleteEmailFromQueue($email)
    {
        $this->doctrine->getRepository(MessageQueue::class)->deleteEmailFromQueue($email);
    }

    public function deleteEmailFromEmailQueue($email, EmailCampaign $campaign = null, $withFlush = true)
    {
        $em = $this->doctrine->getManager();

        $queues = $this->doctrine->getRepository(EmailQueue::class)->getQueues($campaign);
        foreach ($queues as $queue) {
            if ($queue->isForEmail($email)) {
                $em->remove($queue);
            }
        }

        if ($withFlush) {
            $em->flush();
        }
    }

    public function clearMessageQueue()
    {
        return $this->doctrine->getRepository(MessageQueue::class)->clearQueue($this->deleteSentMessagesAfter);
    }
}
