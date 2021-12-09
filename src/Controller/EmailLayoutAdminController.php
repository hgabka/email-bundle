<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailLayoutAdminController extends CRUDController
{
    /** @var MailBuilder */
    protected $mailBuilder;

    /**
     * EmailLayoutAdminController constructor.
     */
    public function __construct(MailBuilder $mailBuilder)
    {
        $this->mailBuilder = $mailBuilder;
    }

    protected function preList(Request $request): ?Response
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
        
        return null;
    }

    protected function preCreate(Request $request, object $object): ?Response
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
        
        return null;
    }

    protected function preEdit(Request $request, object $object): ?Response
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
        
        return null;
    }

    protected function preDelete(Request $request, object $object): ?Response
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
        
        return null;
    }

    protected function preShow(Request $request, object $object): ?Response
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
        
        return null;
    }
}
