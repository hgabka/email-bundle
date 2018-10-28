<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Event\MailBuilderEvents;
use Hgabka\EmailBundle\Event\MailRecipientEvent;
use Hgabka\EmailBundle\Event\MailSenderEvent;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
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
        TemplateTypeManager $templateTypeManager
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
     *
     * @return bool|\Swift_Message
     */
    public function createTemplateMessage($class, $parameters = [], $sendParams = [], $locale = null)
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
        $templateType->setLocale($locale);
        if (!empty($paramArray)) {
            $accessor =
                PropertyAccess::createPropertyAccessorBuilder()
                              ->enableExceptionOnInvalidIndex()
                              ->getPropertyAccessor()
            ;
            foreach ($paramArray as $key => $value) {
                $accessor->setValue($templateType, $key, $value);
            }
        }

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
        $paramTos = $this->recipientManager->getParamTos($sendParams, $template, $locale, $this->getDefaultTo());

        if (!empty($sendParams['cc'])) {
            $paramCc = $sendParams['cc'];
        } elseif ($templateType->isCcEditable()) {
            $paramCc = $this->recipientManager->composeCc($template->getCcData(), $locale, $this->getDefaultTo());
        }

        if (!empty($sendParams['bcc'])) {
            $paramBcc = $sendParams['bcc'];
        } elseif ($templateType->isBccEditable()) {
            $paramBcc = $this->recipientManager->composeCc($template->getBccData(), $locale, $this->getDefaultTo());
        }

        if (empty($paramFrom) || empty($paramTos)) {
            return false;
        }
        $messages = [];

        foreach ($paramTos as $paramToRow) {
            $paramTo = $paramToRow['to'];
            ['name' => $name, 'email' => $email] = $this->addDefaultParams($paramFrom, $paramTo, $params);

            $locale = $this->hgabkaUtils->getCurrentLocale($paramToRow['locale'] ?? null);

            $subject = $this->paramSubstituter->substituteParams($template->translate($locale)->getSubject(), $params, true);

            $bodyText = $this->paramSubstituter->substituteParams($template->translate($locale)->getContentText(), $params, true);
            $bodyHtml = $template->translate($locale)->getContentHtml();
            $mail = new \Swift_Message($subject);

            $layout = $template->getLayout();

            if ($layout && \strlen($bodyHtml) > 0) {
                $layoutFile = $this->config['layout_file'];
                if (false === $layoutFile) {
                    $layoutFile = null;
                } elseif (empty($layoutFile)) {
                    $layoutFile = $this->paramSubstituter->getDefaultLayoutPath();
                }

                $bodyHtml = strtr($layout->getDecoratedHtml($locale, $subject, $layoutFile), [
                    '%%tartalom%%' => $bodyHtml,
                    '%%nev%%' => $name,
                    '%%email%%' => $email,
                    '%%host%%' => $this->hgabkaUtils->getSchemeAndHttpHost(),
                ]);
            } elseif (\strlen($bodyHtml) > 0 && (false !== $this->config['layout_file'] || !empty($parameters['layout_file']))) {
                $layoutFile = !empty($parameters['layout_file']) || (isset($parameters['layout_file']) && false === $parameters['layout_file']) ? $parameters['layout_file'] : $this->config['layout_file'];

                if (false !== $layoutFile && !is_file($layoutFile)) {
                    $layoutFile = $this->paramSubstituter->getDefaultLayoutPath();
                }

                if (!empty($layoutFile)) {
                    $layoutFile = strtr($layoutFile, ['%locale%' => $locale]);
                    $html = @file_get_contents($layoutFile);
                } else {
                    $html = null;
                }
                if (!empty($html)) {
                    $bodyHtml = $this->applyLayout($html, $subject, $bodyHtml, $name, $email);
                }
            }

            if (\strlen($bodyText) > 0) {
                $mail->addPart($bodyText, 'text/plain');
            }

            if (\strlen($bodyHtml) > 0) {
                $bodyHtml = $this->paramSubstituter->prepareHtml($mail, $bodyHtml, $params, true);
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

                $messages[] = $mail;
            } catch (\Exception $e) {
                throw $e;

                return false;
            }
        }

        return $messages;
    }

    /**
     * @param Message $message
     * @param         $to
     * @param null    $locale
     * @param bool    $addCcs
     * @param array   $parameters
     *
     * @return \Swift_Message
     */
    public function createMessageMail(Message $message, $to, $locale = null, $addCcs = true, $parameters = [])
    {
        $locale = $this->hgabkaUtils->getCurrentLocale($locale);

        $params = \is_array($to) ? ['nev' => current($to), 'email' => key($to)] : ['email' => $to];
        $params['webversion'] = $this->router->generate('hg_email_message_webversion', ['id' => $message->getId(), '_locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL);

        $subscriber = $this->getSubscriberRepository()->findOneBy(['email' => $params['email']]);

        $unsubscribeUrl = $this->router->generate('hg_email_message_unsubscribe', ['token' => $subscriber ? $subscriber->getToken() : 'XXX', '_locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL);
        $unsubscribeLink = '<a href="'.$unsubscribeUrl.'">'.$this->translator->trans('hg_email.message_unsubscribe_default_text').'</a>';
        $params['unsubscribe'] = $unsubscribeUrl;
        $params['unsubscribe_link'] = $unsubscribeLink;

        foreach ($parameters as $key => $value) {
            if (!\in_array($key, ['to', 'name', 'email', 'webversion', 'unsubscribe', 'unsubscribe_link'], true) && \is_string($value)) {
                $params[$key] = $value;
            }
        }

        $subject = $this->paramSubstituter->substituteParams($message->translate($locale)->getSubject(), $params);
        $mail = new \Swift_Message($subject);

        $bodyText = $this->paramSubstituter->substituteParams($message->translate($locale)->getContentText(), $params);
        $bodyHtml = $this->paramSubstituter->prepareHtml($mail, $message->translate($locale)->getContentHtml(), $params);

        if ($this->config['auto_append_unsubscribe_link'] && !empty($unsubscribeLink)) {
            $bodyHtml .= '<br /><br />'.$unsubscribeLink;
        }

        $layout = $message->getLayout();

        if ($layout && \strlen($bodyHtml) > 0) {
            $bodyHtml = strtr($layout->getDecoratedHtml($locale, $subject), [
                '%%tartalom%%' => $bodyHtml,
                '%%nev%%' => isset($params['nev']) ? $params['nev'] : '',
                '%%email%%' => isset($params['email']) ? $params['email'] : '',
                '%%host%%' => $this->hgabkaUtils->getSchemeAndHttpHost(),
            ]);
        } elseif (\strlen($bodyHtml) > 0 && (false !== $this->config['layout_file'] || !empty($parameters['layout_file']))) {
            $layoutFile = !empty($parameters['layout_file']) || (isset($parameters['layout_file']) && false === $parameters['layout_file']) ? $parameters['layout_file'] : $this->config['layout_file'];

            if (false !== $layoutFile && !is_file($layoutFile)) {
                $layoutFile = $this->paramSubstituter->getDefaultLayoutPath();
            }

            if (!empty($layoutFile)) {
                $layoutFile = strtr($layoutFile, ['%locale%' => $locale]);
                $html = @file_get_contents($layoutFile);
            } else {
                $html = null;
            }
            if (!empty($html)) {
                $bodyHtml = $this->applyLayout($html, $subject, $bodyHtml, isset($params['nev']) ? $params['nev'] : '', isset($params['email']) ? $params['email'] : '');
            }
        }

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

        $name = $message->getFromName();
        $from = empty($name) ? $message->getFromEmail() : [$message->getFromEmail() => $name];
        $mail->setFrom($from);

        if ($addCcs) {
            $cc = $message->getCc();

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

            $bcc = $message->getBcc();

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

        return $mail;
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

    public function getMessageVars()
    {
        $params = [
            'nev' => 'hg_email.default_param_labels.name',
            'email' => 'hg_email.default_param_labels.email',
            'webversion' => 'hg_email.default_param_labels.webversion',
            'unsubscribe' => 'hg_email.default_param_labels.unsubscribe',
            'unsubscribe_link' => 'hg_email.default_param_labels.unsubscribe_link',
        ];

        return $this->paramSubstituter->addVarChars($params);
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

    protected function translateDefaultVariable($code)
    {
        return $this->translator->trans($code, [], 'messages', $this->hgabkaUtils->getCurrentLocale());
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
     * @param $layout
     * @param $subject
     * @param $bodyHtml
     * @param $name
     * @param $email
     *
     * @return string
     */
    protected function applyLayout($layout, $subject, $bodyHtml, $name, $email)
    {
        if (empty($name)) {
            $name = $this->translator->trans($this->config['default_name']);
        }

        return strtr($layout, [
            '%%host%%' => $this->hgabkaUtils->getSchemeAndHttpHost(),
            '%%styles%%' => '',
            '%%title%%' => $subject,
            '%%content%%' => $bodyHtml,
            '%%name%%' => $name,
            '%%email%%' => $email,
        ]);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getSubscriberRepository()
    {
        return $this->doctrine->getRepository(MessageSubscriber::class);
    }
}
