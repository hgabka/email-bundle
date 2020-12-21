<?php

namespace Hgabka\EmailBundle\Form;

use Doctrine\ORM\EntityManager;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Hgabka\UtilsBundle\Form\Type\LocaleType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MessageSubscriberAdminType extends AbstractType
{
    /** @var EntityManager */
    private $manager;

    /** @var AuthorizationChecker */
    private $authChecker;

    /** @var SubscriptionManager */
    private $subscriptionManager;

    /** @var HgabkaUtils */
    private $hgabkaUtils;

    /**
     * MessageSubscriberAdminType constructor.
     *
     * @param null|EntityManager $manager
     */
    public function __construct(EntityManager $manager, HgabkaUtils $hgabkaUtils, SubscriptionManager $subscriptionManager, AuthorizationChecker $authChecker = null)
    {
        $this->manager = $manager;
        $this->authChecker = $authChecker;
        $this->subscriptionManager = $subscriptionManager;
        $this->hgabkaUtils = $hgabkaUtils;
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
        $subscriptionManager = $this->subscriptionManager;
        $hgabkaUtils = $this->hgabkaUtils;

        $builder
            ->add('name', TextType::class, ['label' => 'hg_email.labels.name', 'required' => true])
            ->add('email', EmailType::class, ['label' => 'hg_email.labels.email', 'required' => true])
            ->add('locale', LocaleType::class, ['label' => 'hg_email.labels.locale', 'placeholder' => ''])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($hgabkaUtils) {
                $subscriber = $event->getData();

                if (empty($subscriber->getId())) {
                    $subscriber
                        ->setLocale($hgabkaUtils->getDefaultLocale());
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($subscriptionManager) {
                /** @var MessageSubscriber $subscriber */
                $subscriber = $event->getData();

                $subscriptionManager->addSubscriberToLists($subscriber, null, false);
            })
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix()
    {
        return 'hg_email_message_subscriber_type';
    }
}
