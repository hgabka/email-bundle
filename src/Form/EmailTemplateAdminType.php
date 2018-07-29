<?php

namespace Hgabka\EmailBundle\Form;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Doctrine\ORM\EntityManager;
use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class EmailTemplateAdminType extends AbstractType
{
    /** @var EntityManager */
    private $manager;

    /** @var AuthorizationChecker */
    private $authChecker;

    public function __construct(EntityManager $manager = null, AuthorizationChecker $authChecker = null)
    {
        $this->manager = $manager;
        $this->authChecker = $authChecker;
    }

    /**
     * Builds the form.
     *
     * This method is called for each type in the hierarchy starting form the
     * top most type. Type extensions can further modify the form.
     *
     * @see FormTypeExtensionInterface::buildForm()
     *
     * @param FormBuilderInterface $builder The form builder
     * @param array                $options The options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, ['label' => 'hgabka_kuma_email.labels.name', 'required' => true])
            ->add('comment', TextareaType::class, ['label' => 'hgabka_kuma_email.labels.comment'])
        ;
        if ($this->authChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('slug', TextType::class, ['label' => 'hgabka_kuma_email.labels.slug']);
            $builder->add('isSystem', CheckboxType::class, ['label' => 'hgabka_kuma_email.labels.is_system', 'required' => false]);
        }
        $builder->add('layout', EntityType::class, [
            'label' => 'hgabka_kuma_email.labels.layout',
            'class' => EmailLayout::class,
            'placeholder' => 'hgabka_kuma_email.labels.no_layout',
            'required' => false,
        ]);
        $builder->add('translations', TranslationsType::class, [
            'label' => false,
            'fields' => [
                'subject' => [
                    'field_type' => TextType::class,
                    'label' => 'hgabka_kuma_email.labels.subject',
                ],
                'contentText' => [
                    'field_type' => TextareaType::class,
                    'label' => 'hgabka_kuma_email.labels.content_text',
                ],
                'contentHtml' => [
                    'field_type' => WysiwygType::class,
                    'label' => 'hgabka_kuma_email.labels.content_html',
                ],
                'attachments' => [
                    'field_type' => CollectionType::class,
                    'label' => 'hgabka_kuma_email.labels.attachments',
                    'entry_type' => AttachmentType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'delete_empty' => true,
                    'required' => true,
                    'attr' => [
                        'nested_form' => true,
                        'nested_sortable' => false,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'hgabka_kunstmaanemail_email_template_type';
    }
}
