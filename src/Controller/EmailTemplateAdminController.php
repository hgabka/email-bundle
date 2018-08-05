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

        $type = $request->get('type');
        $recType = $recipientManager->getType($type);

        $builder = $this
            ->get('form.factory')
            ->createNamedBuilder($request->get('name'))
            ->add('toData', RecipientsType::class, [
                'admin' => $this->admin,
                'template_type' => $this->admin->getSubject()->getType(),
            ])
        ;

        $key = uniqid('regtype_');
        $builder->get('toData')->add($recipientManager->createTypeFormBuilder($key, $type));

        $form = $builder->getForm();

        $html = $this->renderView('@HgabkaEmail/Admin/recipient_type_form.html.twig', [
            'form' => $form->createView(),
            'key' => $key,
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }
}
