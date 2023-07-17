<?php

namespace Hgabka\EmailBundle\Model;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractRecipientType implements RecipientTypeInterface
{
    /** @var MailBuilder */
    protected $builder;

    /** @var array */
    protected $params;

    /** @var array */
    protected $staticParams;

    protected $recipients;

    /** @var RecipientManager */
    protected $manager;

    /** @var TranslatorInterface */
    protected $translator;

    protected $priority;

    #[Required]
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
        $this->recipients = null;
    }

    public function getBuilder(): MailBuilder
    {
        return $this->builder;
    }

    #[Required]
    public function setBuilder(MailBuilder $builder)
    {
        $this->builder = $builder;

        return $this;
    }

    #[Required]
    public function setManager(RecipientManager $manager)
    {
        $this->manager = $manager;
    }

    public function createRecipients()
    {
        $recipients = $this->computeRecipients();
        if (!empty($recipients)) {
            if (!\is_array($recipients) || !\is_int(key($recipients))) {
                if (isset($recipients['to'])) {
                    return [$recipients];
                }

                $recipients = [['to' => $recipients, 'locale' => null]];
            }

            foreach ($recipients as &$recipient) {
                if (!\array_key_exists('to', $recipient)) {
                    $recipient = ['to' => $recipient, 'locale' => null];
                }
            }
        }

        return $recipients;
    }

    public function getRecipients()
    {
        if (null === $this->recipients) {
            $this->recipients = $this->createRecipients();
        }

        return $this->recipients;
    }

    public function getRecipientCount()
    {
        $recipients = $this->getRecipients();

        return !\is_array($recipients) ? 0 : \count($recipients);
    }

    public function addFormFields(FormBuilderInterface $formBuilder)
    {
        return $formBuilder;
    }

    public function isPublic()
    {
        return false;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getStaticParams()
    {
        return [];
    }

    /**
     * @param array $staticParams
     *
     * @return AbstractEmailTemplateRecipientType
     */
    public function setStaticParams($staticParams)
    {
        $this->staticParams = $staticParams;

        return $this;
    }

    public function getStaticParam($name)
    {
        return $this->getStaticParams[$name] ?? null;
    }

    public function getParamDefaults()
    {
        return [];
    }

    public function alterHtmlBody($html, $params, $locale)
    {
        return $html;
    }

    public function getVariableValues($params)
    {
        return [];
    }

    public function getMessageVariables()
    {
        return [];
    }

    public function getFormTemplate()
    {
        return null;
    }

    abstract protected function computeRecipients();

    protected function getRecipientDisplay($recipient)
    {
        if (!\is_array($recipient)) {
            return $recipient;
        }

        if (\array_key_exists('to', $recipient)) {
            $recipient = $recipient['to'];
            if (!\is_array($recipient)) {
                return $recipient;
            }
        }

        return current($recipient) . ' (' . key($recipient) . ')';
    }

    protected function getRecipientText($email, $name)
    {
        if (empty($email)) {
            return '';
        }

        if (empty($name)) {
            return $email;
        }

        return $name . ' (' . $email . ')';
    }
}
