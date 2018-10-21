<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Form\Type\RecipientFormType;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
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

    /** @var array|RecipientTypeInterface[] */
    protected $types;

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
     * @param SettingTypeInterface $type
     * @param                      $alias
     */
    public function addType(RecipientTypeInterface $type)
    {
        $alias = \get_class($type);

        $this->types[$alias] = $type;
        uasort($this->types, function ($type1, $type2) {
            $p1 = null === $type1->getPriority() ? PHP_INT_MAX : $type1->getPriority();
            $p2 = null === $type2->getPriority() ? PHP_INT_MAX : $type2->getPriority();

            return $p1 <=> $p2;
        });
    }

    /**
     * @param null|string $type
     *
     * @return null|mixed|RecipientTypeInterface
     */
    public function getType(string $type = null)
    {
        return !empty($type) ? ($this->types[$type] ?? null) : null;
    }

    /**
     * @param string $type
     * @param bool   $removable
     * @param mixed  $name
     *
     * @return null|\Symfony\Component\Form\FormBuilderInterface
     */
    public function createTypeFormBuilder($name, string $type, $removable = true)
    {
        $type = clone $this->getType($type);

        $params = $type->getParams();
        $params['type'] = \get_class($type);

        if ($type) {
            $builder = $this->formFactory->createNamedBuilder($name, RecipientFormType::class, $params, [
                'removable' => $removable,
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
    public function getTypeChoices()
    {
        $choices = [];
        foreach ($this->types as $class => $type) {
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
                $defRecType = $this->getType($defRecData['type']);
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
     * @param mixed                      $culture
     *
     * @return null|array|bool|mixed
     */
    public function getParamTos($sendParams, EmailTemplate $template, $culture, $defaultTo)
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

        return $this->getTosByData($toData, $culture, $defaultTo);
    }

    /**
     * @param $ccData
     * @param $culture
     * @param mixed $defaultTo
     *
     * @return array
     */
    public function composeCc($ccData, $culture, $defaultTo)
    {
        $paramCc = [];
        if (empty($ccData)) {
            return $paramCc;
        }

        $ccData = $this->getTosByData($ccData, $culture, $defaultTo);
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

    /**
     * @param $toData
     * @param $culture
     *
     * @return null|array|bool|mixed
     */
    protected function getToArray($toData, $culture)
    {
        if (empty($toData)) {
            return false;
        }

        if (\is_string($toData)) {
            return [
                ['to' => $toData, 'locale' => $culture],
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
            $type = $this->getType($toData['type']);
            unset($toData['type']);

            $type->setParams($toData);

            return $type->getRecipients();
        }

        if (\is_string(current($toData))) {
            return [
                ['to' => $toData, 'locale' => $culture],
            ];
        }

        return null;
    }

    /**
     * @param $toData
     * @param $culture
     * @param $defaultTo
     *
     * @return null|array|bool|mixed
     */
    protected function getTosByData($toData, $culture, $defaultTo)
    {
        $toArray = $this->getToArray($toData, $culture);

        if (false === $toArray) {
            return $defaultTo;
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
}
