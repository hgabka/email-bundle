<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\EmailBundle\Model\RecipientTypeInterface;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
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

    /** @var array|EmailTemplateTypeInterface[] */
    protected $templateTypes = [];

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
        RecipientManager $recipientManager
    ) {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
        $this->paramSubstituter = $paramSubstituter;
        $this->translator = $translator;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->router = $router;
        $this->mediaManager = $mediaManager;
        $this->recipientManager = $recipientManager;
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

        return is_array($default) ? current($default) : null;
    }

    public function getDefaultFromEmail()
    {
        $default = $this->getDefaultFrom();

        return is_array($default) ? key($default) : $default;
    }

    /**
     * @return array|string
     */
    public function getDefaultFrom()
    {
        return $this->translateEmailAddress($this->config['default_sender']);
    }

    /**
     * @return array|string
     */
    public function getDefaultTo()
    {
        return $this->translateEmailAddress($this->config['default_recipient']);
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
        if (is_string($address) || ((!isset($address['name']) || 0 === strlen($address['name'])) && (!isset($address['email']) || 0 === strlen($address['email'])))) {
            return $address;
        }

        if (isset($address['name']) && strlen($address['name'])) {
            return [$address['email'] => $address['name']];
        }

        return $address['email'];
    }

    /**
     * @param EmailTemplateTypeInterface|string $class
     * @param array                             $parameters
     * @param null                              $culture
     * @param mixed                             $sendParams
     *
     * @return bool|\Swift_Message
     */
    public function createTemplateMessage($class, $parameters = [], $sendParams = [], $culture = null)
    {
        if ($class instanceof EmailTemplateTypeInterface) {
            $templateType = $class;
        } elseif (!$templateType = $this->getTemplateType($class)) {
            throw new \InvalidArgumentException('Invalid template type: '.$class);
        }

        $template = $this->getTemplateEntity($templateType);
        $paramFrom = empty($sendParams['from']) ? $this->getFromFromTemplate($template) : $sendParams['from'];
        $paramArray = $parameters;

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

        $toData = [];
        if (!empty($sendParams['to'])) {
            $toData = $sendParams['to'];
        } else {
            $toData = $this->recipientManager->getToDataByTemplate($template, $templateType);
        }
        $paramTos = $this->getTosByData($toData, $culture);
        $paramCc = $sendParams['cc'] ?? null;
        $paramBcc = $sendParams['bcc'] ?? null;

        if (empty($paramFrom) || empty($paramTos)) {
            return false;
        }
        $messages = [];

        foreach ($paramTos as $paramToRow) {
            $paramTo = $paramToRow['to'];
            list('name' => $name, 'email' => $email) = $this->addDefaultParams($paramFrom, $paramTo, $params);

            $culture = $this->hgabkaUtils->getCurrentLocale($paramToRow['locale'] ?? null);

            $subject = $this->paramSubstituter->substituteParams($template->translate($culture)->getSubject(), $params, true);

            $bodyText = $this->paramSubstituter->substituteParams($template->translate($culture)->getContentText(), $params, true);
            $bodyHtml = $template->translate($culture)->getContentHtml();
            $mail = new \Swift_Message($subject);

            $layout = $template->getLayout();

            if ($layout && strlen($bodyHtml) > 0) {
                $layoutFile = $this->config['layout_file'];
                if (false === $layoutFile) {
                    $layoutFile = null;
                } elseif (empty($layoutFile)) {
                    $layoutFile = $this->paramSubstituter->getDefaultLayoutPath();
                }

                $bodyHtml = strtr($layout->getDecoratedHtml($culture, $subject, $layoutFile), [
                    '%%tartalom%%' => $bodyHtml,
                    '%%nev%%' => $name,
                    '%%email%%' => $email,
                    '%%host%%' => $this->hgabkaUtils->getSchemeAndHttpHost(),
                ]);
            } elseif (strlen($bodyHtml) > 0 && (false !== $this->config['layout_file'] || !empty($parameters['layout_file']))) {
                $layoutFile = !empty($parameters['layout_file']) || (isset($parameters['layout_file']) && false === $parameters['layout_file']) ? $parameters['layout_file'] : $this->config['layout_file'];

                if (false !== $layoutFile && !is_file($layoutFile)) {
                    $layoutFile = $this->paramSubstituter->getDefaultLayoutPath();
                }

                if (!empty($layoutFile)) {
                    $layoutFile = strtr($layoutFile, ['%culture%' => $culture]);
                    $html = @file_get_contents($layoutFile);
                } else {
                    $html = null;
                }
                if (!empty($html)) {
                    $bodyHtml = $this->applyLayout($html, $subject, $bodyHtml, $name, $email);
                }
            }

            if (strlen($bodyText) > 0) {
                $mail->addPart($bodyText, 'text/plain');
            }

            if (strlen($bodyHtml) > 0) {
                $bodyHtml = $this->paramSubstituter->embedImages($this->paramSubstituter->substituteParams($bodyHtml, $params, true), $mail);
                $mail->addPart($bodyHtml, 'text/html');
            }

            $attachments = $this->doctrine->getRepository(Attachment::class)->getByTemplate($template, $culture);

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
                    $mail->setCc($this->translateEmailAddress($paramCc));
                }

                if (!empty($paramBcc)) {
                    $mail->setBcc($this->translateEmailAddress($paramBcc));
                }

                if (!empty($parameters['attachments'])) {
                    $attachments = $parameters['attachments'];
                    if (is_string($attachments)) {
                        $attachments = [$attachments];
                    }

                    foreach ($attachments as $attachment) {
                        if (is_string($attachment)) {
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
                return false;
            }
        }

        return $messages;
    }

    /**
     * @param string $name
     *
     * @return null|EmailTemplate
     */
    public function getTemplateByName(string $name)
    {
        if (empty($name)) {
            return null;
        }

        return $this->doctrine->getRepository(EmailTemplate::class)->findOneBy(['name' => $name]);
    }

    /**
     * @param $slug
     *
     * @return null|EmailTemplate
     */
    public function getTemplateBySlug(string $slug)
    {
        if (empty($slug)) {
            return null;
        }

        return $this->doctrine->getRepository(EmailTemplate::class)->findOneBy(['slug' => $slug]);
    }

    /**
     * @param $name
     *
     * @return null|EmailTemplate
     */
    public function getTemplate($name)
    {
        $template = $this->getTemplateBySlug($name);

        if (!$template) {
            $template = $this->getTemplateByName($name);
        }

        return $template;
    }

    /**
     * @param Message $message
     * @param         $to
     * @param null    $culture
     * @param bool    $addCcs
     * @param array   $parameters
     *
     * @return \Swift_Message
     */
    public function createMessageMail(Message $message, $to, $culture = null, $addCcs = true, $parameters = [])
    {
        $culture = $this->hgabkaUtils->getCurrentLocale($culture);

        $params = is_array($to) ? ['nev' => current($to), 'email' => key($to)] : ['email' => $to];
        $params['webversion'] = $this->router->generate('hg_email_message_webversion', ['id' => $message->getId(), '_locale' => $culture], UrlGeneratorInterface::ABSOLUTE_URL);

        $subscriber = $this->getSubscriberRepository()->findOneBy(['email' => $params['email']]);

        $unsubscribeUrl = $this->router->generate('hg_email_message_unsubscribe', ['token' => $subscriber ? $subscriber->getToken() : 'XXX', '_locale' => $culture], UrlGeneratorInterface::ABSOLUTE_URL);
        $unsubscribeLink = '<a href="'.$unsubscribeUrl.'">'.$this->translator->trans('hg_email.message_unsubscribe_default_text').'</a>';
        $params['unsubscribe'] = $unsubscribeUrl;
        $params['unsubscribe_link'] = $unsubscribeLink;

        foreach ($parameters as $key => $value) {
            if (!in_array($key, ['to', 'name', 'email', 'webversion', 'unsubscribe', 'unsubscribe_link'], true) && is_string($value)) {
                $params[$key] = $value;
            }
        }

        $subject = $this->paramSubstituter->substituteParams($message->translate($culture)->getSubject(), $params);
        $mail = new \Swift_Message($subject);

        $bodyText = $this->paramSubstituter->substituteParams($message->translate($culture)->getContentText(), $params);
        $bodyHtml = $this->paramSubstituter->substituteParams($this->paramSubstituter->embedImages($message->translate($culture)->getContentHtml(), $mail), $params);

        if ($this->config['auto_append_unsubscribe_link'] && !empty($unsubscribeLink)) {
            $bodyHtml .= '<br /><br />'.$unsubscribeLink;
        }

        $layout = $message->getLayout();

        if ($layout && strlen($bodyHtml) > 0) {
            $bodyHtml = strtr($layout->getDecoratedHtml($culture, $subject), [
                '%%tartalom%%' => $bodyHtml,
                '%%nev%%' => isset($params['nev']) ? $params['nev'] : '',
                '%%email%%' => isset($params['email']) ? $params['email'] : '',
                '%%host%%' => $this->hgabkaUtils->getSchemeAndHttpHost(),
            ]);
        } elseif (strlen($bodyHtml) > 0 && (false !== $this->config['layout_file'] || !empty($parameters['layout_file']))) {
            $layoutFile = !empty($parameters['layout_file']) || (isset($parameters['layout_file']) && false === $parameters['layout_file']) ? $parameters['layout_file'] : $this->config['layout_file'];

            if (false !== $layoutFile && !is_file($layoutFile)) {
                $layoutFile = $this->paramSubstituter->getDefaultLayoutPath();
            }

            if (!empty($layoutFile)) {
                $layoutFile = strtr($layoutFile, ['%culture%' => $culture]);
                $html = @file_get_contents($layoutFile);
            } else {
                $html = null;
            }
            if (!empty($html)) {
                $bodyHtml = $this->applyLayout($html, $subject, $bodyHtml, isset($params['nev']) ? $params['nev'] : '', isset($params['email']) ? $params['email'] : '');
            }
        }

        if (strlen($bodyText) > 0) {
            $mail->addPart($bodyText, 'text/plain');
        }

        if (strlen($bodyHtml) > 0) {
            $mail->addPart($bodyHtml, 'text/html');
        }

        $attachments = $this
            ->doctrine
            ->getRepository(Attachment::class)
            ->getByMessage($message, $culture)
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

                    if (is_array($oneCc)) {
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
                    if (is_array($oneBcc)) {
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

    public function getTemplateVars($template)
    {
        if (!$template instanceof EmailTemplate) {
            $template = $this->getTemplate($template);
        }

        $params = [
            'nev' => 'hg_email.default_param_labels.name',
            'email' => 'hg_email.default_param_labels.email',
        ];

        if ($template && isset($this->config['mail_template_params'][$template->getSlug()])) {
            $params = array_merge($params, $this->config['mail_template_params'][$template->getSlug()]);
        }

        return $this->paramSubstituter->addVarChars($params);
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

    public function addTemplateType(EmailTemplateTypeInterface $templateType)
    {
        $alias = get_class($templateType);       
        $this->templateTypes[$alias] = $templateType;
    }

    public function getTemplateType($class)
    {
        return $this->templateTypes[$class] ?? null;
    }

    public function getTemplateTypeEntities()
    {
        foreach ($this->templateTypes as $class => $type) {
            $this->getTemplateEntity($type);
        }
    }

    /**
     * @return array|EmailTemplateTypeInterface[]
     */
    public function getTemplateTypes()
    {
        return $this->templateTypes;
    }

    /**
     * @return array
     */
    public function getTemplateTypeClasses()
    {
        return array_keys($this->getTemplateTypes());
    }

    public function getTitleByType($class)
    {
        $type = $this->getTemplateType($class);

        return $type ? $type->getTitle() : '';
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

    protected function getToArray($toData, $culture)
    {
        if (empty($toData)) {
            return false;
        }

        if (is_string($toData)) {
            return [
                ['to' => $toData, 'locale' => $culture],
            ];
        }

        if ($toData instanceof RecipientTypeInterface) {
            return $toData->getRecipients();
        }

        if (!is_array($toData)) {
            return false;
        }

        reset($toData);

        if (isset($toData['to'])) {
            return [
                $toData,
            ];
        }

        if (isset($toData['type'])) {
            $type = $this->recipientManager->getType($toData['type']);
            unset($toData['type']);

            $type->setParams($toData);

            return $type->getRecipients();
        }

        if (is_string(current($toData))) {
            return [
                ['to' => $toData, 'locale' => $culture],
            ];
        }

        return null;
    }

    protected function getTosByData($toData, $culture)
    {
        $toArray = $this->getToArray($toData, $culture);

        if (false === $toArray) {
            return [
                ['to' => $this->getDefaultTo(), 'locale' => null],
            ];
        }

        if (null !== $toArray) {
            return $toArray;
        }

        $result = [];
        foreach ($toData as $data) {
            $toArray = $this->getToArray($data, $culture);

            if ($toArray) {
                foreach ($toArray as $to) {
                    $result[] = $to;
                }
            }
        }

        return $result;
    }

    protected function getFromFromTemplate(EmailTemplate $template = null)
    {
        $default = $this->getDefaultFrom();
        $defaultName = $this->getDefaultFromName();
        $defaultEmail = is_array($default) ? key($default) : $default;
        $name = ($template ? $template->getFromName() : null) ?? ($defaultName ?? null);
        $email = ($template ? $template->getFromName() : null) ?? $defaultEmail;

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

        $toName = is_array($to) ? current($to) : $to;
        $toEmail = is_array($to) ? key($to) : $to;

        $fromName = is_array($from) ? current($from) : $from;
        $fromEmail = is_array($from) ? key($from) : $from;

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
            'name' => is_array($to) ? current($to) : '',
            'email' => $toEmail,
        ];
    }

    protected function createSwiftAttachment(Media $media)
    {
        $content = $this->getMediaContent($media);
        $mime = \Swift_Attachment::newInstance($content, $media->getOriginalFilename(), $media->getContentType());
        $mime->setSize($this->mediaManager->getMediaSize($media));

        return $mime;
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

    protected function getTemplateEntity(EmailTemplateTypeInterface $templateType)
    {
        if (empty($templateType->getEntity())) {
            $template = $this->doctrine->getRepository(EmailTemplate::class)->findOneBy(['type' => get_class($templateType)]);
            if (!$template) {
                $template = new EmailTemplate();
                $template
                    ->setType(get_class($templateType));

                foreach ($this->hgabkaUtils->getAvailableLocales() as $locale) {
                    $template->translate($locale)
                             ->setComment($this->translator->trans($templateType->getComment(), [], 'messages', $locale))
                             ->setSubject($this->translator->trans($templateType->getDefaultSubject(), [], 'messages', $locale))
                             ->setFromName($this->translator->trans($templateType->getDefaultFromName(), [], 'messages', $locale))
                             ->setFromEmail($this->translator->trans($templateType->getDefaultFromEmail(), [], 'messages', $locale))
                             ->setContentText($this->translator->trans($templateType->getDefaultTextContent(), [], 'messages', $locale))
                             ->setContentHtml($this->translator->trans($templateType->getDefaultHtmlContent(), [], 'messages', $locale))
                    ;
                }
                $template->setCurrentLocale($this->hgabkaUtils->getCurrentLocale());
                $this->doctrine->getManager()->persist($template);
                $this->doctrine->getManager()->flush();
            }

            $templateType->setEntity($template);
        }

        return $templateType->getEntity();
    }
}
