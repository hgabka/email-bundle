<?php

namespace Hgabka\KunstmaanEmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\KunstmaanEmailBundle\Entity\AbstractQueue;
use Hgabka\KunstmaanEmailBundle\Entity\Attachment;
use Hgabka\KunstmaanEmailBundle\Entity\MessageQueue;
use Hgabka\KunstmaanEmailBundle\Enum\QueueStatusEnum;
use Hgabka\KunstmaanEmailBundle\Logger\MessageLogger;
use Kunstmaan\MediaBundle\Entity\Media;

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

    public function __construct(Registry $doctrine, \Swift_Mailer $mailer, MessageLogger $logger, array $bounceConfig, int $maxRetries, int $sendLimit, bool $loggingEnabled, int $deleteSentMessagesAfter)
    {
        $this->doctrine = $doctrine;
        $this->mailer = $mailer;
        $this->bounceConfig = $bounceConfig;
        $this->maxRetries = $maxRetries;
        $this->sendLimit = $sendLimit;
        $this->logger = $logger;
        $this->loggingEnabled = $loggingEnabled;
        $this->deleteSentMessagesAfter = $deleteSentMessagesAfter;
    }

    /**
     * @param MailBuilder $mailBuilder
     *
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
     * @return MailBuilder
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
            $contentHtml = $queue->getContentHtml();

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
                        \Swift_Attachment::newInstance($content, $attachment->getFilename(), $attachment->getContentType())
                    );
                }
            }

            $headers = $message->getHeaders();
            $headers->addTextHeader('Hg-Email-Id', $queue->getId());

            if (isset($this->bounceConfig['account']['address'])) {
                $message->setReturnPath($this->bounceConfig['account']['address']);
            }

            if ($this->mailer->send($message) < 1) {
                $this->setError('Sikertelen küldés', $queue);
                $this->doctrine->getManager()->flush();

                return true;
            }
            $queue->setStatus(QueueStatusEnum::STATUS_ELKULDVE);
            $this->doctrine->getManager()->flush();

            return false;
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

        if (in_array($queue->getStatus(), [QueueStatusEnum::STATUS_SIKERTELEN, QueueStatusEnum::STATUS_VISSZAPATTANT], true)) {
            return false;
        }

        $toName = $queue->getToName();
        $toEmail = $queue->getToEmail();

        $to = empty($toName) ? $toEmail : [$toEmail => $toName];

        $fromName = $message->getFromName();
        $fromEmail = $message->getFromEmail();

        $from = empty($fromName) ? $fromEmail : [$fromEmail => $fromName];
        $bounceConfig = $this->bounceConfig;

        try {
            $params = $queue->getParameters();
            $message = $this->mailBuilder->createMessageMail($message, $to, $queue->getLocale(), true, unserialize($params));
            $headers = $message->getHeaders();
            $headers->addTextHeader('Hg-Message-Id', $message->getId());

            if (isset($bounceConfig['account']['address'])) {
                $message->setReturnPath($bounceConfig['account']['address']);
            }

            if ($mailer->send($message) < 1) {
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
        return $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:Attachment')->getByQueue($queue);
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

    public function addMessageToQueue($message, $recipients)
    {
        $this->deleteMessageFromQueue($message);

        if (!$message) {
            return false;
        }

        foreach ($recipients as $recipient) {
            if (!isset($recipient['to'])) {
                continue;
            }

            $to = $recipient['to'];
            $culture = isset($recipient['culture']) ? $recipient['culture'] : sfConfig::get('sf_default_culture');

            $queue = new MessageQueue();
            $queue->setMessage($message);
            if (is_array($to)) {
                $queue->setToEmail(key($to));
                $queue->setToName(current($to));
            } else {
                $queue->setToEmail($to);
            }

            $queue->setLocale($culture);
            $queue->setParameters(serialize($recipient));
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

        foreach ($attachments as $attachment) {
            $newAttachment = new Attachment();
            $newAttachment->setType(get_class($queue));
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
     *
     * @return array
     */
    public function sendEmails($limit = null): array
    {
        if (empty($limit)) {
            $limit = $this->sendLimit;
        }

        $this->log('Uzenetek kuldese (limit: '.$limit.')');

        $count = $sent = $fail = 0;

        $queueRepo = $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:EmailQueue');
        $errorQueues = $queueRepo->getErrorQueuesForSend($limit);

        foreach ($errorQueues as $queue) {
            ++$count;
            $to = unserialize($queue->getTo());

            $email = is_array($to) ? key($to) : $to;

            if ($this->sendEmailQueue($queue)) {
                $this->log('Sikertelen kuldes ujra. Email kuldese sikeres. Email: '.$email);
                $this->doctrine->getManager()->remove($queue);
                ++$sent;
            } else {
                $this->log('Sikertelen kuldes ujra. Email kuldes sikertelen. Email: '.$email.' Hiba: '.$queue->getLastError());
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

            $email = is_array($to) ? key($to) : $to;
            if ($this->send($queue)) {
                $this->log('Email kuldese sikeres. Email: '.$email);

                $days = $this->config['delete_sent_messages_after'];

                if (empty($days)) {
                    $queue->delete();
                }
                ++$sent;
            } else {
                $this->log('Email kuldes sikertelen. Email: '.$email.' Hiba: '.$queue->getLastError());
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
     *
     * @return array
     */
    public function sendMessages($limit = null): array
    {
        if (empty($limit)) {
            $limit = $this->sendLimit;
        }

        $this->log('Uzenetek kuldese (limit: '.$limit.')');

        $count = $sent = $fail = 0;

        $queueRepo = $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:MessageQueue');
        $errorQueues = $queueRepo->getErrorQueuesForSend($limit);

        foreach ($errorQueues as $queue) {
            ++$count;
            $email = $queue->getToEmail();

            if ($this->sendMessageQueue($queue)) {
                $this->log('Sikertelen kuldes ujra. Email kuldese sikeres. Email: '.$email);
                ++$sent;
            } else {
                $this->log('Sikertelen kuldes ujra. Email kuldes sikertelen. Email: '.$email.' Hiba: '.$this->getLastError());
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
                $this->log('Email kuldese sikeres. Email: '.$email);
                ++$sent;
            } else {
                $this->log('Email kuldes sikertelen. Email: '.$email.' Hiba: '.$queue->getLastError());
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

        $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:MessageQueue')->deleteMessageFromQueue($message);
    }

    public function deleteEmailFromQueue($email)
    {
        $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:MessageQueue')->deleteEmailFromQueue($email);
    }

    public function deleteEmailFromEmailQueue($email, EmailCampaign $campaign = null, $withFlush = true)
    {
        $em = $this->doctrine->getManager();

        $queues = $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:EmailQueue')->getQueues($campaign);
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
        return $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:MessageQueue')->clearQueue($this->deleteSentMessagesAfter);
    }
}
