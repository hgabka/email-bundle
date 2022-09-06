<?php

namespace Hgabka\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateRecipientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['recipient_type']) {
            $builder->add('type', HiddenType::class);
            $builder->get('type')
                ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                    $event->setData(\get_class($options['recipient_type']));
                })
            ;
        }
    }

    public function getParent(): ?string
    {
        return FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => null,
                'removable' => true,
                'recipient_type' => null,
                'auto_initialize' => false,
                'block_title' => null,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['removable'] = $options['removable'];
        $view->vars['typeName'] = $options['recipient_type'] ? $options['recipient_type']->getName() : '';
        $view->vars['blockTitle'] = $options['block_title'] ?? ($options['recipient_type'] ? $options['recipient_type']->getTitle() : '');
        $view->vars['recipientType'] = $options['recipient_type'];
    }
}
