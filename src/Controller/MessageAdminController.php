<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Entity\Attachment;
use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Enum\MessageStatusEnum;
use Hgabka\EmailBundle\Form\MessageMailType;
use Hgabka\EmailBundle\Form\MessageRecipientsType;
use Hgabka\EmailBundle\Form\MessageSendType;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\MessageSender;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Recipient\GeneralMessageRecipientType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class MessageAdminController extends CRUDController
{
    public function addRecipientAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createNotFoundException();
        }
        $fieldName = $request->get('fieldType').'Data';

        $recipientManager = $this->get(RecipientManager::class);
        $type = $request->get('type');

        $recType = $recipientManager->getMessageRecipientType($type);

        $builder = $this
            ->get('form.factory')
            ->createNamedBuilder($request->get('name'))
            ->add($fieldName, MessageRecipientsType::class, [
                'admin' => $this->admin,
                'recipients_type' => $request->get('fieldtype'),
            ])
        ;

        $key = uniqid('regtype_');
        $builder->get($fieldName)->add($recipientManager->createMessageRecipientTypeFormBuilder($key, $type));

        $form = $builder->getForm();
        $template = $recType->getFormTemplate() ?: '@HgabkaEmail/Admin/recipient_type_form.html.twig';

        $html = $this->renderView($template, [
            'child' => $form->get($fieldName)->get($key)->createView(),
            'key' => $key,
            'ajax' => true,
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }

    public function renderUsableVarsAction()
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->createNotFoundException();
        }

        parse_str($request->request->get('data'), $data);
        $toData = $data[$request->request->get('name')]['toData'] ?? [];

        $message = (new Message())->setToData($toData);

        return $this->render('@HgabkaEmail/Admin/Message/render_usable_vars.html.twig', [
            'message' => $message,
        ]);
    }

    public function prepareAction(MessageSender $sender)
    {
        $request = $this->getRequest();

        $id = $request->get($this->admin->getIdParameter());
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        if (!$existingObject->isPrepareable()) {
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('hg_email.messages.not_prepareable')
            );

            return $this->redirectToList();
        }

        $this->admin->checkAccess('prepare', $existingObject);

        $form = $this->createForm(MessageSendType::class, $existingObject);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                $sender->prepareMessage($existingObject);
                $this->addFlash(
                    'sonata_flash_success',
                    $this->trans('hg_email.messages.prepare_success')
                );

                return $this->redirectToList();
            }
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('hg_email.messages.prepare_error')
            );
        }
        $formView = $form->createView();
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        return $this->renderWithExtraParams('@HgabkaEmail/Admin/Message/prepare.html.twig', [
            'action' => 'prepare',
            'form' => $formView,
            'object' => $existingObject,
            'objectId' => $this->admin->getNormalizedIdentifier($existingObject),
        ]);
    }

    public function unprepareAction(MessageSender $sender)
    {
        $request = $this->getRequest();

        $id = $request->get($this->admin->getIdParameter());
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        if (!$existingObject->isUnprepareable()) {
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('hg_email.messages.not_unprepareable')
            );

            return $this->redirectToList();
        }

        $this->admin->checkAccess('prepare', $existingObject);

        $sender->unPrepareMessage($existingObject);
        $this->addFlash(
            'sonata_flash_success',
            $this->trans('hg_email.messages.unprepare_success')
        );

        return $this->redirectToList();
    }

    public function testmailAction(MailBuilder $mailBuilder, RecipientManager $recipientManager, HgabkaUtils $hgabkaUtils)
    {
        $request = $this->getRequest();

        $id = $request->get($this->admin->getIdParameter());
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }
        $this->admin->checkAccess('list');

        $form = $this->createForm(MessageMailType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $email = $form->getData()['email'];
                $locale = $form->getData()['locale'] ?? $hgabkaUtils->getDefaultLocale();
                $recType = $recipientManager->getMessageRecipientType(GeneralMessageRecipientType::class);
                $recType->setParams([
                    'name' => 'XXX',
                    'email' => $email,
                    'locale' => $locale,
                ]);
                $text = $this->get('translator')->trans('hg_email.title.unsubscribe', [], 'messages', $locale);
                $params = [
                    'vars' => [
                        'unsubscribe_url' => '#',
                        'unsubscribe_link' => '<a href="#">'.$text.'</a>',
                        'webversion' => '',
                    ],
                ];

                ['mail' => $message] = $mailBuilder
                                ->createMessageMail($existingObject, [$email => 'XXX'], $locale, false, $params, $recType);

                $this->get('mailer')->send($message);
                $this->addFlash(
                    'sonata_flash_success',
                    $this->trans('hg_email.messages.testmail_success')
                );

                return $this->redirectToList();
            }
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('hg_email.messages.testmail_error')
            );
        }

        $formView = $form->createView();
        $this->setFormTheme($formView, $this->admin->getFormTheme());

        return $this->renderWithExtraParams('@HgabkaEmail/Admin/Message/testmail.html.twig', [
            'form' => $formView,
            'object' => $existingObject,
            'action' => 'testmail',
        ]);
    }

    public function copyAction(HgabkaUtils $utils)
    {
        $request = $this->getRequest();

        $id = $request->get($this->admin->getIdParameter());
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }
        $this->admin->checkAccess('create');

        $arr = $utils->entityToArray($existingObject, 0);
        unset($arr['id'], $arr['createdAt'], $arr['updatedAt']);
        $arr['sendAt'] = null;
        $arr['status'] = MessageStatusEnum::STATUS_INIT;

        $message = new Message();
        $utils->entityFromArray($message, $arr);
        $message
            ->setSentMail(0)
            ->setSentFail(0)
            ->setSentSuccess(0)
        ;
        $em = $this->getDoctrine()->getManager();
        $em->persist($message);
        foreach ($utils->getAvailableLocales() as $loc) {
            if (!empty($existingObject->translate($loc)->getName())) {
                $copyText = $this->get('translator')->trans('hg_email.text.copy', [], 'messages', $loc);
                $message->translate($loc)->setSubject($existingObject->translate($loc)->getSubject());
                $message->translate($loc)->setContentText($existingObject->translate($loc)->getContentText());
                $message->translate($loc)->setContentHtml($existingObject->translate($loc)->getContentHtml());
                $message->translate($loc)->setName($existingObject->translate($loc)->getName().' - '.$copyText);
            } else {
                $existingObject->translate($loc)->setName('dummy');
            }
        }

        $em->flush();

        $attachments = $em->getRepository(Attachment::class)->getByMessage($existingObject);
        foreach ($attachments as $attachment) {
            $attArr = $utils->entityToArray($attachment, 0);
            unset($attArr['id'], $attArr['ownerId'], $attArr['createdAt'], $attArr['updatedAt']);

            $newAttachment = new Attachment();
            $utils->entityFromArray($newAttachment, $attArr);

            $newAttachment
                ->setOwnerId($message->getId())
                ->setMedia($attachment->getMedia())
            ;

            $em->persist($newAttachment);
        }

        $em->flush();
        $this->addFlash(
            'sonata_flash_success',
            $this->trans('hg_email.messages.copy_success')
        );

        return $this->redirectToList();
    }

    /**
     * Redirect the user depend on this choice.
     *
     * @param object $object
     *
     * @return RedirectResponse
     */
    protected function redirectTo(Request $request, object $object): RedirectResponse
    {
        if (null !== $request->get('btn_update_and_prepare')) {
            return $this->redirect($this->admin->generateObjectUrl('prepare', $object));
        }

        return parent::redirectTo($request, $object);
    }

    protected function preEdit(Request $request, object $object): ?Response
    {
        if (!$object->isPrepareable()) {
            $this->addFlash(
                'sonata_flash_error',
                $this->trans('hg_email.messages.not_editable')
            );

            return $this->redirectToList();
        }

        parent::preEdit($request, $object); // TODO: Change the autogenerated stub
    }
}
