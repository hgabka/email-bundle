<?php

namespace Hgabka\EmailBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Hgabka\UtilsBundle\Form\Type\LocaleType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class SubscriberAdmin extends AbstractAdmin
{
    /** @var SubscriptionManager */
    protected $manager;

    /** @var HgabkaUtils */
    protected $utils;

    public function getManager()
    {
        return $this->manager;
    }

    public function setManager(SubscriptionManager $subscriptionManager)
    {
        $this->manager = $subscriptionManager;

        return $this;
    }

    public function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'subscriber';
    }

    public function setUtils(HgabkaUtils $hgabkaUtils)
    {
        $this->utils = $hgabkaUtils;

        return $this;
    }

    public function toString(object $object): string
    {
        return $this->getTranslator()->trans('hg_email.label.subscriber', ['%name%' => (string) $object->getName()]);
    }

    public function postPersist(object $object): void
    {
        $this->manager->updateListSubscriptions($object, true);
    }

    public function postUpdate(object $object): void
    {
        $this->manager->updateListSubscriptions($object, true);
    }

    protected function configureBatchActions(array $actions): array
    {
        return [];
    }

    protected function configureListFields(ListMapper $list): void
    {
        if ($this->manager->isUseNames()) {
            $list
                ->add('name', null, [
                    'label' => 'hg_email.label.name',
                ])
            ;
        }

        $list
            ->add('email', null, [
                'label' => 'hg_email.label.email',
            ])
        ;
        if ($this->manager->isEditableLists()) {
            $list
                ->add('lists', null, [
                    'label' => 'hg_email.label.lists',
                    'template' => '@HgabkaEmail/Admin/Subscriber/list_lists.html.twig',
                ])
            ;
        }
        $list
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $filter): void
    {
        if ($this->manager->isUseNames()) {
            $filter
                ->add('name', null, [
                    'label' => 'hg_email.label.name',
                ])
            ;
        }

        $filter
            ->add('email', null, [
                'label' => 'hg_email.label.email',
            ])
        ;
        if ($this->manager->isEditableLists()) {
            $filter
                ->add('lists', CallbackFilter::class, [
                    'label' => 'hg_email.label.lists',
                    'field_type' => EntityType::class,
                    'field_options' => [
                        'class' => MessageList::class,
                        'label' => 'hg_email.label.lists',
                        'multiple' => true,
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('l')
                                      ->leftJoin('l.translations', 'lt', 'WITH', 'lt.locale = :locale')
                                      ->setParameter('locale', $this->utils->getCurrentLocale())
                                      ->orderBy('l.isDefault', 'DESC')
                                      ->addOrderBy('lt.name')
                                ;
                        },
                    ],
                    'callback' => static function ($query, $alias, $field, FilterData $value) {
                        if (!$value->hasValue() || empty($value->getValue())) {
                            return false;
                        }

                        $query
                            ->leftJoin($alias . '.listSubscriptions', 'sl')
                            ->andWhere('sl.list IN (:lists)')
                            ->setParameter('lists', $value->getValue())
                           // ->groupBy($alias.'.id')
                        ;

                        return true;
                    },
                ])
            ;
        }
    }

    protected function configureFormFields(FormMapper $form): void
    {
        if ($this->manager->isUseNames()) {
            $form
                ->add('name', TextType::class, [
                    'label' => 'hg_email.label.name',
                    'constraints' => new NotBlank(),
                ])
            ;
        }

        $form
            ->add('email', EmailType::class, [
                'label' => 'hg_email.label.email',
                'constraints' => [
                    new Email(),
                    new NotBlank(),
                ],
            ])
        ;
        if (\count($this->utils->getAvailableLocales()) > 1) {
            $form
                ->add('locale', LocaleType::class, [
                    'label' => 'hg_email.label.locale',
                ])
            ;
        }

        if ($this->manager->isEditableLists()) {
            $form
                ->add('lists', EntityType::class, [
                    'class' => MessageList::class,
                    'label' => 'hg_email.label.lists',
                    'multiple' => true,
                    'query_builder' => function (EntityRepository $er) {
                        return $er->createQueryBuilder('l')
                            ->leftJoin('l.translations', 'lt', 'WITH', 'lt.locale = :locale')
                            ->setParameter('locale', $this->utils->getCurrentLocale())
                            ->orderBy('lt.name')
                        ;
                    },
                    'preferred_choices' => [$this->manager->getDefaultList()],
                ])
            ;
        }
    }
}
