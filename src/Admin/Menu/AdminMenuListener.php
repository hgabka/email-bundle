<?php

namespace Hgabka\EmailBundle\Admin\Menu;

use Doctrine\Common\Persistence\ManagerRegistry;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Sonata\AdminBundle\Admin\Pool;
use Sonata\AdminBundle\Event\ConfigureMenuEvent;

class AdminMenuListener
{
    /** @var Pool */
    protected $adminPool;

    /** @var SubscriptionManager */
    protected $subscriptionManager;

    /** @var MailBuilder */
    protected $mailBuilder;

    /**
     * AdminMenuListener constructor.
     *
     * @param MediaAdmin      $mediaAdmin
     * @param ManagerRegistry $doctrine
     * @param FolderManager   $folderManager
     */
    public function __construct(Pool $adminPool, SubscriptionManager $subscriptionManager, MailBuilder $mailBuilder)
    {
        $this->adminPool = $adminPool;
        $this->subscriptionManager = $subscriptionManager;
        $this->mailBuilder = $mailBuilder;
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
            $subscrCh = $group->getChild('hg_email.admin.subscriber.label');
            if ($subscrCh) {
                $subscrCh->setExtra('icon', '<i class="fa fa-users"></i>');
            }
            $listCh = $group->getChild('hg_email.admin.message_list.label');
            if ($listCh) {
                if (!$this->subscriptionManager->isEditableLists()) {
                    $group->removeChild($listCh);
                } else {
                    $listCh->setExtra('icon', '<i class="fa fa-list"></i>');
                }
            }
            $layoutCh = $group->getChild('hg_email.admin.email_layout.label');
            if ($layoutCh) {
                if (!$this->mailBuilder->layoutsEditable()) {
                    $group->removeChild($listCh);
                } else {
                    $layoutCh->setExtra('icon', '<i class="fa fa-image"></i>');
                }
            }
        }
    }
}
