<?php

namespace Hgabka\EmailBundle\Form;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Doctrine\ORM\EntityManager;
use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Entity\MessageSendList;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class MessageAdminType extends AbstractType
{
    /** @var EntityManager */
    private $manager;

    /** @var AuthorizationChecker */
    private $authChecker;

    /** @var MailBuilder */
    private $mailBulder;

    public function __construct(?EntityManager $manager = null, ?MailBuilder $mailBuilder = null, ?AuthorizationChecker $authChecker = null)
    {
        $this->manager = $manager;
        $this->authChecker = $authChecker;
        $this->mailBulder = $mailBuilder;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mailBuilder = $this->mailBulder;
        $builder
            ->add('fromName', TextType::class, ['label' => 'hgabka_kuma_email.labels.from_name', 'required' => true])
            ->add('fromEmail', EmailType::class, ['label' => 'hgabka_kuma_email.labels.from_email', 'required' => true])
        ;
        $builder->add('layout', EntityType::class, [
            'label' => 'hgabka_kuma_email.labels.layout',
            'class' => EmailLayout::class,
            'placeholder' => 'hgabka_kuma_email.labels.no_layout',
            'required' => false,
        ]);
        $builder
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'fields' => [
                    'subject' => [
                        'field_type' => TextType::class,
                        'label' => 'hgabka_kuma_email.labels.subject',
                        'required' => true,
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
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($mailBuilder) {
                $message = $event->getData();

                if (empty($message->getId())) {
                    $from = $mailBuilder->getDefaultFrom();

                    $message
                        ->setFromName(\is_array($from) ? reset($from) : null)
                        ->setFromEmail(\is_array($from) ? key($from) : $from);
                }
            })
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var Message $message */
                $message = $event->getData();

                // Egyelőre csak a default listára
                /** @var MessageList $list */
                $list = $this->manager
                    ->getRepository(MessageList::class)
                    ->getDefaultList()
                ;
                if ($message->getId()) {
                    $messageList = $this->manager
                        ->getRepository(MessageSendList::class)
                        ->findForMessageAndList($message, $list)
                    ;
                }

                if (empty($messageList)) {
                    $messageList = new MessageSendList();
                    $this->manager->persist($messageList);
                }

                $list->addSendList($messageList);
                $message->addSendList($messageList);
            })
        ;
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'hg_email_message_type';
    }
}
