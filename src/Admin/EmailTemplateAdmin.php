<?php

namespace Hgabka\EmailBundle\Admin;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EmailTemplateAdmin extends AbstractAdmin
{
    /** @var MailBuilder */
    private $builder;

    /** @var AuthorizationCheckerInterface */
    private $authChecker;

    public function setBuilder(MailBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function setAuthChecker(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function createQuery($context = 'list')
    {
        $this->builder->getTemplateTypeEntities();

        return parent::createQuery($context);
    }

    public function hasAccess($action, $object = null)
    {
        if ('edit' === $action) {
            return $this->authChecker->isGranted($this->getConfigurationPool()->getContainer()->getParameter('hgabka_email.editor_role'));
        }

        return parent::hasAccess($action, $object);
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(['edit', 'list']);
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('name', null, [
                'label' => 'hg_email.label.name',
            ])
            ->add('comment', null, [
                'label' => 'hg_email.label.comment',
            ])
        ;
    }
}
