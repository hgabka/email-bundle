<?php

namespace Hgabka\EmailBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\UtilsBundle\Form\Type\StaticControlType;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailLayoutAdmin extends AbstractAdmin
{
    /** @var MailBuilder */
    protected $mailBuilder;

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

    public function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'email-layout';
    }
    
    public function toString(object $object): string
    {
        return $this->trans('hg_email.label.message_list', ['%name%' => (string) $object->getName()]);
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'translations.name';
    }

    protected function configureBatchActions(array $actions): array
    {
        return [];
    }

    /**
     * Get the list of actions that can be accessed directly from the dashboard.
     *
     * @return array
     */
    protected function configureDashboardActions(array $actions): array
    {
        $actions = $this->mailBuilder->layoutsEditable() ? parent::onfigureDashboardActions($actions) : [];

        return $actions;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list
            ->add('translations.name', null, [
                'label' => 'hg_email.label.name',
                'sortable' => true,
                'template' => '@HgabkaEmail/Admin/MessageList/list_name.html.twig',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $form): void
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
