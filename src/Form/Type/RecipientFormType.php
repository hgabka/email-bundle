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

class RecipientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['recipient_type']) {
            $builder->add('type', HiddenType::class);
            $builder->get('type')
                ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
                    $event->setData(get_class($options['recipient_type']));
                })
            ;
        }
    }

    public function getParent()
    {
        return FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => null,
                'removable' => true,
                'recipient_type' => null,
                'auto_initialize' => false,
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['removable'] = $options['removable'];
        $view->vars['typeName'] = $options['recipient_type'] ? $options['recipient_type']->getName() : '';
        $view->vars['blockTitle'] = $options['recipient_type'] ? $options['recipient_type']->getTitle() : '';
    }
}
