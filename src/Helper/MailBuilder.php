<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Kunstmaan\MediaBundle\Entity\Media;
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
        RouterInterface $router
    ) {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
        $this->paramSubstituter = $paramSubstituter;
        $this->translator = $translator;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->router = $router;
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
     * @param EmailTemplate $template
     * @param array         $parameters
     * @param null          $culture
     *
     * @return bool|\Swift_Message
     */
    public function createTemplateMessage(EmailTemplate $template, $parameters = [], $culture = null)
    {
        $parameters['from'] = empty($parameters['from']) ? $this->getDefaultFrom() : $parameters['from'];
        $parameters['to'] = empty($parameters['to']) ? $this->getDefaultTo() : $parameters['to'];

        if (empty($parameters['from']) || empty($parameters['to'])) {
            return false;
        }
        $params = $this->paramSubstituter->normalizeParams(empty($parameters['params']) ? [] : $parameters['params']);
        $to = $this->translateEmailAddress($parameters['to']);

        $name = is_array($to) ? current($to) : '';
        $email = is_array($to) ? key($to) : $to;
        if (!isset($params['nev'])) {
            $params['nev'] = $name;
        }

        if (!isset($params['email'])) {
            $params['email'] = $email;
        }

        $culture = $this->hgabkaUtils->getCurrentLocale($culture);

        $subject = $this->paramSubstituter->substituteParams($template->translate($culture)->getSubject(), $params, true);

        $mail = new \Swift_Message($subject);

        $bodyText = $this->paramSubstituter->substituteParams($template->translate($culture)->getContentText(), $params, true);
        $bodyHtml = $template->translate($culture)->getContentHtml();

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

        $attachments = $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:Attachment')->getByTemplate($template, $culture);

        foreach ($attachments as $attachment) {
            /** @var Attachment $attachment */
            $media = $attachment->getMedia();

            if ($media) {
                $mail->attach($this->createSwiftAttachment($media));
            }
        }

        try {
            $mail->setFrom($this->translateEmailAddress($parameters['from']));
            $mail->setTo($this->translateEmailAddress($parameters['to']));

            if (!empty($parameters['cc'])) {
                $mail->setCc($this->translateEmailAddress($parameters['cc']));
            }

            if (!empty($parameters['bcc'])) {
                $mail->setBcc($this->translateEmailAddress($parameters['bcc']));
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

            return $mail;
        } catch (\Exception $e) {
            return false;
        }
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

        return $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:EmailTemplate')->findOneBy(['name' => $name]);
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

        return $this->doctrine->getRepository('HgabkaKunstmaanEmailBundle:EmailTemplate')->findOneBy(['slug' => $slug]);
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
     * @param $to
     * @param null  $culture
     * @param bool  $addCcs
     * @param array $parameters
     *
     * @return \Swift_Message
     */
    public function createMessageMail(Message $message, $to, $culture = null, $addCcs = true, $parameters = [])
    {
        $culture = $this->hgabkaUtils->getCurrentLocale($culture);

        $params = is_array($to) ? ['nev' => current($to), 'email' => key($to)] : ['email' => $to];
        $params['webversion'] = $this->router->generate('hgabka_kunstmaan_email_message_webversion', ['id' => $message->getId(), '_locale' => $culture], UrlGeneratorInterface::ABSOLUTE_URL);

        $subscriber = $this->getSubscriberRepository()->findOneBy(['email' => $params['email']]);

        $unsubscribeUrl = $this->router->generate('hgabka_kunstmaan_email_message_unsubscribe', ['token' => $subscriber ? $subscriber->getToken() : 'XXX', '_locale' => $culture], UrlGeneratorInterface::ABSOLUTE_URL);
        $unsubscribeLink = '<a href="'.$unsubscribeUrl.'">'.$this->translator->trans('hgabka_kunstmaan_email.message_unsubscribe_default_text').'</a>';
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
            ->getRepository('HgabkaKunstmaanEmailBundle:Attachment')
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
        return $this->hgabkaUtils->getMediaContent($media);
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

    protected function createSwiftAttachment(Media $media)
    {
        $content = $this->getMediaContent($media);
        $mime = \Swift_Attachment::newInstance($content, $media->getOriginalFilename(), $media->getContentType());
        $mime->setSize($this->kumaUtils->getMediaSize($media));

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
}
