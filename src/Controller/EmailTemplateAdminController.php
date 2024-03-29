<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Form\EmailTemplateRecipientsType;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\Attribute\Required;

class EmailTemplateAdminController extends CRUDController
{
    /** @var FormFactoryInterface */
    protected FormFactoryInterface $formFactory;

    #[Required]
    public function setFormFactory(FormFactoryInterface $formFactory): self
    {
        $this->formFactory = $formFactory;

        return $this;
    }

    public function addRecipientAction(Request $request, RecipientManager $recipientManager)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->createNotFoundException();
        }
        $id = $request->query->get($this->admin->getIdParameter());
        $existingObject = $this->admin->getObject($id);

        $this->admin->setSubject($existingObject);
        $fieldName = $request->get('fieldType') . 'Data';

        $type = $request->get('type');
        $recType = $recipientManager->getTemplateRecipientType($type);

        $builder = $this
            ->formFactory
            ->createNamedBuilder($request->get('name'))
            ->add($fieldName, EmailTemplateRecipientsType::class, [
                'admin' => $this->admin,
                'template_type' => $this->admin->getSubject()->getType(),
                'recipients_type' => $request->get('fieldtype'),
            ])
        ;

        $key = uniqid('regtype_');
        $builder->get($fieldName)->add($recipientManager->createTemplateRecipientTypeFormBuilder($key, $type));

        $form = $builder->getForm();

        $html = $this->renderView('@HgabkaEmail/Admin/recipient_type_form.html.twig', [
            'child' => $form->get($fieldName)->get($key)->createView(),
            'key' => $key,
            'ajax' => true,
        ]);

        return new JsonResponse([
            'html' => $html,
        ]);
    }
}
