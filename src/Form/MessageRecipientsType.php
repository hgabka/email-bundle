<?php

namespace Hgabka\EmailBundle\Form;

use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Model\MessageRecipientTypeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageRecipientsType extends AbstractType
{
    /**
     * RecipientsType constructor.
     */
    public function __construct(protected readonly RecipientManager $manager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (!empty($data)) {
                    foreach ($form->all() as $name => $field) {
                        $form->remove($name);
                    }
                }

                if (0 === \count($form)) {
                    if (!empty($data)) {
                        foreach ($data as $name => $typeData) {
                            $this->addRecipientType($name, $form, $typeData['type'], $typeData);
                        }
                    }
                }
            })
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                foreach ($form->all() as $name => $field) {
                    $form->remove($name);
                }

                if (0 === \count($form) && !empty($data)) {
                    foreach ($data as $name => $typeData) {
                        $this->addRecipientType($name, $form, $typeData['type'] ?? null, $typeData);
                    }

                    $form->setData($data);
                } elseif (empty($data)) {
                    $form->setData($data);
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'recipients_type' => RecipientManager::RECIPIENT_TYPE_TO,
                'admin' => null,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['admin'] = $options['admin'];
        $view->vars['recipientsType'] = $options['recipients_type'];
        $view->vars['add_button'] = true;
        $view->vars['dataType'] = 'message';
    }

    public function getBlockPrefix(): string
    {
        return 'message_recipients';
    }

    protected function addRecipientType($name, FormInterface $form, $type, $params = null): void
    {
        /** @var MessageRecipientTypeInterface $recType */
        $recType = $this->manager->getMessageRecipientType($type);
        if (!$recType) {
            return;
        }
        if (null !== $params) {
            $recType->setParams($params);
        }

        $builder = $this->manager->createMessageRecipientTypeFormBuilder($name ?? uniqid('rectype_'), $type);
        if ($builder) {
            $form
                ->add($builder->getForm())
            ;
        }
    }
}
