<?php

namespace Hgabka\EmailBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageListAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'message-list';

    protected $datagridValues = [
        '_page' => 1,
        '_sort_order' => 'ASC',
        '_sort_by' => 'translations.name',
    ];

    /** @var SubscriptionManager */
    protected $manager;

    protected function configureBatchActions(array $actions): array
    {
        return [];
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function setManager(SubscriptionManager $subscriptionManager)
    {
        $this->manager = $subscriptionManager;

        return $this;
    }

    public function toString(object $object): string
    {
        return $this->getTranslator()->trans('hg_email.label.message_list', ['%name%' => (string)$object->getName()]);
    }

    /**
     * Get the list of actions that can be accessed directly from the dashboard.
     *
     * @return array
     */
    protected function configureDashboardActions(array $actions): array
    {
        $actions = $this->manager->isEditableLists() ? parent::configureDashboardActions() : [];

        return $actions;
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->remove('export');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('translations.name', null, [
                'label' => 'hg_email.label.name',
                'sortable' => true,
                'template' => '@HgabkaEmail/Admin/MessageList/list_name.html.twig',
            ])
            ->add('subscribers', null, [
                'label' => 'hg_email.label.subscribers',
                'template' => '@HgabkaEmail/Admin/MessageList/list_subscribers.html.twig',
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
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'fields' => [
                    'name' => [
                        'label' => 'hg_email.label.name',
                        'constraints' => new NotBlank(),
                    ],
                ],
            ])
        ;
    }
}
