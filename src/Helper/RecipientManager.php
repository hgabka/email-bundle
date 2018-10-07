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
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var array|RecipientTypeInterface[] */
    protected $types;

    /**
     * RecipientManager constructor.
     *
     * @param ManagerRegistry     $doctrine
     * @param TranslatorInterface $translator
     */
    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator, FormFactoryInterface $formFactory)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->formFactory = $formFactory;
    }

    /**
     * @param SettingTypeInterface $type
     * @param                      $alias
     */
    public function addType(RecipientTypeInterface $type)
    {
        $alias = get_class($type);
 
        $this->types[$alias] = $type;
        uasort($this->types, function ($type1, $type2) {
            $p1 = null === $type1->getPriority() ? PHP_INT_MAX : $type1->getPriority();
            $p2 = null === $type2->getPriority() ? PHP_INT_MAX : $type2->getPriority();

            return $p1 <=> $p2;
        });
    }

    public function getType(string $type)
    {
        return $this->types[$type] ?? null;
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
        $params['type'] = get_class($type);

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

    public function getToDataByTemplate(EmailTemplate $template, EmailTemplateTypeInterface $templateType)
    {
        $toData = [];
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
}
