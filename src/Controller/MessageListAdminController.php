<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageListAdminController extends CRUDController
{
    /** @var SubscriptionManager */
    protected $subscriptionManager;

    /**
     * MessageListAdminController constructor.
     */
    public function __construct(SubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;
    }

    protected function preList(Request $request): ?Response
    {
        if (!$this->subscriptionManager->isEditableLists()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }
    }

    protected function preCreate(Request $request, object $object): ?Response
    {
        if (!$this->subscriptionManager->isEditableLists()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return null;
    }

    protected function preEdit(Request $request, object $object): ?Response
    {
        if (!$this->subscriptionManager->isEditableLists()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return null;
    }

    protected function preDelete(Request $request, object $object): ?Response
    {
        if (!$this->subscriptionManager->isEditableLists()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return null;
    }

    protected function preShow(Request $request, object $object): ?Response
    {
        if (!$this->subscriptionManager->isEditableLists()) {
            return $this->redirectToRoute('sonata_admin_dashboard');
        }

        return null;
    }
}
