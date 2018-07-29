<?php

namespace Hgabka\KunstmaanEmailBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Hgabka\KunstmaanEmailBundle\Entity\EmailTemplate;
use Hgabka\KunstmaanEmailBundle\Entity\Message;
use Kunstmaan\AdminListBundle\Event\AdminListEvent;
use Kunstmaan\AdminListBundle\Event\AdminListEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminListSubscriber implements EventSubscriberInterface
{
    /** @var Registry */
    protected $doctrine;

    /**
     * MailerSubscriber constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public static function getSubscribedEvents()
    {
        return [
            AdminListEvents::POST_ADD => 'onPostAdd',
            AdminListEvents::POST_EDIT => 'onPostEdit',
        ];
    }

    public function onPostAdd(AdminListEvent $event)
    {
        $em = $this->doctrine->getManager();

        $object = $event->getEntity();
        if ($object instanceof EmailTemplate) {
            foreach ($object->getTranslations() as $trans) {
                $attRepo = $em->getRepository('HgabkaKunstmaanEmailBundle:Attachment');
                foreach ($attRepo->getByTemplate($trans->getTranslatable(), $trans->getLocale()) as $att) {
                    $em->remove($att);
                }
                foreach ($trans->getAttachments() as $att) {
                    $att
                        ->setType(EmailTemplate::class)
                        ->setOwnerId($object->getId())
                        ->setLocale($trans->getLocale())
                    ;
                    $em->persist($att);
                }
            }

            $em->flush();
        }

        if ($object instanceof Message) {
            foreach ($object->getTranslations() as $trans) {
                $attRepo = $em->getRepository('HgabkaKunstmaanEmailBundle:Attachment');
                foreach ($attRepo->getByMessage($trans->getTranslatable(), $trans->getLocale()) as $att) {
                    $em->remove($att);
                }
                foreach ($trans->getAttachments() as $att) {
                    $att
                        ->setType(Message::class)
                        ->setOwnerId($object->getId())
                        ->setLocale($trans->getLocale())
                    ;
                    $em->persist($att);
                }
            }

            $em->flush();
        }
    }

    public function onPostEdit(AdminListEvent $event)
    {
        $em = $this->doctrine->getManager();

        $object = $event->getEntity();
        if ($object instanceof EmailTemplate) {
            foreach ($object->getTranslations() as $trans) {
                $attRepo = $em->getRepository('HgabkaKunstmaanEmailBundle:Attachment');
                foreach ($attRepo->getByTemplate($trans->getTranslatable(), $trans->getLocale()) as $att) {
                    $em->remove($att);
                }
                foreach ($trans->getAttachments() as $att) {
                    $att
                        ->setType(EmailTemplate::class)
                        ->setOwnerId($object->getId())
                        ->setLocale($trans->getLocale())
                    ;
                    $em->persist($att);
                }
            }

            $em->flush();
        }

        if ($object instanceof Message) {
            foreach ($object->getTranslations() as $trans) {
                $attRepo = $em->getRepository('HgabkaKunstmaanEmailBundle:Attachment');
                foreach ($attRepo->getByMessage($trans->getTranslatable(), $trans->getLocale()) as $att) {
                    $em->remove($att);
                }
                foreach ($trans->getAttachments() as $att) {
                    $att
                        ->setType(Message::class)
                        ->setOwnerId($object->getId())
                        ->setLocale($trans->getLocale())
                    ;
                    $em->persist($att);
                }
            }

            $em->flush();
        }
    }
}
