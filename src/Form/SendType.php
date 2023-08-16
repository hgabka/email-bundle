<?php

namespace Hgabka\EmailBundle\Form;

use Sonata\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'hg_email.label.send_at',
                'choices' => [
                    'hg_email.label.send_now' => 'now',
                    'hg_email.label.send_later' => 'later',
                ],
            ])
            ->add('time', DatePickerType::class, [
                'label' => 'hg_email.label.send_time',
                'constraints' => [
                    new Range(['min' => 'now', 'minMessage' => 'hg_email.messages.send_at_error']),
                ],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'constraints' => [
                new Callback([
                    'callback' => function ($data, ExecutionContextInterface $context) {
                        if ('later' === $data['type'] && empty($data['time'])) {
                            $context->buildViolation('hg_email.messages.send_time_required')
                                     ->atPath('[time]')
                                     ->addViolation();
                        }
                    },
                ]),
            ],
        ]);
    }
}
