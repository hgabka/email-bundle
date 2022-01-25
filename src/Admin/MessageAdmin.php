<?php

namespace Hgabka\EmailBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Form\AttachmentType;
use Hgabka\EmailBundle\Form\MessageRecipientsType;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridInterface;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Route\RouteCollectionInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'message';

    protected $accessMapping = [
        'prepare' => 'PREPARE',
    ];

    /** @var HgabkaUtils */
    protected $utils;

    /** @var MailBuilder */
    private $builder;

    /** @var AuthorizationCheckerInterface */
    private $authChecker;

    public function setUtils(HgabkaUtils $utils)
    {
        $this->utils = $utils;
    }

    public function setBuilder(MailBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function setAuthChecker(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function prePersist(object $object): void
    {
        $em = $this->getModelManager()->getEntityManager($object);

        foreach ($object->getTranslations() as $trans) {
            $attRepo = $em->getRepository(Attachment::class);
            foreach ($attRepo->getByMessage($trans->getTranslatable(), $trans->getLocale()) as $att) {
                $em->remove($att);
            }
            foreach ($trans->getAttachments() as $att) {
                $att
                    ->setType(Message::class)
                    ->setOwnerId($object->getId())
                    ->setLocale($trans->getLocale())
                ;
                $em->persist($att);
            }
        }
    }

    public function preUpdate(object $object): void
    {
        $em = $this->getModelManager()->getEntityManager($object);

        foreach ($object->getTranslations() as $trans) {
            $attRepo = $em->getRepository(Attachment::class);
            foreach ($attRepo->getByMessage($trans->getTranslatable(), $trans->getLocale()) as $att) {
                $em->remove($att);
            }
            foreach ($trans->getAttachments() as $att) {
                $att
                    ->setType(Message::class)
                    ->setOwnerId($object->getId())
                    ->setLocale($trans->getLocale())
                ;
                $em->persist($att);
            }
        }
    }

    public function postPersist(object $object): void
    {
        $em = $this->getModelManager()->getEntityManager($object);

        foreach ($object->getTranslations() as $trans) {
            foreach ($trans->getAttachments() as $att) {
                $att
                    ->setOwnerId($object->getId())
                ;
            }
        }

        $em->flush();
    }

    public function toString(object $object): string
    {
        $name = $object ? $object->getName($this->utils->getCurrentLocale()) : null;

        return $name ?: $this->getTranslator()->trans('breadcrumb.link_message_create');
    }

    protected function configureDefaultSortValues(array &$sortValues): void
    {
        $sortValues[DatagridInterface::SORT_BY] = 'translations.name';
    }

    protected function configureBatchActions(array $actions): array
    {
        return [];
    }

    protected function configureRoutes(RouteCollectionInterface $collection): void
    {
        $collection->clearExcept(['create', 'edit', 'list', 'delete']);
        $collection->add('add_recipient', 'addRecipient');
        $collection->add('render_usable_vars', 'renderUsableVars');
        $collection->add('prepare', $this->getRouterIdParameter() . '/prepare');
        $collection->add('unprepare', $this->getRouterIdParameter() . '/unprepare');
        $collection->add('testmail', $this->getRouterIdParameter() . '/testmail');
        $collection->add('copy', $this->getRouterIdParameter() . '/copy');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('translations.name', null, [
                'label' => 'hg_email.label.name',
                'sortable' => true,
                'template' => '@HgabkaEmail/Admin/Message/list_name.html.twig',
            ])
            ->add('status', null, [
                'label' => 'hg_email.label.status',
                'template' => '@HgabkaEmail/Admin/Message/list_status.html.twig',
            ])
            ->add(ListMapper::NAME_ACTIONS, null, [
                'actions' => [
                    'edit' => [
                        'template' => '@HgabkaEmail/Admin/Message/list_edit.html.twig',
                    ],
                    'prepare' => [
                        'template' => '@HgabkaEmail/Admin/Message/list_prepare.html.twig',
                    ],
                    'unprepare' => [
                        'template' => '@HgabkaEmail/Admin/Message/list_unprepare.html.twig',
                    ],
                    'testmail' => [
                        'template' => '@HgabkaEmail/Admin/Message/list_testmail.html.twig',
                    ],
                    'copy' => [
                        'template' => '@HgabkaEmail/Admin/Message/list_copy.html.twig',
                    ],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $form): void
    {
        $transFields = [
            'name' => [
                'field_type' => TextType::class,
                'label' => 'hg_email.label.name',
                'required' => true,
                'constraints' => new NotBlank(),
            ],
            'fromName' => [
                'field_type' => TextType::class,
                'label' => 'hg_email.label.from_name',
                'help' => $this->getTranslator()->trans('hg_email.help.from_name', ['%current%' => $this->builder->getDefaultFromName()]),
                'required' => false,
            ],
            'fromEmail' => [
                'field_type' => TextType::class,
                'label' => 'hg_email.label.from_email',
                'help' => $this->getTranslator()->trans('hg_email.help.from_email', ['%current%' => $this->builder->getDefaultFromEmail()]),
                'required' => false,
                'constraints' => new Email(),
            ],
            'subject' => [
                'field_type' => TextType::class,
                'label' => 'hg_email.label.subject',
                'required' => true,
                'constraints' => new NotBlank(),
            ],
            'contentText' => [
                'field_type' => TextareaType::class,
                'label' => 'hg_email.label.content_text',
                'required' => false,
                'attr' => [
                    'rows' => 10,
                ],
            ],
            'contentHtml' => [
                'field_type' => WysiwygType::class,
                'label' => 'hg_email.label.content_html',
                'required' => false,
            ],
            'attachments' => [
                'field_type' => CollectionType::class,
                'label' => false,
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
        ];
        $options = [
            'label' => false,
            'fields' => $transFields,
        ];

        $form
            ->tab('hg_email.tab.general')
                ->with('hg_email.form_block.general')
        ;
        if ($this->builder->layoutsEditable()) {
            $form
                ->add('layout', EntityType::class, [
                    'label' => 'hg_email.label.layout',
                    'required' => false,
                    'class' => EmailLayout::class,
                    'placeholder' => $this->getTranslator()->trans('hg_email.placeholder.email_layout'),
                ])
            ;
        }
        $form
                    ->add('translations', TranslationsType::class, $options)
            ->end()
        ;

        $form
                ->with('hg_email.form_block.from_data')
                ->end()
            ;

        $form
                ->with('hg_email.form_block.to_data')
                ->add('toData', MessageRecipientsType::class, [
                    'label' => false,
                    'admin' => $this,
                    'recipients_type' => RecipientManager::RECIPIENT_TYPE_TO,
                ])
                ->end()
            ;

        if ($this->isCcEditable()) {
            $form
                ->with('hg_email.form_block.cc_data')
                ->add('ccData', MessageRecipientsType::class, [
                    'label' => false,
                    'admin' => $this,
                    'recipients_type' => RecipientManager::RECIPIENT_TYPE_CC,
                ])
                ->end()
            ;
        }
        if ($this->isBccEditable()) {
            $form
                ->with('hg_email.form_block.bcc_data')
                ->add('bccData', MessageRecipientsType::class, [
                    'label' => false,
                    'admin' => $this,
                    'recipients_type' => RecipientManager::RECIPIENT_TYPE_BCC,
                ])
                ->end()
            ;
        }
        $form
            ->end()
            ->tab('hg_email.tab.content', ['description' => true])
            ->with('hg_email.form_block.content')
            ->end()
            ->end()
            ->tab('hg_email.tab.attachments')
            ->with('hg_email.form_block.attachments')
            ->end()
            ->end()
        ;
    }

    protected function isCcEditable()
    {
        return $this->builder->isMessageCcEditable();
    }

    protected function isBccEditable()
    {
        return $this->builder->isMessageBccEditable();
    }

    protected function prepareQueryForTranslatableColumns($query)
    {
        $currentAlias = $query->getRootAliases()[0];
        $utils = $this->getConfigurationPool()->getContainer()->get(HgabkaUtils::class);
        $locale = $utils->getCurrentLocale();
        $parameters = $this->getFilterParameters();
        $sortBy = $parameters['_sort_by'];
        $fieldDescription = $this->getListFieldDescription($sortBy);
        $mapping = $fieldDescription->getAssociationMapping();
        $entityClass = $mapping['targetEntity'] ?: $this->getClass();

        if ($mapping) {
            $mappings = $fieldDescription->getParentAssociationMappings();
            $mappings[] = $mapping;

            foreach ($mappings as $parentMapping) {
                $fieldName = $parentMapping['fieldName'];
                $query->leftJoin($currentAlias . '.' . $fieldName, $fieldName);

                $currentAlias = $fieldName;
            }
        }

        $query
            ->leftJoin(
                $currentAlias . '.translations',
                'tr',
                'with',
                'tr.locale = :lang OR
                (NOT EXISTS(SELECT t.id FROM ' . $entityClass . 'Translation t WHERE t.translatable = tr.translatable AND t.locale = :lang)
                AND tr.locale = :lang_default)'
            )
            ->addOrderBy('tr.name', $parameters['_sort_order'])
            ->setParameter(':lang', $locale)
            ->setParameter(':lang_default', $utils->getDefaultLocale());

        return $query;
    }

    protected function getAccessMapping(): array
    {
        return $this->accessMapping;
    }
}
