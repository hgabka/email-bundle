<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Form\Type\MessageRecipientFormType;
use Hgabka\EmailBundle\Form\Type\TemplateRecipientFormType;
use Hgabka\EmailBundle\Model\EmailTemplateRecipientTypeInterface;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\EmailBundle\Model\MessageRecipientTypeInterface;
use Hgabka\EmailBundle\Model\RecipientTypeInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RecipientManager
{
    const RECIPIENT_TYPE_TO = 'to';
    const RECIPIENT_TYPE_CC = 'cc';
    const RECIPIENT_TYPE_BCC = 'bcc';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var array|EmailTemplateRecipientTypeInterface[] */
    protected $templateRecipientTypes;

    /** @var array|MessageRecipientTypeInterface[] */
    protected $messageRecipientTypes;

    /** @var TemplateTypeManager */
    protected $templateTypeManager;

    /**
     * RecipientManager constructor.
     *
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     */
    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator, FormFactoryInterface $formFactory, TemplateTypeManager $templateTypeManager)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->formFactory = $formFactory;
        $this->templateTypeManager = $templateTypeManager;
    }

    /**
     * @param EmailTemplateRecipientTypeInterface $type
     * @param                                     $alias
     */
    public function addTemplateRecipientType(EmailTemplateRecipientTypeInterface $type)
    {
        $alias = \get_class($type);

        $this->templateRecipientTypes[$alias] = $type;
        uasort($this->templateRecipientTypes, function ($type1, $type2) {
            $p1 = null === $type1->getPriority() ? PHP_INT_MAX : $type1->getPriority();
            $p2 = null === $type2->getPriority() ? PHP_INT_MAX : $type2->getPriority();

            return $p1 <=> $p2;
        });
    }

    /**
     * @param MessageRecipientTypeInterface $type
     * @param                               $alias
     */
    public function addMessageRecipientType(MessageRecipientTypeInterface $type)
    {
        $alias = \get_class($type);

        $this->messageRecipientTypes[$alias] = $type;
        uasort($this->messageRecipientTypes, function ($type1, $type2) {
            $p1 = null === $type1->getPriority() ? PHP_INT_MAX : $type1->getPriority();
            $p2 = null === $type2->getPriority() ? PHP_INT_MAX : $type2->getPriority();

            return $p1 <=> $p2;
        });
    }

    /**
     * @param null|string $type
     *
     * @return null|EmailTemplateRecipientTypeInterface|mixed
     */
    public function getTemplateRecipientType(string $type = null)
    {
        return !empty($type) ? ($this->templateRecipientTypes[$type] ?? null) : null;
    }

    /**
     * @param null|string $type
     *
     * @return null|MessageRecipientTypeInterface|mixed
     */
    public function getMessageRecipientType(string $type = null)
    {
        return !empty($type) ? ($this->messageRecipientTypes[$type] ?? null) : null;
    }

    /**
     * @param string $type
     * @param bool   $removable
     * @param mixed  $name
     *
     * @return null|\Symfony\Component\Form\FormBuilderInterface
     */
    public function createTemplateRecipientTypeFormBuilder($name, string $type, $removable = true)
    {
        $type = clone $this->getTemplateRecipientType($type);

        $params = $type->getParams();
        $params['type'] = \get_class($type);

        if ($type) {
            $builder = $this->formFactory->createNamedBuilder($name, TemplateRecipientFormType::class, $params, [
                'removable' => $removable,
                'recipient_type' => $type,
            ]);
            $type->addFormFields($builder);

            return $builder;
        }

        return null;
    }

    public function createMessageRecipientTypeFormBuilder($name, string $type)
    {
        $type = clone $this->getMessageRecipientType($type);

        $params = $type->getParams();
        $params['type'] = \get_class($type);

        if ($type) {
            $builder = $this->formFactory->createNamedBuilder($name, MessageRecipientFormType::class, $params, [
                'recipient_type' => $type,
            ]);
            $type->addFormFields($builder);

            return $builder;
        }

        return null;
    }

    /**
     * @return array
     */
    public function getTemplateRecipientTypeChoices()
    {
        $choices = [];
        foreach ($this->templateRecipientTypes as $class => $type) {
            if ($type->isPublic()) {
                $choices[$this->translator->trans($type->getName())] = $class;
            }
        }

        return $choices;
    }

    /**
     * @return array
     */
    public function getMessageRecipientTypeChoices()
    {
        $choices = [];
        foreach ($this->messageRecipientTypes as $class => $type) {
            if ($type->isPublic()) {
                $choices[$this->translator->trans($type->getName())] = $class;
            }
        }

        return $choices;
    }

    /**
     * @param EmailTemplate              $template
     * @param EmailTemplateTypeInterface $templateType
     *
     * @return array|mixed
     */
    public function getToDataByTemplate(EmailTemplate $template)
    {
        $toData = [];
        $templateType = $this->templateTypeManager->getTemplateType($template->getType());
        if (!empty($templateType->getDefaultRecipients())) {
            $defRec = $templateType->getDefaultRecipients();
            if (isset($defRec['type'])) {
                $defRec = [$defRec];
            }
            foreach ($defRec as $defRecData) {
                $defRecType = $this->getTemplateRecipientType($defRecData['type']);
                $params = $defRecData['params'] ?? [];
                if (!empty($template->getToData())) {
                    foreach ($template->getToData() as $td) {
                        if ($td['type'] === $defRecType) {
                            $params = array_merge($params, $td);
                        }
                    }
                }
                unset($params['type']);
                $defRecType->setParams($params);
                $toData[] = $defRecType;
            }
        } else {
            $toData = $template->getToData();
        }

        return $toData;
    }

    /**
     * @param                            $sendParams
     * @param EmailTemplateTypeInterface $templateType
     * @param                            $defaultTo
     * @param mixed                      $locale
     *
     * @return null|array|bool|mixed
     */
    public function getParamTos($sendParams, EmailTemplate $template, $locale, $defaultTo)
    {
        $templateType = $this->templateTypeManager->getTemplateType($template->getType());
        $toData = [];
        if (!empty($sendParams['to'])) {
            $toData = $sendParams['to'];
        } else {
            if (!$templateType->isToEditable()) {
                throw new \InvalidArgumentException('The template type '.\get_class($templateType).' has no recipient set. Provide recipient for the email.');
            }

            $toData = $this->getToDataByTemplate($template);
        }

        return $this->getTosByData($toData, $locale, $defaultTo);
    }

    /**
     * @param $ccData
     * @param $locale
     * @param mixed $defaultTo
     *
     * @return array
     */
    public function composeCc($ccData, $locale, $defaultTo)
    {
        $paramCc = [];
        if (empty($ccData)) {
            return $paramCc;
        }

        $ccData = $this->getTosByData($ccData, $locale, $defaultTo);
        foreach ($ccData as $ccRow) {
            $paramTo = $ccRow['to'];
            $to = $this->translateEmailAddress($paramTo);

            $toName = \is_array($to) ? current($to) : null;
            $toEmail = \is_array($to) ? key($to) : $to;

            $paramCc[] = $toName ? [$toEmail => $toName] : $toEmail;
        }

        return $paramCc;
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
        if (\is_string($address) || ((!isset($address['name']) || 0 === \strlen($address['name'])) && (!isset($address['email']) || 0 === \strlen($address['email'])))) {
            return $address;
        }

        if (isset($address['name']) && \strlen($address['name'])) {
            return [$address['email'] => $address['name']];
        }

        return $address['email'];
    }

    protected function hasTemplateRecipientType($type)
    {
        return array_key_exists($type, $this->templateRecipientTypes);
    }

    protected function hasMessageRecipientType($type)
    {
        return array_key_exists($type, $this->messageRecipientTypes);
    }

    /**
     * @param $toData
     * @param $locale
     *
     * @return null|array|bool|mixed
     */
    protected function getToArray($toData, $locale)
    {
        if (empty($toData)) {
            return false;
        }

        if (\is_string($toData)) {
            return [
                ['to' => $toData, 'locale' => $locale],
            ];
        }

        if ($toData instanceof RecipientTypeInterface) {
            return $toData->getRecipients();
        }

        if (!\is_array($toData)) {
            return false;
        }

        reset($toData);

        if (isset($toData['to'])) {
            return [
                $toData,
            ];
        }

        if (isset($toData['type'])) {
            $type = $this->hasTemplateRecipientType($toData['type']) ? $this->getTemplateRecipientType($toData['type']) : $this->getMessageRecipientType($toData['type']);
            unset($toData['type']);

            $type->setParams($toData);

            return $type->getRecipients();
        }

        if (\is_string(current($toData))) {
            return [
                ['to' => $toData, 'locale' => $locale],
            ];
        }

        return null;
    }

    /**
     * @param $toData
     * @param $locale
     * @param $defaultTo
     *
     * @return null|array|bool|mixed
     */
    protected function getTosByData($toData, $locale, $defaultTo)
    {
        $toArray = $this->getToArray($toData, $locale);

        if (false === $toArray) {
            return $defaultTo;
        }

        if (null !== $toArray) {
            return $toArray;
        }

        $result = [];
        foreach ($toData as $data) {
            $toArray = $this->getToArray($data, $locale);

            if ($toArray) {
                foreach ($toArray as $to) {
                    $result[] = $to;
                }
            }
        }

        return $result;
    }
}
