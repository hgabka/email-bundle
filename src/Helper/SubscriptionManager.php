<?php

namespace Hgabka\KunstmaanEmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\KunstmaanEmailBundle\Entity\MessageList;
use Hgabka\KunstmaanEmailBundle\Entity\MessageListSubscription;
use Hgabka\KunstmaanEmailBundle\Entity\MessageSubscriber;
use Hgabka\KunstmaanExtensionBundle\Helper\KumaUtils;

class SubscriptionManager
{
    /** @var Registry */
    protected $doctrine;

    /** @var KumaUtils */
    protected $kumaUtils;

    /** @var bool */
    protected $editableLists;

    /**
     * SubscriptionManager constructor.
     *
     * @param Registry  $doctrine
     * @param KumaUtils $kumaUtils
     * @param bool      $editableLists
     */
    public function __construct(Registry $doctrine, KumaUtils $kumaUtils, bool $editableLists)
    {
        $this->doctrine = $doctrine;
        $this->kumaUtils = $kumaUtils;
        $this->editableLists = $editableLists;
    }

    public function addSubscriberToLists(MessageSubscriber $subscriber, $lists = null, $withFlush = true)
    {
        $em = $this->doctrine->getManager();
        $subscrRepo = $em->getRepository('HgabkaKunstmaanEmailBundle:MessageListSubscription');

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
        $existing = $em->getRepository('HgabkaKunstmaanEmailBundle:MessageSubscriber')->findOneByEmail($email);
        if (!$existing) {
            $existing = new MessageSubscriber();
            $existing
                ->setName($name)
                ->setEmail($email)
                ->setLocale($locale ?? $this->kumaUtils->getCurrentLocale())
            ;

            $em->persist($existing);
        }

        $this->addSubscriberToLists($existing, $lists);
    }

    public function deleteSubscription($email, $lists = null)
    {
        $em = $this->doctrine->getManager();
        $existing = $em->getRepository('HgabkaKunstmaanEmailBundle:MessageSubscriber')->findOneByEmail($email);

        if (!$existing) {
            return;
        }

        if (null === $lists) {
            $em->remove($existing);
            $em->flush();

            return;
        }
        $subscrRepo = $em->getRepository('HgabkaKunstmaanEmailBundle:MessageListSubscription');

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
        $repo = $this->doctrine->getManager()->getRepository('HgabkaKunstmaanEmailBundle:MessageSubscriber');

        while ($existing = $repo->findOneByToken($sku)) {
            $sku = md5(microtime());
        }

        $subscriber->setToken($sku);
    }

    protected function getListsFromParams($lists)
    {
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository('HgabkaKunstmaanEmailBundle:MessageList');

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
