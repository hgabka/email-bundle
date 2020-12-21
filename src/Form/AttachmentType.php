<?php

namespace Hgabka\EmailBundle\Form;

use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\MediaBundle\Admin\MediaAdmin;
use Hgabka\MediaBundle\Form\Type\MediaSimpleType;
use Hgabka\MediaBundle\Form\Type\MediaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttachmentType extends AbstractType
{
    /** @var MediaAdmin */
    protected $mediaAdmin;

    /**
     * AttachmentType constructor.
     */
    public function __construct(MediaAdmin $mediaAdmin)
    {
        $this->mediaAdmin = $mediaAdmin;
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
        $mediaAccess = $this->mediaAdmin->hasAccess('list');
        if ($mediaAccess) {
            $builder
                ->add('media', MediaType::class, ['label' => false, 'required' => false, 'mediatype' => 'file', 'foldername' => 'attachment']);
        } else {
            $builder
                ->add('media', MediaSimpleType::class, [
                    'required' => false,
                    'label' => false,
                    'foldername' => 'attachment',
                    'parentfolder' => 'fileroot',
                    ]);
        }
    }

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver the resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Attachment::class,
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'hg_email_attachment_type';
    }
}
