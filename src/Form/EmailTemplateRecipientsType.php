<?php

namespace Hgabka\EmailBundle\Form;

use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Helper\TemplateTypeManager;
use Hgabka\EmailBundle\Model\EmailTemplateRecipientTypeInterface;
use Hgabka\EmailBundle\Recipient\DefaultEmailTemplateRecipientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateRecipientsType extends AbstractType
{
    /** @var RecipientManager */
    protected $manager;

    /** @var TemplateTypeManager */
    protected $templateTypeManager;

    /**
     * RecipientsType constructor.
     *
     * @param RecipientManager $manager
     */
    public function __construct(RecipientManager $manager, TemplateTypeManager $templateTypeManager)
    {
        $this->manager = $manager;
        $this->templateTypeManager = $templateTypeManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $form = $event->getForm();

                if (!empty($data)) {
                    foreach ($form->all() as $name => $field) {
                        $form->remove($name);
                    }
                }

                if (0 === \count($form)) {
                    $templateType = $this->templateTypeManager->getTemplateType($options['template_type']);

                    if ($templateType) {
                        if (empty($data)) {
                            if (RecipientManager::RECIPIENT_TYPE_TO === $options['recipients_type'] && !empty($templateType->getDefaultRecipients())) {
                                $recipients = $templateType->getDefaultRecipients();
                                if (array_key_exists('type', $recipients)) {
                                    $recipients = [$recipients];
                                }

                                foreach ($recipients as $recTypeData) {
                                    if (!empty($recTypeData['type'])) {
                                        $this->addRecipientType(null, $form, $recTypeData['type'], $recTypeData['params'] ?? null, RecipientManager::RECIPIENT_TYPE_TO !== $options['recipients_type']);
                                    }
                                }
                            } else {
                                if (RecipientManager::RECIPIENT_TYPE_TO === $options['recipients_type']) {
                                    $this->addRecipientType(null, $form, DefaultEmailTemplateRecipientType::class);
                                }
                            }
                        } else {
                            $removable = RecipientManager::RECIPIENT_TYPE_TO !== $options['recipients_type'] || empty($templateType->getDefaultRecipients());
                            foreach ($data as $name => $typeData) {
                                $this->addRecipientType($name, $form, $typeData['type'], $typeData, $removable);
                            }
                        }
                    }
                }
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $form = $event->getForm();
                foreach ($form->all() as $name => $field) {
                    $form->remove($name);
                }

                $templateType = $this->templateTypeManager->getTemplateType($options['template_type']);

                $removable = RecipientManager::RECIPIENT_TYPE_TO !== $options['recipients_type'] || empty($templateType->getDefaultRecipients());

                if (0 === \count($form) && !empty($data)) {
                    foreach ($data as $name => $typeData) {
                        $this->addRecipientType($name, $form, $typeData['type'] ?? null, $typeData, $removable);
                    }

                    $form->setData($data);
                } elseif (RecipientManager::RECIPIENT_TYPE_TO !== $options['recipients_type'] && $removable && empty($data)) {
                    $form->setData($data);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'recipients_type' => RecipientManager::RECIPIENT_TYPE_TO,
                'template_type' => null,
                'admin' => null,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['admin'] = $options['admin'];
        $view->vars['recipientsType'] = $options['recipients_type'];
        $tplType = $this->templateTypeManager->getTemplateType($options['template_type']);
        $view->vars['add_button'] = RecipientManager::RECIPIENT_TYPE_TO !== $options['recipients_type'] || ($tplType && empty($tplType->getDefaultRecipients()));
    }

    public function getBlockPrefix()
    {
        return 'recipients';
    }

    protected function addRecipientType($name, FormInterface $form, $type, $params = null, $removable = true)
    {
        /** @var EmailTemplateRecipientTypeInterface $recType */
        $recType = $this->manager->getTemplateRecipientType($type);
        if (!$recType) {
            return;
        }
        if (null !== $params) {
            $recType->setParams($params);
        }

        $builder = $this->manager->createTemplateRecipientTypeFormBuilder($name ?? uniqid('rectype_'), $type, $removable);
        if ($builder) {
            $form
                ->add($builder->getForm())
            ;
        }
    }
}
