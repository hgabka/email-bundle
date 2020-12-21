<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;

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

    protected function preList(Request $request)
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
    }

    protected function preCreate(Request $request, $object)
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
    }

    protected function preEdit(Request $request, $object)
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
    }

    protected function preDelete(Request $request, $object)
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
    }

    protected function preShow(Request $request, $object)
    {
        if (!$this->mailBuilder->layoutsEditable()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
    }
}
