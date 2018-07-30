<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Entity\MessageListSubscription;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;

class SubscriptionManager
{
    /** @var Registry */
    protected $doctrine;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var bool */
    protected $editableLists;

    /**
     * SubscriptionManager constructor.
     *
     * @param Registry    $doctrine
     * @param HgabkaUtils $hgabkaUtils
     * @param bool        $editableLists
     */
    public function __construct(Registry $doctrine, HgabkaUtils $hgabkaUtils, bool $editableLists)
    {
        $this->doctrine = $doctrine;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->editableLists = $editableLists;
    }

    public function addSubscriberToLists(MessageSubscriber $subscriber, $lists = null, $withFlush = true)
    {
        $em = $this->doctrine->getManager();
        $subscrRepo = $em->getRepository(MessageListSubscription::class);

        foreach ($this->getListsFromParams($lists) as $list) {
            $existing = $subscrRepo->findForSubscriberAndList($subscriber, $list);
            if (!$existing) {
                $existing = new MessageListSubscription();
                $existing
                    ->setList($list)
                    ->setSubscriber($subscriber)
                ;

                $em->persist($existing);
            }
        }

        if ($withFlush) {
            $em->flush();
        }
    }

    public function createSubscription($name, $email, $locale = null, $lists = null)
    {
        $em = $this->doctrine->getManager();
        $existing = $em->getRepository(MessageSubscriber::class)->findOneByEmail($email);
        if (!$existing) {
            $existing = new MessageSubscriber();
            $existing
                ->setName($name)
                ->setEmail($email)
                ->setLocale($locale ?? $this->hgabkaUtils->getCurrentLocale())
            ;

            $em->persist($existing);
        }

        $this->addSubscriberToLists($existing, $lists);
    }

    public function deleteSubscription($email, $lists = null)
    {
        $em = $this->doctrine->getManager();
        $existing = $em->getRepository(MessageSubscriber::class)->findOneByEmail($email);

        if (!$existing) {
            return;
        }

        if (null === $lists) {
            $em->remove($existing);
            $em->flush();

            return;
        }
        $subscrRepo = $em->getRepository(MessageListSubscription::class);

        foreach ($this->getListsFromParams($lists) as $list) {
            $subscr = $subscrRepo->findForSubscriberAndList($existing, $list);
            $em->remove($subscr);
        }

        $em->flush();
    }

    public function generateSubscriberToken(MessageSubscriber $subscriber)
    {
        if (!empty($subscriber->getToken())) {
            return;
        }
        $sku = md5(microtime());
        $repo = $this->doctrine->getManager()->getRepository(MessageSubscriber::class);

        while ($existing = $repo->findOneByToken($sku)) {
            $sku = md5(microtime());
        }

        $subscriber->setToken($sku);
    }

    protected function getListsFromParams($lists)
    {
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository(MessageList::class);

        if (empty($lists) || !$this->editableLists) {
            $lists = [
                $repo->getDefaultList(),
            ];
        }

        if (is_int($lists)) {
            $lists = [
                $repo->find($lists),
            ];
        }
        if (!is_array($lists)) {
            $lists = [$lists];
        }

        foreach ($lists as $key => $list) {
            if (is_int($list)) {
                $lists[$key] = $repo->find($list);
            }

            if (!$list instanceof MessageList) {
                unset($lists[$key]);
            }
        }

        return $lists;
    }
}
