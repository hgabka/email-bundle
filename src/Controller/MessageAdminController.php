<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Entity\Message;
use Hgabka\EmailBundle\Form\MessageRecipientsType;
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

        $html = $this->renderView('@HgabkaEmail/Admin/recipient_type_form.html.twig', [
            'form' => $form->createView(),
            'key' => $key,
            'fieldName' => $fieldName,
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
}
