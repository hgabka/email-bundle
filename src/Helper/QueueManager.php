<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use function get_class;
use Hgabka\EmailBundle\Entity\AbstractQueue;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailCampaign;
use Hgabka\EmailBundle\Entity\EmailQueue;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Enum\QueueStatusEnum;
use Hgabka\EmailBundle\Logger\MessageLogger;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class QueueManager
{
    /** @var Registry */
    protected $doctrine;

    protected $lastError;

    /** @var MailerInterface */
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

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    public function __construct(Registry $doctrine, MailerInterface $mailer, MessageLogger $logger, RecipientManager $recipientManager, RouterInterface $router, HgabkaUtils $hgabkaUtils, array $bounceConfig, int $maxRetries, int $sendLimit, bool $loggingEnabled, int $deleteSentMessagesAfter, $messageReturnPath, $emailReturnPath)
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
        $this->hgabkaUtils = $hgabkaUtils;
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

    public function sendEmailQueue(EmailQueue $queue): bool
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

        $embeds = $queue->getEmbeds();

        try {
            $message =
                (new Email())
                    ->from(...$from)
                    ->to(...$to)
                    ->subject($queue->getSubject())
            ;

            if (!empty($cc)) {
                $message->cc(...$cc);
            }

            if (!empty($bcc)) {
                $message->bcc(...$bcc);
            }

            $contentText = $queue->getContentText();
            $contentHtml = $this->mailBuilder->embedImages($queue->getContentHtml(), $message);

            if (!empty($contentText)) {
                $message->text($contentText);
            }
            if (!empty($contentHtml)) {
                $message->html($contentHtml);
            }

            foreach ($embeds as $embed) {
                $message->embedFromPath($embed['path'], $embed['name'], $embed['content-type']);
            }

            foreach ($this->getAttachments($queue) as $attachment) {
                $content = $attachment->getContent();

                if ($content) {
                    $message->attach($content, $attachment->getFilename(), $attachment->getContentType());
                }
            }

            $headers = $message->getHeaders();
            $headers->addTextHeader('Hg-Email-Id', $queue->getId());

            $returnPath = $queue->getReturnPath();

            if (!empty($returnPath)) {
                $message->returnPath(unserialize($returnPath));
            } else {
                $message->returnPath(...$message->getFrom());
            }

            $headers = $queue->getHeaders();

            if (!empty($headers)) {
                $this->mailBuilder->addHeadersFromArray($message, $headers);
            }

            try {
                $this->mailer->send($message);
            } catch (TransportException $e) {
                $this->setError('Sikertelen küldés', $queue);
                $this->doctrine->getManager()->flush();

                return false;
            }
            $queue->setStatus(QueueStatusEnum::STATUS_ELKULDVE);
            $this->doctrine->getManager()->flush();

            return true;
        } catch (Exception $e) {
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

        $to = empty($toName) ? new Address($toEmail) : new Address($toEmail, $toName);

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
            // $params['vars']['webversion'] = $this->router->generate('hgabka_email_message_webversion', ['id' => $queue->getId(), 'hash' => $queue->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

            ['mail' => $mail] = $this->mailBuilder->createMessageMail($message, $to, $queue->getLocale(), true, $params, $recType, true, false, $queue);
            /** @var Email $mail */
            $headers = $mail->getHeaders();
            $headers->addTextHeader('Hg-Message-Id', $message->getId());

            if (isset($bounceConfig['account']['address'])) {
                $mail->returnPath($bounceConfig['account']['address']);
            } else {
                if (null === $this->messageReturnPath) {
                    $from = $mail->getFrom();
                    $mail->returnPath(...$from);
                } elseif (\is_string($this->messageReturnPath)) {
                    $mail->returnPath($this->messageReturnPath);
                }
            }

            try {
                $mailer->send($mail);
            } catch (TransportException $e) {
                $this->setError('Sikertelen kuldes', $queue);
                $this->doctrine->getManager()->flush();

                return false;
            }
            $queue->setStatus(QueueStatusEnum::STATUS_ELKULDVE);
            $this->doctrine->getManager()->flush();

            return true;
        } catch (Exception $e) {
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
                $recipients[] = array_merge(['type' => get_class($recType), 'typeParams' => $toData], $recData);
            }
        }

        foreach ($recipients as $recipient) {
            if (!isset($recipient['to'])) {
                continue;
            }

            $to = $this->translateEmailAddress($recipient['to']);
            $locale = $recipient['locale'] ?? null;

            $existing = $this
                ->doctrine
                ->getRepository(MessageQueue::class)
                ->getForMessageAndEmail($message, $to->getAddress())
            ;

            if ($existing) {
                continue;
            }
            $queue = new MessageQueue();
            $queue->setMessage($message);
            $queue->setToEmail($to->getAddress());
            $queue->setToName($to->getName());
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

    public function addEmailMessageToQueue($message, $attachments, $headers = [], $sendAt = null, $campaign = false): ?Email
    {
        if (!$message) {
            return null;
        }

        /** @var Email $message */
        $queue = new EmailQueue();
        $queue->setSendAt($sendAt);
        $queue->setFrom(serialize($message->getFrom()));
        $queue->setTo(serialize($message->getTo()));
        $queue->setCc(serialize($message->getCc()));
        $queue->setBcc(serialize($message->getBcc()));
        $queue->setSubject($message->getSubject());
        $queue->setContentText($message->getTextBody());
        $queue->setContentHtml($message->getHtmlBody());
        $queue->setHeaders($headers);

        $returnPath = $message->getReturnPath();
        $queue->setReturnPath(empty($returnPath) ? null : serialize($returnPath));

        $r = new \ReflectionProperty(Email::class, 'attachments');
        $r->setAccessible(true);
        $atts = $r->getValue($message);
        $queueAtts = [];
        if (!empty($atts)) {
            foreach ($atts as $att) {
                if ($att['inline']) {
                    $queueAtts[] = $att;
                }
            }
        }
        $queue->setEmbeds($queueAtts);

        if ($campaign instanceof EmailCampaign) {
            $queue->setCampaign($campaign);
        }

        $em = $this->doctrine->getManager();
        $em->persist($queue);
        $em->flush();

        foreach ($attachments as $attachment) {
            $newAttachment = new Attachment();
            $newAttachment->setType(get_class($queue));
            $newAttachment->setOwnerId($queue->getId());
            /** @var Media $media */
            $media = $attachment->getMedia();
            $name = $media->translate($this->hgabkaUtils->getCurrentLocale())->getName();
            $newAttachment->setFilename(empty($name) ? $media->getOriginalFilename() : $name);
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
     *
     * @return array
     */
    public function sendEmails(?int $limit = null): array
    {
        if (empty($limit)) {
            $limit = $this->sendLimit;
        }

        $this->log('Uzenetek kuldese (limit: ' . $limit . ')');

        $count = $sent = $fail = 0;

        $queueRepo = $this->doctrine->getRepository(EmailQueue::class);
        $errorQueues = $queueRepo->getErrorQueuesForSend($limit);

        foreach ($errorQueues as $queue) {
            $to = $this->translateEmailAddress(unserialize($queue->getTo()));
            $email = $to->getAddress();

            if ($this->sendEmailQueue($queue)) {
                $this->log('Sikertelen kuldes ujra. Email kuldese sikeres. Email: ' . $email);
                $this->doctrine->getManager()->remove($queue);
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
            $to = $this->translateEmailAddress(unserialize($queue->getTo()));
            $email = $to->getAddress();

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

    public function translateEmailAddress($address): Address
    {
        return $this->recipientManager->translateEmailAddress($address);
    }
}
