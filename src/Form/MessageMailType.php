<?php

namespace Hgabka\EmailBundle\Form;

use Hgabka\UtilsBundle\Form\Type\LocaleType;
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
            'label' => 'hg_email.label.email',
            'required' => true,
            'constraints' => [
                new Email(),
                new NotBlank(),
            ],
        ])
            ->add('locale', LocaleType::class, [
                'label' => 'hg_email.label.locale',
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
        return 'hg_email_message_mail_type';
    }
}
