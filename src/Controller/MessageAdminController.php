<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Form\MessageRecipientsType;
use Hgabka\EmailBundle\Form\MessageSendType;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class MessageAdminController extends CRUDController
{
    public function addRecipientAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->createNotFoundException();
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

    public function prepareAction()
    {
        $request = $this->getRequest();
        // the key used to lookup the template
        $templateKey = 'edit';

        $id = $request->get($this->admin->getIdParameter());
        $existingObject = $this->admin->getObject($id);

        if (!$existingObject) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id: %s', $id));
        }

        $this->checkParentChildAssociation($request, $existingObject);

        $this->admin->checkAccess('send', $existingObject);

        $form = $this->createForm(MessageSendType::class, $existingObject);

        $form->handleRequest($request);
    }
}
