<?php

namespace Hgabka\EmailBundle\Admin;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\EmailLayout;
use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Form\AttachmentType;
use Hgabka\EmailBundle\Form\EmailTemplateRecipientsType;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Helper\TemplateTypeManager;
use Hgabka\UtilsBundle\Form\Type\StaticControlType;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailTemplateAdmin extends AbstractAdmin
{
    protected $baseRoutePattern = 'email-template';

    /** @var MailBuilder */
    private $builder;

    /** @var AuthorizationCheckerInterface */
    private $authChecker;

    /** @var TemplateTypeManager */
    private $templateTypeManager;

    public function setBuilder(MailBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function setTemplateTypeManager(TemplateTypeManager $templateTypeManager)
    {
        $this->templateTypeManager = $templateTypeManager;
    }

    public function getBatchActions()
    {
        return [];
    }

    public function setAuthChecker(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function createQuery($context = 'list')
    {
        $this->templateTypeManager->getTemplateTypeEntities(true);
        $types = $this->templateTypeManager->getTemplateTypeClasses(true);

        $query = parent::createQuery($context);
        $alias = current($query->getRootAliases());

        $orx = $query->expr()->orX();
        $orx->add($alias.'.type IS NULL');

        if (!empty($types)) {
            $orx->add($query->expr()->in($alias.'.type', $types));
        }

        $query->andWhere($orx);

        return $query;
    }

    public function hasAccess($action, $object = null)
    {
        if ('edit' === $action) {
            if ($object) {
                $type = $this->templateTypeManager->getTemplateType($object->getType());
                if ($type && !$type->isPublic()) {
                    return false;
                }
            }

            return $this->authChecker->isGranted($this->getConfigurationPool()->getContainer()->getParameter('hg_email.editor_role'));
        }

        return parent::hasAccess($action, $object);
    }

    public function checkAccess($action, $object = null)
    {
        if ('edit' === $action) {
            $isEditor = $this->authChecker->isGranted($this->getConfigurationPool()->getContainer()->getParameter('hg_email.editor_role'));
            if ($object) {
                $type = $this->templateTypeManager->getTemplateType($object->getType());

                if ($type && !$type->isPublic()) {
                    throw new AccessDeniedException($this->trans('hg_email.messages.access_denied'));
                }

                if ($isEditor) {
                    return;
                }
            } elseif ($isEditor) {
                return;
            }
        }

        parent::checkAccess($action, $object);
    }

    public function prePersist($object)
    {
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();

        foreach ($object->getTranslations() as $trans) {
            $attRepo = $em->getRepository(Attachment::class);
            foreach ($attRepo->getByTemplate($trans->getTranslatable(), $trans->getLocale()) as $att) {
                $em->remove($att);
            }
            foreach ($trans->getAttachments() as $att) {
                $att
                    ->setType(EmailTemplate::class)
                    ->setOwnerId($object->getId())
                    ->setLocale($trans->getLocale())
                ;
                $em->persist($att);
            }
        }
    }

    public function preUpdate($object)
    {
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();
        foreach ($object->getTranslations() as $trans) {
            $attRepo = $em->getRepository(Attachment::class);
            foreach ($attRepo->getByTemplate($trans->getTranslatable(), $trans->getLocale()) as $att) {
                $em->remove($att);
            }
            foreach ($trans->getAttachments() as $att) {
                $att
                    ->setType(EmailTemplate::class)
                    ->setOwnerId($object->getId())
                    ->setLocale($trans->getLocale())
                ;
                $em->persist($att);
            }
        }
    }

    public function toString($object)
    {
        $type = $object->getType();

        return $type ? $this->templateTypeManager->getTitleByType($type) : $template->getComment();
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(['edit', 'list', 'delete']);
        $collection->add('add_recipient', 'addRecipient');
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('comment', null, [
                'label' => 'hg_email.label.comment',
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
        $type = $this->templateTypeManager->getTemplateType($this->getSubject()->getType());
        $transFields = [
            'comment' => [
                'field_type' => TextType::class,
                'label' => 'hg_email.label.comment',
                'required' => true,
                'constraints' => new NotBlank(),
            ],
        ];

        if ($type->isSenderEditable()) {
            $transFields = array_merge($transFields, [
                'fromName' => [
                    'field_type' => TextType::class,
                    'label' => 'hg_email.label.from_name',
                    'sonata_help' => $this->trans('hg_email.help.from_name', ['%current%' => $this->builder->getDefaultFromName()]),
                    'required' => false,
                ],
                'fromEmail' => [
                    'field_type' => TextType::class,
                    'label' => 'hg_email.label.from_email',
                    'sonata_help' => $this->trans('hg_email.help.from_email', ['%current%' => $this->builder->getDefaultFromEmail()]),
                    'required' => false,
                    'constraints' => new Email(),
                ],
            ]);
        }

        $transFields = array_merge($transFields, [
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
        ]);
        $options = [
            'label' => false,
            'fields' => $transFields,
        ];
        if (!$type->isSenderEditable()) {
            $options['exclude_fields'] = ['fromName', 'fromEmail'];
        }

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
                    'placeholder' => $this->trans('hg_email.placeholder.email_layout'),
                ])
            ;
        }
        $form
                   ->add('translations', TranslationsType::class, $options)
                ->end()
        ;
        if ($type->isSenderEditable()) {
            $form
                ->with('hg_email.form_block.from_data')
                ->end()
            ;
        } else {
            $form
                ->with('hg_email.form_block.from_data_static')
                    ->add('fromText', StaticControlType::class, [
                        'label' => false,
                        'html' => '
                            <div class="panel-group">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4 class="panel-title">
                                            <a>
                                                 '.$this->trans($type->getSenderText()).'
                                            </a>
                                         </h4>
                                     </div>

                                </div>
                            </div>',
                        'mapped' => false,
                    ])
                ->end()
            ;
        }
        if ($type->isToEditable()) {
            $form
                ->with('hg_email.form_block.to_data')
                    ->add('toData', EmailTemplateRecipientsType::class, [
                        'label' => false,
                        'template_type' => $this->getSubject()->getType(),
                        'admin' => $this,
                        'recipients_type' => RecipientManager::RECIPIENT_TYPE_TO,
                    ])
                ->end()
            ;
        }

        if ($type->isCcEditable() || !empty($type->getDefaultCc())) {
            $form
                ->with('hg_email.form_block.cc_data')
                    ->add('ccData', EmailTemplateRecipientsType::class, [
                        'label' => false,
                        'template_type' => $this->getSubject()->getType(),
                        'admin' => $this,
                        'recipients_type' => RecipientManager::RECIPIENT_TYPE_CC,
                    ])
                ->end()
            ;
        }
        if ($type->isBccEditable() || !empty($type->getDefaultBcc())) {
            $form
                ->with('hg_email.form_block.bcc_data')
                    ->add('bccData', EmailTemplateRecipientsType::class, [
                        'label' => false,
                        'template_type' => $this->getSubject()->getType(),
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
}
