<?php

namespace Hgabka\KunstmaanEmailBundle\Form;

use Hgabka\KunstmaanExtensionBundle\Form\Type\DateTimepickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class SendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'hgabka_kuma_email.labels.send_at',
                'choices' => [
                    'hgabka_kuma_email.labels.send_now' => 'now',
                    'hgabka_kuma_email.labels.send_later' => 'later',
                ],
            ])
            ->add('time', DateTimepickerType::class, [
                'label' => 'hgabka_kuma_email.labels.send_time',
                'constraints' => [
                    new Range(['min' => 'now', 'minMessage' => 'hgabka_kuma_email.messages.send_at_error']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound' => true,
            'constraints' => [
                new Callback([
                    'callback' => function ($data, ExecutionContextInterface $context) {
                        if ('later' === $data['type'] && empty($data['time'])) {
                            $context->buildViolation('hgabka_kuma_email.messages.send_time_required')
                                     ->atPath('[time]')
                                     ->addViolation();
                        }
                    },
                ]),
            ],
        ]);
    }
}
