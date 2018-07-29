<?php

namespace Hgabka\KunstmaanEmailBundle\Form;

use Hgabka\KunstmaanExtensionBundle\Form\Type\LocaleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageMailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class, [
            'label' => 'hgabka_kuma_email.labels.email',
            'required' => true,
            'constraints' => [
                new Email(),
                new NotBlank(),
            ],
        ])
            ->add('locale', LocaleType::class, [
                'label' => 'hgabka_kuma_email.labels.locale',
            ])
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'hgabka_kunstmaanemail_message_mail_type';
    }
}
