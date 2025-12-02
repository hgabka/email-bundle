<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\ObjectRepository;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Event\BuildMessageMailEvent;
use Hgabka\EmailBundle\Event\BuildTemplateMailEvent;
use Hgabka\EmailBundle\Event\MailBuilderEvents;
use Hgabka\EmailBundle\Event\MailRecipientEvent;
use Hgabka\EmailBundle\Event\MailSenderEvent;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\EmailBundle\Model\MessageVarInterface;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use http\Exception\InvalidArgumentException;
use function is_string;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** @var MailHelper */
    protected $mailHelper;

    protected $messageVars = [];

    /**
     * MailBuilder constructor.
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
        LayoutManager $layoutManager,
        MailHelper $mailHelper
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
        $this->mailHelper = $mailHelper;
    }

    public function addMessageVar(MessageVarInterface $messageVar, $priority = null)
    {
        $alias = \get_class($messageVar);

        if (null !== $priority) {
            $messageVar->setPriority($priority);
        }

        $this->messageVars[$alias] = $messageVar;
        uasort($this->messageVars, function ($type1, $type2) {
            $p1 = (null === $type1->getPriority() ? 0 : $type1->getPriority());
            $p2 = (null === $type2->getPriority() ? 0 : $type2->getPriority());

            return $p2 <=> $p1;
        });
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function getDefaultFromName(): ?string
    {
        $default = $this->getDefaultFrom();

        return $default->getName();
    }

    public function getDefaultFromEmail(): string
    {
        $default = $this->getDefaultFrom();

        return $default->getAddress();
    }

    /**
     * @return Address
     */
    public function getDefaultFrom()
    {
        $senderData = $this->config['default_sender'];
        $event = new MailSenderEvent($this);
        $event->setSenderData($senderData);

        $this->eventDispatcher->dispatch($event, MailBuilderEvents::SET_DEFAULT_SENDER);

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

        $this->eventDispatcher->dispatch($event, MailBuilderEvents::SET_DEFAULT_RECIPIENT);

        $recipientData = $event->getRecipientData();
        $recipientData['locale'] = $recipientData['locale'] ?? null;

        return $recipientData;
    }

    /**
     * email cím formázás.
     *
     * @param array $address
     *
     * @return Address
     */
    public function translateEmailAddress($address): Address
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
     * @return array|false
     */
    public function createTemplateMessage($class, $parameters = [], $sendParams = [], $locale = null, $embedImages = true)
    {
        if ($class instanceof EmailTemplateTypeInterface) {
            $templateType = $class;
        } elseif (!$templateType = $this->templateTypeManager->getTemplateType($class)) {
            throw new \InvalidArgumentException('Invalid template type: ' . $class);
        }

        $template = $this->templateTypeManager->getTemplateEntity($templateType);

        $paramArray = $parameters;
        $templateType->setParameters($paramArray);

        if (!empty($sendParams['from'])) {
            $paramFrom = $sendParams['from'];
        } else {
            if (!$templateType->isSenderEditable()) {
                if (empty($templateType->getDefaultFromEmail())) {
                    throw new \InvalidArgumentException('The template type ' . \get_class($templateType) . ' has no sender set. Provide sender for the email or return a default email in the getDefaultFromName method of the type.');
                }

                $paramFrom = empty($templateType->getDefaultFromName())
                    ? $templateType->getDefaultFromEmail()
                    : [$templateType->getDefaultFromEmail() => $templateType->getDefaultFromName()]
                ;
            } else {
                $paramFrom = $this->getFromFromTemplate($template, $this->hgabkaUtils->getCurrentLocale($locale));
            }
        }

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

        if (isset($paramTos['to'])) {
            $paramTos = [$paramTos];
        }

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

            $mail = new Email();
            $mail->subject($subject);

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
            $this->eventDispatcher->dispatch($event, MailBuilderEvents::BUILD_TEMPLATE_MAIL);
            if (!empty($event->getBody())) {
                $bodyHtml = $event->getBody();
            }

            if ($this->config['auto_create_text_parts'] && !empty(trim((string) $bodyHtml)) && empty(trim((string) $bodyText))) {
                $bodyText = $this->hgabkaUtils->convertHtml($bodyHtml);
            }

            $layout = $template->getLayout();

            $layoutParams = array_merge($params, [
                    'subject' => $subject,
                    'send_params' => $sendParams,
            ]);
            $bodyHtml = $this->layoutManager->applyLayout($bodyHtml, $layout, $mail, $locale, $layoutParams, $sendParams['layout_file'] ?? null);

            if ('' !== $bodyText) {
                $mail->text($bodyText);
            }

            if ('' !== $bodyHtml) {
                $mail->html($bodyHtml);
            }

            $attachments = $this->doctrine->getRepository(Attachment::class)->getByTemplate($template, $locale);

            foreach ($attachments as $attachment) {
                /** @var Attachment $attachment */
                $media = $attachment->getMedia();

                if ($media) {
                    $path = $this->mediaManager->getMediaPath($media);
                    $name = $media->translate($this->hgabkaUtils->getCurrentLocale())->getName();
                    $mail->attachFromPath($path, empty($name) ? $media->getOriginalFilename() : $name, $media->getContentType());
                }
            }

            try {
                $mail->from($this->translateEmailAddress($paramFrom));
                $mail->to($this->translateEmailAddress($paramTo));

                if (!empty($paramCc)) {
                    $this->addCcToMail($mail, $paramCc, RecipientManager::RECIPIENT_TYPE_CC);
                }

                if (!empty($paramBcc)) {
                    $this->addCcToMail($mail, $paramBcc, RecipientManager::RECIPIENT_TYPE_BCC);
                }

                if (!empty($sendParams['attachments'])) {
                    $attachments = $sendParams['attachments'];
                    if (is_string($attachments)) {
                        $attachments = [$attachments];
                    }

                    foreach ($attachments as $attachment) {
                        if (is_string($attachment)) {
                            if (!is_file($attachment)) {
                                continue;
                            }
                            $mail->attachFromPath($attachment);
                        } elseif (isset($attachment['media']) || isset($attachment['media_id'])) {
                            $media = isset($attachment['media']) && $attachment['media'] instanceof Media
                                ? $attachment['media']
                                : (!empty($attachment['media_id']) ? $this->doctrine->getRepository(Media::class)->find($attachment['media_id']) : null)
                            ;
                            if (!$media instanceof Media) {
                                continue;
                            }

                            $mail->attachFromPath(
                                $this->mediaManager->getMediaPath($media),
                                $attachment['filename'] ?? $media->getOriginalFilename(),
                                $attachment['mime'] ?? $media->getContentType()
                            );
                        } else {
                            $filename = $attachment['path'] ?? '';
                            if (!is_file($filename)) {
                                continue;
                            }

                            $mail->attachFromPath($filename, $attachment['filename'] ?? null, $attachment['mime'] ?? null);
                        }
                    }
                }

                if (!isset($sendParams['return_path'])) {
                    $from = $mail->getFrom();
                    $mail->returnPath(...$from);
                } else {
                    if (is_string($sendParams['return_path'])) {
                        $mail->returnPath(new Address($sendParams['return_path']));
                    } elseif (is_array($sendParams['return_path'])) {
                        $mail->returnPath(new Address(key($sendParams['return_path']), current($sendParams['return_path'])));
                    } elseif ($sendParams['return_path'] instanceof Address) {
                        $mail->returnPath($sendParams['return_path']);
                    }
                }

                if (isset($sendParams['reply_to'])) {
                    $mail->replyTo($sendParams['reply_to']);
                }

                if (isset($sendParams['headers'])) {
                    $this->mailHelper->addHeadersFromArray($mail, $sendParams['headers']);
                }

                $templateType->alterEmail($mail);

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
     * @param mixed      $webversion
     *
     * @return array
     */
    public function createMessageMail(Message $message, $to, $locale = null, $addCcs = true, $parameters = [], $recType = null, $embedImages = true, $webversion = false, ?MessageQueue $queue = null)
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

        foreach ($this->messageVars as $messageVar) {
            $value = $messageVar->getValue($message, $paramFrom, $to, $locale, $queue);

            if (null !== $value) {
                $params[$messageVar->getPlaceholder()] = [
                    'type' => $messageVar->getType(),
                    'value' => $value,
                ];
            }
        }

        $subject = $this->paramSubstituter->substituteParams($message->translate($locale)->getSubject(), $params);
        $mail = new Email();
        $mail->subject($subject);

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

        $this->eventDispatcher->dispatch($event, MailBuilderEvents::BUILD_MESSAGE_MAIL);
        if (!empty($event->getBody())) {
            $bodyHtml = $event->getBody();
        }

        if ($this->config['auto_create_text_parts'] && !empty(trim((string) $bodyHtml)) && empty(trim((string) $bodyText))) {
            $bodyText = $this->hgabkaUtils->convertHtml($bodyHtml);
        }

        $layout = $message->getLayout();
        $layoutParams = array_merge($params ?? [], [
                'subject' => $subject,
        ]);

        $bodyHtml = $this->layoutManager->applyLayout($bodyHtml, $layout, $mail, $locale, $layoutParams, null, $webversion);

        if ('' !== $bodyText) {
            $mail->text($bodyText);
        }

        if ('' !== $bodyHtml) {
            $mail->html($bodyHtml);
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
                $path = $this->mediaManager->getMediaPath($media);
                $name = $media->translate($this->hgabkaUtils->getCurrentLocale())->getName();
                $mail->attachFromPath($path, empty($name) ? $media->getOriginalFilename() : $name, $media->getContentType());
            }
        }

        $mail->to($this->translateEmailAddress($to));

        $mail->from($this->translateEmailAddress($paramFrom));
        if (!empty($params['unsubscribe_url'])) {
            $url = is_string($params['unsubscribe_url']) ? $params['unsubscribe_url'] : ($params['unsubscribe_url']['value'] ?? '');

            if (!empty($url)) {
                $mail->getHeaders()->addTextHeader('List-Unsubscribe', '<' . $url . '>');
            }
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
                        $mail->addCc(new Address(key($oneCc), current($oneCc)));
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
                        $mail->addCc(new Address(key($oneBcc), current($oneBcc)));
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

        foreach ($this->messageVars as $messageVar) {
            $enabled = $messageVar->isEnabled($message);
            if ($enabled) {
                $vars[$messageVar->getLabel()] = $messageVar->getPlaceholder();
            }
        }

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
        $toNameLabel = $this->translateDefaultVariable('hg_email.variables.to') . '_' . $this->translateDefaultVariable('hg_email.variables.name');
        $toEmailLabel = $this->translateDefaultVariable('hg_email.variables.to') . '_' . $this->translateDefaultVariable('hg_email.variables.email');

        $fromNameLabel = $this->translateDefaultVariable('hg_email.variables.from') . '_' . $this->translateDefaultVariable('hg_email.variables.name');
        $fromEmailLabel = $this->translateDefaultVariable('hg_email.variables.from') . '_' . $this->translateDefaultVariable('hg_email.variables.email');

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
        $defaultEmail = $this->getDefaultFromEmail();
        $name = ($message ? $message->getFromName($locale) : null) ?? ($defaultName ?? null);
        $email = ($message ? $message->getFromEmail($locale) : null) ?? $defaultEmail;

        return empty($name) ? new Address($email) : new Address($email, $name);
    }

    public function translateDefaultVariable($code)
    {
        return $this->translator->trans($code, [], 'messages', $this->hgabkaUtils->getDefaultLocale());
    }

    public function getTemplatetypeEntity($typeOrClass)
    {
        if (is_string($typeOrClass)) {
            $type = $this->templateTypeManager->getTemplateType($typeOrClass);
        } else {
            $type = $typeOrClass;
        }

        if (!$type instanceof EmailTemplateTypeInterface) {
            throw new InvalidArgumentException('Invalid template type: ' . $typeOrClass);
        }

        return $this->templateTypeManager->getTemplateEntity($type);
    }

    public function addHeadersFromArray(Email $message, ?array $headers)
    {
        $this->mailHelper->addHeadersFromArray($message, $headers);
    }

    protected function addCcToMail($mail, $paramCc, $type)
    {
        $method = RecipientManager::RECIPIENT_TYPE_BCC === $type ? 'bcc' : 'cc';
        if (is_string($paramCc)) {
            $mail->{$method}(Address::create($paramCc));

            return;
        }

        if (\is_array($paramCc)) {
            reset($paramCc);
            if (is_numeric(key($paramCc))) {
                foreach ($paramCc as $cc) {
                    if (\is_array($cc)) {
                        $mail->{'add' . ucfirst($method)}($this->translateEmailAddress($cc));
                    } else {
                        $mail->{'add' . ucfirst($method)}(Address::create($cc));
                    }
                }

                return;
            }
        }

        $mail->{$method}($this->translateEmailAddress($paramCc));
    }

    protected function getFromFromTemplate(EmailTemplate $template = null, $locale = null)
    {
        $defaultName = $this->getDefaultFromName();
        $defaultEmail = $this->getDefaultFromEmail();

        $name = $template ? $template->getFromName($locale) : null;
        if (empty($name)) {
            $name = empty($defaultName) ? null : $defaultName;
        }

        $email = $template ? $template->getFromEmail($locale) : null;
        if (empty($email)) {
            $email = $defaultEmail;
        }

        return empty($name) ? new Address($email) : new Address($email, $name);
    }

    protected function addDefaultParams($paramFrom, $paramTo, &$params)
    {
        $to = $this->translateEmailAddress($paramTo);
        $from = $this->translateEmailAddress($paramFrom);

        $toName = $to->getName();
        $toEmail = $to->getAddress();

        $fromName = $from->getName();
        $fromEmail = $from->getAddress();

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
            'name' => (string) $to->getName(),
            'email' => $to->getAddress(),
        ];
    }

    protected function getSubscriberRepository(): ?ObjectRepository
    {
        return $this->doctrine->getRepository(MessageSubscriber::class);
    }
}
