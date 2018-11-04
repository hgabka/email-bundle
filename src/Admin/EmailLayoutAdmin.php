<?php

namespace Hgabka\EmailBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\UtilsBundle\Form\Type\StaticControlType;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailLayoutAdmin extends AbstractAdmin
{
    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'ASC',
        '_sort_by' => 'translations.name',
    ];

    /** @var MailBuilder */
    protected $mailBuilder;

    public function getBatchActions()
    {
        return [];
    }

    /**
     * @return MailBuilder
     */
    public function getMailBuilder()
    {
        return $this->mailBuilder;
    }

    /**
     * @param MailBuilder $mailBuilder
     *
     * @return EmailLayoutAdmin
     */
    public function setMailBuilder($mailBuilder)
    {
        $this->mailBuilder = $mailBuilder;

        return $this;
    }

    public function toString($object)
    {
        return $this->trans('hg_email.label.message_list', ['%name%' => $object->getName()]);
    }

    /**
     * Get the list of actions that can be accessed directly from the dashboard.
     *
     * @return array
     */
    public function getDashboardActions()
    {
        $actions = $this->mailBuilder->layoutsEditable() ? parent::getDashboardActions() : [];

        return $actions;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->remove('export');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('translations.name', null, [
                'label' => 'hg_email.label.name',
                'sortable' => true,
                'template' => '@HgabkaEmail/Admin/MessageList/list_name.html.twig',
            ])
            ->add('_action', null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $form)
    {
        $form
            ->with('hg_email.block.layout.usable_vars')
                ->add('usableVars', StaticControlType::class, [
                    'template' => '@HgabkaEmail/Admin/EmailLayout/usable_vars.html.twig',
                    'mapped' => false,
                    'label' => false,
                ])
            ->end()
            ->with('hg_email.block.layout.general')
                ->add('translations', TranslationsType::class, [
                    'label' => false,
                    'fields' => [
                        'name' => [
                            'label' => 'hg_email.label.name',
                            'constraints' => new NotBlank(),
                        ],
                        'contentHtml' => [
                            'field_type' => WysiwygType::class,
                            'label' => 'hg_email.label.content_html',
                            'constraints' => new NotBlank(),
                            'config' => [
                                'allowedContent' => true,
                                'extraAllowedContent' => '*[*](*){*}',
                                'fullPage' => true,
                                'enterMode' => 2,
                                'extraPlugins' => 'docprops',
                            ],
                        ],
                    ],
                ])
            ->end()
        ;
    }
}
