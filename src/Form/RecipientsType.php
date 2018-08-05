<?php

namespace Hgabka\EmailBundle\Form;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Model\RecipientTypeInterface;
use Hgabka\EmailBundle\Recipient\DefaultRecipientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientsType extends AbstractType
{
    /** @var RecipientManager */
    protected $manager;

    /** @var MailBuilder */
    protected $builder;

    /**
     * RecipientsType constructor.
     *
     * @param RecipientManager $manager
     */
    public function __construct(RecipientManager $manager, MailBuilder $builder)
    {
        $this->manager = $manager;
        $this->builder = $builder;
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

                if (0 === count($form)) {
                    $templateType = $this->builder->getTemplateType($options['template_type']);

                    if ($templateType) {
                        if (empty($data)) {
                            if (!empty($templateType->getDefaultRecipients())) {
                                $recipients = $templateType->getDefaultRecipients();
                                if (array_key_exists('type', $recipients)) {
                                    $recipients = [$recipients];
                                }

                                foreach ($recipients as $recTypeData) {
                                    if (!empty($recTypeData['type'])) {
                                        $this->addRecipientType(null, $form, $recTypeData['type'], $recTypeData['params'] ?? null, false);
                                    }
                                }
                            } else {
                                $this->addRecipientType(null, $form, DefaultRecipientType::class);
                            }
                        } else {
                            $removable = empty($templateType->getDefaultRecipients());
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
                $templateType = $this->builder->getTemplateType($options['template_type']);

                $removable = empty($templateType->getDefaultRecipients());

                if (0 === count($form) && !empty($data)) {
                    foreach ($data as $name => $typeData) {
                        $this->addRecipientType($name, $form, $typeData['type'] ?? null, $typeData, $removable);
                    }

                    $form->setData($data);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'template_type' => null,
                'admin' => null,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['admin'] = $options['admin'];
        $tplType = $this->builder->getTemplateType($options['template_type']);
        $view->vars['add_button'] = $tplType && empty($tplType->getDefaultRecipients());
    }

    public function getBlockPrefix()
    {
        return 'recipients';
    }

    protected function addRecipientType($name, FormInterface $form, $type, $params = null, $removable = true)
    {
        /** @var RecipientTypeInterface $recType */
        $recType = $this->manager->getType($type);
        if (!$recType) {
            return;
        }
        if (null !== $params) {
            $recType->setParams($params);
        }

        $builder = $this->manager->createTypeFormBuilder($name ?? uniqid('regtype_'), $type, $removable);
        if ($builder) {
            $form
                ->add($builder->getForm())
            ;
        }
    }
}
