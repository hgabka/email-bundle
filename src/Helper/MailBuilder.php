<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Event\BuildMessageMailEvent;
use Hgabka\EmailBundle\Event\BuildTemplateMailEvent;
use Hgabka\EmailBundle\Event\MailBuilderEvents;
use Hgabka\EmailBundle\Event\MailRecipientEvent;
use Hgabka\EmailBundle\Event\MailSenderEvent;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use http\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MailBuilder
{
    /** @var Registry */
    protected $doctrine;

    /** @var array */
    protected $config;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ParamSubstituter */
    protected $paramSubstituter;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var RouterInterface */
    protected $router;

    /** @var MediaManager */
    protected $mediaManager;

    /** @var RecipientManager */
    protected $recipientManager;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var TemplateTypeManager */
    protected $templateTypeManager;

    /** @var LayoutManager */
    protected $layoutManager;

    /**
     * MailBuilder constructor.
     *
     * @param Registry            $doctrine
     * @param RequestStack        $requestStack
     * @param ParamSubstituter    $paramSubstituter
     * @param TranslatorInterface $translator
     * @param HgabkaUtils         $hgabkaUtils
     * @param RouterInterface     $router
     */
    public function __construct(
        Registry $doctrine,
        RequestStack $requestStack,
        ParamSubstituter $paramSubstituter,
        TranslatorInterface $translator,
        HgabkaUtils $hgabkaUtils,
        RouterInterface $router,
        MediaManager $mediaManager,
        RecipientManager $recipientManager,
        EventDispatcherInterface $eventDispatcher,
        TemplateTypeManager $templateTypeManager,
        LayoutManager $layoutManager
    ) {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
        $this->paramSubstituter = $paramSubstituter;
        $this->translator = $translator;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->router = $router;
        $this->mediaManager = $mediaManager;
        $this->recipientManager = $recipientManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->templateTypeManager = $templateTypeManager;
        $this->layoutManager = $layoutManager;
    }

    /**
     * @return array
     */
    public function getConfig()
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

    public function getDefaultFromName()
    {
        $default = $this->getDefaultFrom();

        return \is_array($default) ? current($default) : null;
    }

    public function getDefaultFromEmail()
    {
        $default = $this->getDefaultFrom();

        return \is_array($default) ? key($default) : $default;
    }

    /**
     * @return array|string
     */
    public function getDefaultFrom()
    {
        $senderData = $this->config['default_sender'];
        $event = new MailSenderEvent($this);
        $event->setSenderData($senderData);

        $this->eventDispatcher->dispatch(MailBuilderEvents::SET_DEFAULT_SENDER, $event);

        return $this->translateEmailAddress($event->getSenderData());
    }

    /**
     * @return array|string
     */
    public function getDefaultTo()
    {
        $recipientData = [];
        $recipientData['to'] = $this->translateEmailAddress($this->config['default_recipient']);
        $recipientData['locale'] = $this->config['default_recipient']['locale'] ?? null;
        $event = new MailRecipientEvent($this);
        $event->setRecipientData($recipientData);

        $this->eventDispatcher->dispatch(MailBuilderEvents::SET_DEFAULT_RECIPIENT, $event);

        $recipientData = $event->getRecipientData();
        $recipientData['locale'] = $recipientData['locale'] ?? null;

        return $recipientData;
    }

    /**
     * email cím formázás.
     *
     * @param array $address
     *
     * @return array|string
     */
    public function translateEmailAddress($address)
    {
        return $this->recipientManager->translateEmailAddress($address);
    }

    /**
     * @param EmailTemplateTypeInterface|string $class
     * @param array                             $parameters
     * @param null                              $locale
     * @param mixed                             $sendParams
     * @param mixed                             $embedImages
     *
     * @return bool|\Swift_Message
     */
    public function createTemplateMessage($class, $parameters = [], $sendParams = [], $locale = null, $embedImages = true)
    {
        if ($class instanceof EmailTemplateTypeInterface) {
            $templateType = $class;
        } elseif (!$templateType = $this->templateTypeManager->getTemplateType($class)) {
            throw new \InvalidArgumentException('Invalid template type: '.$class);
        }

        $template = $this->templateTypeManager->getTemplateEntity($templateType);
        if (!empty($sendParams['from'])) {
            $paramFrom = $sendParams['from'];
        } else {
            if (!$templateType->isSenderEditable()) {
                throw new \InvalidArgumentException('The template type '.\get_class($templateType).' has no sender set. Provide sender for the email.');
            }

            $paramFrom = $this->getFromFromTemplate($template, $this->hgabkaUtils->getCurrentLocale($locale));
        }
        $paramArray = $parameters;
        $templateType->setParameters($paramArray);

        $paramTos = $this->recipientManager->getTemplateParamTos($sendParams, $template, $locale, $this->getDefaultTo());

        if (!empty($sendParams['cc'])) {
            $paramCc = $sendParams['cc'];
        } elseif ($templateType->isCcEditable()) {
            $paramCc = $this->recipientManager->composeCc($template->getCcData(), $locale, $this->getDefaultTo());
        } elseif (!empty($templateType->getDefaultCc())) {
            $ccData = $this->recipientManager->getCcDataByTemplate($template);
            $paramCc = $this->recipientManager->composeCc($ccData, $locale, $this->getDefaultTo());
        }

        if (!empty($sendParams['bcc'])) {
            $paramBcc = $sendParams['bcc'];
        } elseif ($templateType->isBccEditable()) {
            $paramBcc = $this->recipientManager->composeCc($template->getBccData(), $locale, $this->getDefaultTo());
        } elseif (!empty($templateType->getDefaultBcc())) {
            $bccData = $this->recipientManager->getBccDataByTemplate($template);
            $paramBcc = $this->recipientManager->composeCc($bccData, $locale, $this->getDefaultTo());
        }

        if (empty($paramFrom) || empty($paramTos)) {
            return false;
        }
        $messages = [];

        foreach ($paramTos as $paramToRow) {
            $locale = $this->hgabkaUtils->getCurrentLocale($paramToRow['locale'] ?? null);

            $templateType->setLocale($locale);
            $params = [];
            foreach ($templateType->getVariableValues() as $placeholder => $data) {
                $params[$placeholder] = [
                    'value' => $data['value'],
                ];

                if (isset($data['type'])) {
                    $params[$placeholder]['type'] = $data['type'];
                }
            }
            $params = $this->paramSubstituter->normalizeParams($params);
            $paramTo = $paramToRow['to'];
            ['name' => $name, 'email' => $email] = $this->addDefaultParams($paramFrom, $paramTo, $params);

            $subject = $this->paramSubstituter->substituteParams($template->translate($locale)->getSubject(), $params, true);

            $mail = new \Swift_Message($subject);
            $bodyText = $this->paramSubstituter->substituteParams($template->translate($locale)->getContentText(), $params, true);
            $bodyHtml = $this->paramSubstituter->prepareHtml($mail, $template->translate($locale)->getContentHtml(), $params, true, $embedImages);
            $event = new BuildTemplateMailEvent();
            $event
                ->setBuilder($this)
                ->setTemplateType($templateType)
                ->setBody($bodyHtml)
                ->setLocale($locale)
                ->setParams($params)
                ->setParamSubstituter($this->paramSubstituter)
            ;
            $this->eventDispatcher->dispatch(MailBuilderEvents::BUILD_TEMPLATE_MAIL, $event);
            if (!empty($event->getBody())) {
                $bodyHtml = $event->getBody();
            }

            $layout = $template->getLayout();

            $layoutParams = array_merge($params, [
                    'subject' => $subject,
            ]);
            $bodyHtml = $this->layoutManager->applyLayout($bodyHtml, $layout, $locale, $layoutParams, $parameters['layout_file'] ?? null);

            if (\strlen($bodyText) > 0) {
                $mail->addPart($bodyText, 'text/plain');
            }

            if (\strlen($bodyHtml) > 0) {
                $mail->addPart($bodyHtml, 'text/html');
            }

            $attachments = $this->doctrine->getRepository(Attachment::class)->getByTemplate($template, $locale);

            foreach ($attachments as $attachment) {
                /** @var Attachment $attachment */
                $media = $attachment->getMedia();

                if ($media) {
                    $mail->attach($this->createSwiftAttachment($media));
                }
            }

            try {
                $mail->setFrom($this->translateEmailAddress($paramFrom));
                $mail->setTo($this->translateEmailAddress($paramTo));

                if (!empty($paramCc)) {
                    $this->addCcToMail($mail, $paramCc, RecipientManager::RECIPIENT_TYPE_CC);
                }

                if (!empty($paramBcc)) {
                    $this->addCcToMail($mail, $paramBcc, RecipientManager::RECIPIENT_TYPE_BCC);
                }

                if (!empty($parameters['attachments'])) {
                    $attachments = $parameters['attachments'];
                    if (\is_string($attachments)) {
                        $attachments = [$attachments];
                    }

                    foreach ($attachments as $attachment) {
                        if (\is_string($attachment)) {
                            if (!is_file($attachment)) {
                                continue;
                            }
                            $part = \Swift_Attachment::fromPath($attachment);
                        } else {
                            $filename = isset($attachment['path']) ? $attachment['path'] : '';
                            if (!is_file($filename)) {
                                continue;
                            }
                            $part = \Swift_Attachment::fromPath($filename);
                            if (isset($attachment['filename'])) {
                                $part->setFilename($attachment['filename']);
                            }
                            if (isset($attachment['mime'])) {
                                $part->setContentType($attachment['mime']);
                            }
                            if (isset($attachment['disposition'])) {
                                $part->setDisposition($attachment['disposition']);
                            }
                        }

                        $mail->attach($part);
                    }
                }

                $messages[] = ['message' => $mail, 'locale' => $locale];
            } catch (\Exception $e) {
                throw $e;

                return false;
            }
        }

        return $messages;
    }

    public function layoutsEditable()
    {
        return $this->config['editable_layouts'];
    }

    public function embedImages($html, $mail)
    {
        return $this->paramSubstituter->embedImages($html, $mail);
    }

    /**
     * @param Message    $message
     * @param            $to
     * @param null       $locale
     * @param bool       $addCcs
     * @param array      $parameters
     * @param null|mixed $recType
     * @param mixed      $embedImages
     *
     * @return \Swift_Message
     */
    public function createMessageMail(Message $message, $to, $locale = null, $addCcs = true, $parameters = [], $recType = null, $embedImages = true)
    {
        $locale = $this->hgabkaUtils->getCurrentLocale($locale);
        $params = [];
        $paramFrom = $this->getFromFromMessage($message, $locale);

        ['name' => $name, 'email' => $email] = $this->addDefaultParams($paramFrom, $to, $params);

        /*        $subscriber = $this->getSubscriberRepository()->findOneBy(['email' => $params['email']]);

                $unsubscribeUrl = $this->router->generate('hg_email_message_unsubscribe', ['token' => $subscriber ? $subscriber->getToken() : 'XXX', '_locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL);
                $unsubscribeLink = '<a href="'.$unsubscribeUrl.'">'.$this->translator->trans('hg_email.message_unsubscribe_default_text').'</a>';
                $params['unsubscribe'] = $unsubscribeUrl;
                $params['unsubscribe_link'] = $unsubscribeLink;*/

        $vars = $parameters['vars'] ?? [];
        foreach ($vars as $key => $value) {
            $params[$key] = $value;
        }

        $subject = $this->paramSubstituter->substituteParams($message->translate($locale)->getSubject(), $params);
        $mail = new \Swift_Message($subject);

        $bodyText = $this->paramSubstituter->substituteParams($message->translate($locale)->getContentText(), $params);
        $bodyHtml = $this->paramSubstituter->prepareHtml($mail, $message->translate($locale)->getContentHtml(), $params, false, $embedImages);

        $event = new BuildMessageMailEvent();
        $event
            ->setBuilder($this)
            ->setParams($parameters['params'] ?? [])
            ->setRecipientType($recType)
            ->setMessage($message)
            ->setBody($bodyHtml)
            ->setLocale($locale)
        ;

        $this->eventDispatcher->dispatch(MailBuilderEvents::BUILD_MESSAGE_MAIL, $event);
        if (!empty($event->getBody())) {
            $bodyHtml = $event->getBody();
        }

        $layout = $message->getLayout();
        $layoutParams = array_merge($params ?? [], [
                'subject' => $subject,
        ]);

        $bodyHtml = $this->layoutManager->applyLayout($bodyHtml, $layout, $locale, $layoutParams);

        if (\strlen($bodyText) > 0) {
            $mail->addPart($bodyText, 'text/plain');
        }

        if (\strlen($bodyHtml) > 0) {
            $mail->addPart($bodyHtml, 'text/html');
        }

        $attachments = $this
            ->doctrine
            ->getRepository(Attachment::class)
            ->getByMessage($message, $locale)
        ;

        foreach ($attachments as $attachment) {
            /** @var Attachment $attachment */
            $media = $attachment->getMedia();

            if ($media) {
                $mail->attach($this->createSwiftAttachment($media));
            }
        }

        $mail
            ->setSubject($subject)
            ->setTo($to)
        ;

        $mail->setFrom($paramFrom);
        if (!empty($params['unsubscribe_url'])) {
            $mail->getHeaders()->addTextHeader('List-Unsubscribe', '<'.$params['unsubscribe_url'].'>');
        }


        if ($addCcs) {
            $cc = $message->getCcData();

            if (!empty($cc)) {
                foreach ($this->getTos($cc) as $oneCcData) {
                    if (!isset($oneCcData['to'])) {
                        continue;
                    }
                    $oneCc = $oneCcData['to'];

                    if (\is_array($oneCc)) {
                        $mail->addCc(key($oneCc), current($oneCc));
                    } else {
                        $mail->addCc($oneCc);
                    }
                }
            }

            $bcc = $message->getBccData();

            if (!empty($bcc)) {
                foreach ($this->getTos($bcc) as $oneBccData) {
                    if (!isset($oneBccData['to'])) {
                        continue;
                    }
                    $oneBcc = $oneBccData['to'];
                    if (\is_array($oneBcc)) {
                        $mail->addCc(key($oneBcc), current($oneBcc));
                    } else {
                        $mail->addCc($oneBcc);
                    }
                }
            }
        }

        return ['bodyHtml' => $bodyHtml, 'mail' => $mail];
    }

    /**
     * @param $media
     *
     * @return bool|string
     */
    public function getMediaContent($media)
    {
        return $this->mediaManager->getMediaContent($media);
    }

    public function getMessageVars(Message $message = null)
    {
        $vars = $this->getFromToParams();
        $vars[$this->translator->trans('hg_email.variables.labels.webversion')] = $this->translateDefaultVariable('hg_email.variables.webversion');
        $messageVars = $message ? $this->getMessageVariablesByToData($message->getToData()) : [];

        foreach ($messageVars as $placeholder => $varData) {
            $vars[$this->translator->trans($varData['label'])] = $placeholder;
        }

        return $vars;
    }

    public function getMessageVariablesByToData($toData)
    {
        if (empty($toData)) {
            return [];
        }

        $vars = [];
        foreach ($toData as $recipientTypeData) {
            if (empty($recipientTypeData['type']) || !($recType = $this->recipientManager->getMessageRecipientType($recipientTypeData['type']))) {
                continue;
            }

            $vars = array_merge($vars, $recType->getMessageVariables());
        }

        return $vars;
    }

    public function getFromToParams()
    {
        $toNameLabel = $this->translateDefaultVariable('hg_email.variables.to').'_'.$this->translateDefaultVariable('hg_email.variables.name');
        $toEmailLabel = $this->translateDefaultVariable('hg_email.variables.to').'_'.$this->translateDefaultVariable('hg_email.variables.email');

        $fromNameLabel = $this->translateDefaultVariable('hg_email.variables.from').'_'.$this->translateDefaultVariable('hg_email.variables.name');
        $fromEmailLabel = $this->translateDefaultVariable('hg_email.variables.from').'_'.$this->translateDefaultVariable('hg_email.variables.email');

        return [
            $this->translator->trans('hg_email.variables.labels.to_email') => $toEmailLabel,
            $this->translator->trans('hg_email.variables.labels.to_name') => $toNameLabel,
            $this->translator->trans('hg_email.variables.labels.from_email') => $fromEmailLabel,
            $this->translator->trans('hg_email.variables.labels.from_name') => $fromNameLabel,
        ];
    }

    public function isMessageCcEditable()
    {
        return $this->config['message_with_cc'] ?? false;
    }

    public function isMessageBccEditable()
    {
        return $this->config['message_with_bcc'] ?? false;
    }

    public function getFromFromMessage(Message $message, $locale = null)
    {
        $default = $this->getDefaultFrom();
        $defaultName = $this->getDefaultFromName();
        $defaultEmail = \is_array($default) ? key($default) : $default;
        $name = ($message ? $message->getFromName($locale) : null) ?? ($defaultName ?? null);
        $email = ($message ? $message->getFromEmail($locale) : null) ?? $defaultEmail;

        return empty($name) ? $email : [$email => $name];
    }

    public function translateDefaultVariable($code)
    {
        return $this->translator->trans($code, [], 'messages', 'en');
    }

    public function getTemplatetypeEntity($typeOrClass)
    {
        if (\is_string($typeOrClass)) {
            $type = $this->templateTypeManager->getTemplateType($typeOrClass);
        } else {
            $type = $typeOrClass;
        }

        if (!$type instanceof  EmailTemplateTypeInterface) {
            throw new InvalidArgumentException('Invalid template type: '.$typeOrClass);
        }

        return $this->templateTypeManager->getTemplateEntity($type);
    }

    protected function addCcToMail($mail, $paramCc, $type)
    {
        $method = RecipientManager::RECIPIENT_TYPE_BCC === $type ? 'Bcc' : 'Cc';
        if (\is_string($paramCc)) {
            $mail->{'set'.$method}($paramCc);

            return;
        }

        if (\is_array($paramCc)) {
            reset($paramCc);
            if (is_numeric(key($paramCc))) {
                $mail->{'set'.$method}([]);
                foreach ($paramCc as $cc) {
                    if (\is_array($cc)) {
                        $mail->{'add'.$method}(key($cc), current($cc));
                    } else {
                        $mail->{'add'.$method}($cc);
                    }
                }

                return;
            }
        }

        $mail->{'set'.$method}($paramCc);
    }

    protected function getFromFromTemplate(EmailTemplate $template = null, $locale = null)
    {
        $default = $this->getDefaultFrom();
        $defaultName = $this->getDefaultFromName();
        $defaultEmail = \is_array($default) ? key($default) : $default;
        $name = ($template ? $template->getFromName($locale) : null) ?? ($defaultName ?? null);
        $email = ($template ? $template->getFromEmail($locale) : null) ?? $defaultEmail;

        return empty($name) ? $email : [$email => $name];
    }

    protected function addDefaultParams($paramFrom, $paramTo, &$params)
    {
        $to = $this->translateEmailAddress($paramTo);
        $from = $this->translateEmailAddress($paramFrom);

        $toName = \is_array($to) ? current($to) : $to;
        $toEmail = \is_array($to) ? key($to) : $to;

        $fromName = \is_array($from) ? current($from) : $from;
        $fromEmail = \is_array($from) ? key($from) : $from;

        foreach (array_combine(
            array_values($this->getFromToParams()),
            [
                         $toEmail,
                         $toName,
                         $fromEmail,
                         $fromName,
                     ]
                 ) as $fromToParamKey => $fromToParamValue) {
            $params[$fromToParamKey] = $fromToParamValue;
        }

        return [
            'name' => \is_array($to) ? current($to) : '',
            'email' => $toEmail,
        ];
    }

    protected function createSwiftAttachment(Media $media)
    {
        return (new \Swift_Attachment())
            ->setBody($this->getMediaContent($media))
            ->setFilename($media->getOriginalFilename())
            ->setContentType($media->getContentType())
            ->setSize($this->mediaManager->getMediaSize($media))
        ;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getSubscriberRepository()
    {
        return $this->doctrine->getRepository(MessageSubscriber::class);
    }
}
