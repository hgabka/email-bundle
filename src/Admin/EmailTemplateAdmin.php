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

    public function getBatchActions()
    {
        return [];
    }

    public function setAuthChecker(AuthorizationCheckerInterface $authChecker)
    {
        $this->authChecker = $authChecker;
    }

    public function createQuery($context = 'list')
    {
        $this->builder->getTemplateTypeEntities();
        $types = $this->builder->getTemplateTypeClasses();

        $query = parent::createQuery($context);
        $alias = current($query->getRootAliases());

        $orx = $query->expr()->orX();
        $orx->add($alias.'.type IS NULL');

        if (!empty($types)) {
            $orx->add($query->expr()->in($alias.'.type', $types));
        }

        $query->andWhere($orx);

        return $query;
    }

    public function hasAccess($action, $object = null)
    {
        if ('edit' === $action) {
            return $this->authChecker->isGranted($this->getConfigurationPool()->getContainer()->getParameter('hg_email.editor_role'));
        }

        return parent::hasAccess($action, $object);
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(['edit', 'list', 'delete']);
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
            ->add('_action', null, [
                'actions' => [
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }
}
