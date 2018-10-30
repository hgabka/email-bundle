<?php

namespace Hgabka\EmailBundle\Admin;

use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Form\AttachmentType;
use Hgabka\EmailBundle\Form\Type\MessageRecipientsType;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\UtilsBundle\Form\WysiwygType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class MessageAdmin extends AbstractAdmin
{
    /** @var MailBuilder */
    private $builder;

    /** @var AuthorizationCheckerInterface */
    private $authChecker;

    public function setBuilder(MailBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function getBatchActions()
    {
        return [];
    }

    public function setAuthChecker(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function prePersist($object)
    {
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();

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
                    ->setType(Message::class)
                    ->setOwnerId($object->getId())
                    ->setLocale($trans->getLocale())
                ;
                $em->persist($att);
            }
        }
    }

    public function postPersist($object)
    {
        $em = $this->getConfigurationPool()->getContainer()->get('doctrine')->getManager();
        foreach ($object->getTranslations() as $trans) {
            foreach ($trans->getAttachments() as $att) {
                $att
                    ->setOwnerId($object->getId())
                ;
            }
        }

        $em->flush();
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(['edit', 'list', 'delete']);
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', null, [
                'label' => 'hg_email.label.name',
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
        $transFields = [
            'name' => [
                'field_type' => TextType::class,
                'label' => 'hg_email.label.comment',
                'required' => true,
                'constraints' => new NotBlank(),
            ],
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
        return true;
    }

    protected function isBccEditable()
    {
        return true;
    }
}
