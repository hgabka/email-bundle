<?php

namespace Hgabka\EmailBundle\Model;

use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractRecipientType implements RecipientTypeInterface
{
    /** @var array */
    protected $params;

    /** @var array */
    protected $staticParams;

    protected $recipients;

    /** @var RecipientManager */
    protected $manager;

    public function getParams()
    {
        return $this->params;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    public function setManager(RecipientManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return mixed
     */
    public function getRecipients()
    {
        if (null === $this->recipients) {
            $recipients = $this->calcRecipients();
            if (!empty($recipients)) {
                if (!is_array($recipients) || !is_int(key($recipients))) {
                    if (isset($recipients['to'])) {
                        return [$recipients];
                    }

                    $recipients = [['to' => $recipients, 'locale' => null]];
                }

                foreach ($recipients as &$recipient) {
                    if (!array_key_exists('to', $recipient)) {
                        $recipient = ['to' => $recipient, 'locale' => null];
                    }
                }
            }

            $this->recipients = $recipients;
        }

        return $this->recipients;
    }

    public function getRecipientCount()
    {
        $recipients = $this->getRecipients();

        return !is_array($recipients) ? 0 : count($recipients);
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
        return null;
    }

    public function getStaticParams()
    {
        return [];
    }

    /**
     * @param array $staticParams
     *
     * @return AbstractRecipientType
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

    abstract protected function calcRecipients();

    protected function getRecipientDisplay($recipient)
    {
        if (!is_array($recipient)) {
            return $recipient;
        }

        if (array_key_exists('to', $recipient)) {
            $recipient = $recipient['to'];
            if (!is_array($recipient)) {
                return $recipient;
            }
        }

        return current($recipient).' ('.key($recipient).')';
    }

    protected function getRecipientText($email, $name)
    {
        if (empty($email)) {
            return '';
        }

        if (empty($name)) {
            return $email;
        }

        return $name.' ('.$email.')';
    }
}