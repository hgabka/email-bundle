<?php

namespace Hgabka\EmailBundle\Admin\Menu;

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Event\ConfigureMenuEvent;

class AdminMenuListener
{
    /** @var Pool */
    protected $adminPool;

    /**
     * AdminMenuListener constructor.
     *
     * @param MediaAdmin      $mediaAdmin
     * @param ManagerRegistry $doctrine
     * @param FolderManager   $folderManager
     */
    public function __construct(Pool $adminPool)
    {
        $this->adminPool = $adminPool;
    }

    public function addMenuItems(ConfigureMenuEvent $event)
    {
        $emailAdmin = $this->adminPool->getAdminByAdminCode('hg_email.admin.email_template');
        $messageAdmin = $this->adminPool->getAdminByAdminCode('hg_email.admin.message');

        $group = $event->getMenu()->getChild('hg_email.group');
        if ($group) {
            $emailCh = $group->getChild('hg_email.admin.email_template.label');
            if ($emailCh) {
                $emailCh->setExtra('icon', '<i class="fa fa-map"></i>');
            }
            $messageCh = $group->getChild('hg_email.admin.message.label');
            if ($messageCh) {
                $messageCh->setExtra('icon', '<i class="fa fa-envelope"></i>');
            }
        }
    }
}
