<?php

namespace Hgabka\EmailBundle\Controller;

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
        $recipientManager = $this->get('hg_email.recipient_manager');
        $fieldName = $request->get('fieldType').'Data';

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
}
