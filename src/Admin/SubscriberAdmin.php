<?php

namespace Hgabka\EmailBundle\Admin;

use Doctrine\ORM\EntityRepository;
use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Hgabka\UtilsBundle\Form\Type\LocaleType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class SubscriberAdmin extends AbstractAdmin
{
    /** @var SubscriptionManager */
    protected $manager;

    /** @var HgabkaUtils */
    protected $utils;

    public function getBatchActions()
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

    public function setUtils(HgabkaUtils $hgabkaUtils)
    {
        $this->utils = $hgabkaUtils;

        return $this;
    }

    public function toString($object)
    {
        return $this->trans('hg_email.label.subscriber', ['%name%' => $object->getName()]);
    }

    protected function configureListFields(ListMapper $list)
    {
        $list
            ->add('name', null, [
                'label' => 'hg_email.label.name',
            ])
            ->add('email', null, [
                'label' => 'hg_email.label.email',
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
            ->add('name', TextType::class, [
                'label' => 'hg_email.label.name',
                'constraints' => new NotBlank(),
            ])
            ->add('email', EmailType::class, [
                'label' => 'hg_email.label.email',
                'constraints' => [
                    new Email(),
                    new NotBlank(),
                ],
            ])
        ;
        if ($this->utils->getAvailableLocales() > 1) {
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

        $form->getFormBuilder()
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                /** @var MessageSubscriber $subscriber */
                $data = $event->getData();
                $subscriber = $event->getForm()->getData();
                $this->manager->updateListSubscriptions($subscriber, $data['lists'] ?? null);
            })
        ;
    }
}
