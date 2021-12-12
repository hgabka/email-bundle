<?php

namespace Hgabka\EmailBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriberAdminController extends CRUDController
{
    protected function preEdit(Request $request, object $object): ?Response
    {
        $object->setLists($this->admin->getManager()->getListsForSubscriber($object));

        return parent::preEdit($request, $object);
    }
}
