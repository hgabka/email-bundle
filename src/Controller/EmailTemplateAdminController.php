<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Form\RecipientsType;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class EmailTemplateAdminController extends CRUDController
{
    public function addRecipientAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->createNotFoundException();
        }
        $recipientManager = $this->get('hg_email.recipient_manager');
        $fieldName = $request->get('fieldType').'Data';

        $type = $request->get('type');
        $recType = $recipientManager->getType($type);

        $builder = $this
            ->get('form.factory')
            ->createNamedBuilder($request->get('name'))
            ->add($fieldName, RecipientsType::class, [
                'admin' => $this->admin,
                'template_type' => $this->admin->getSubject()->getType(),
                'recipients_type' => $request->get('fieldtype'),
            ])
        ;

        $key = uniqid('regtype_');
        $builder->get($fieldName)->add($recipientManager->createTypeFormBuilder($key, $type));

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
