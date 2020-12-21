<?php

namespace Hgabka\EmailBundle\Helper;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Entity\MessageListSubscription;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\UtilsBundle\Doctrine\Hydrator\KeyValueHydrator;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;

class SubscriptionManager
{
    /** @var Registry */
    protected $doctrine;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var bool */
    protected $editableLists;

    /** @var bool */
    protected $useNames;

    /**
     * SubscriptionManager constructor.
     */
    public function __construct(Registry $doctrine, HgabkaUtils $hgabkaUtils, bool $editableLists, bool $useNames)
    {
        $this->doctrine = $doctrine;
        $this->hgabkaUtils = $hgabkaUtils;
        $this->editableLists = $editableLists;
        $this->useNames = $useNames;
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

    public function updateListSubscriptions(MessageSubscriber $subscriber, $withFlush = true)
    {
        $lists = $subscriber->getLists();

        $em = $this->doctrine->getManager();
        $subscrRepo = $em->getRepository(MessageListSubscription::class);

        if (null !== $lists) {
            $qb = $subscrRepo
                ->createQueryBuilder('l')
                ->delete()
                ->where('l.subscriber = :subscr')
                ->setParameter('subscr', $subscriber)
            ;

            if (!empty($lists)) {
                $qb
                    ->andWhere('l.list NOT IN (:lists)')
                    ->setParameter('lists', $this->getListsFromParams($lists))
                ;
            }

            $qb
                ->getQuery()
                ->execute()
            ;
        }

        $this->addSubscriberToLists($subscriber, $lists, $withFlush);
    }

    public function getListsForSubscriber(MessageSubscriber $subscriber)
    {
        return
            $this
                ->doctrine
                ->getRepository(MessageList::class)
                ->createQueryBuilder('l')
                ->leftJoin('l.listSubscriptions', 'ls')
                ->where('ls.subscriber = :subscr')
                ->setParameter('subscr', $subscriber)
                ->groupBy('l.id')
                ->getQuery()
                ->getResult()
        ;
    }

    public function createSubscription($email, $name = null, $locale = null, $lists = null)
    {
        $em = $this->doctrine->getManager();
        $existing = $em->getRepository(MessageSubscriber::class)->findOneByEmail($email);
        if (!$existing) {
            $existing = new MessageSubscriber();
            $existing
                ->setName($this->useNames ? $name : null)
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

    public function isEditableLists(): bool
    {
        return $this->editableLists;
    }

    /**
     * @param bool $editableLists
     *
     * @return SubscriptionManager
     */
    public function setEditableLists($editableLists)
    {
        $this->editableLists = $editableLists;

        return $this;
    }

    public function isUseNames(): bool
    {
        return $this->useNames;
    }

    public function getListChoices()
    {
        return
            $this
                ->doctrine
                ->getRepository(MessageList::class)
                ->createQueryBuilder('l')
                ->leftJoin('l.translations', 'lt', 'WITH', 'lt.locale = :locale')
                ->setParameter('locale', $this->hgabkaUtils->getCurrentLocale())
                ->orderBy('lt.name')
                ->select('lt.name, l.id')
                ->getQuery()
                ->getResult(KeyValueHydrator::HYDRATOR_NAME)
        ;
    }

    public function getSubscribers($listIds = null)
    {
        if (empty($listIds)) {
            $defList = $this->doctrine->getRepository(MessageList::class)->getDefaultList();
            $listIds = [$defList->getId()];
        }

        return
            $this
                ->doctrine
                ->getRepository(MessageSubscriber::class)
                ->createQueryBuilder('s')
                ->leftJoin('s.listSubscriptions', 'ls')
                ->leftJoin('ls.list', 'l')
                ->where('l.id IN (:listids)')
                ->setParameter('listids', $listIds)
                ->groupBy('s.id')
                ->getQuery()
                ->getResult()
        ;
    }

    public function getSubscriber($id)
    {
        if (empty($id)) {
            return null;
        }

        return $this->doctrine->getRepository(MessageSubscriber::class)->find($id);
    }

    public function getDefaultList()
    {
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository(MessageList::class);

        return $repo->getDefaultList();
    }

    protected function getListsFromParams($lists)
    {
        if ($lists instanceof ArrayCollection) {
            foreach ($lists as $list) {
                if (!$list instanceof MessageList) {
                    $lists->removeElement($list);
                }
            }

            return $lists;
        }

        $em = $this->doctrine->getManager();
        $repo = $em->getRepository(MessageList::class);

        if (null === $lists || !$this->editableLists) {
            $lists = [
                $repo->getDefaultList(),
            ];
        }

        if (\is_array($lists) && empty($lists)) {
            return [];
        }

        if (ctype_digit($lists)) {
            $lists = [
                $repo->find($lists),
            ];
        }
        if (!\is_array($lists)) {
            $lists = [$lists];
        }

        foreach ($lists as $key => $list) {
            if (ctype_digit($list)) {
                $lists[$key] = $repo->find($list);
            }

            if (!$lists[$key] instanceof MessageList) {
                unset($lists[$key]);
            }
        }

        return $lists;
    }
}
