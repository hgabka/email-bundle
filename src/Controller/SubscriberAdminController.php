<?php

namespace Hgabka\EmailBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;

class SubscriberAdminController extends CRUDController
{
    protected function preEdit(Request $request, $object)
    {
        $object->setLists($this->admin->getManager()->getListsForSubscriber($object));
    }
}
