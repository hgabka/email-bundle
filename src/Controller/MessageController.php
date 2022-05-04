<?php

namespace Hgabka\EmailBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Entity\MessageListSubscription;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageController extends AbstractController
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * The webversion action.
     *
     * @Route("/{id}/webversion/{hash}", name="hgabka_email_message_webversion", requirements={"id" = "\d+"}, methods={"GET"})
     *
     * @param mixed $id
     * @param mixed $hash
     *
     * @return Response
     */
    public function webversionAction(Request $request, RouterInterface $router, MailBuilder $mailBuilder, RecipientManager $recipientManager, ManagerRegistry $doctrine, $id, $hash)
    {
        /** @var MessageQueue $queue */
        $queue = $doctrine->getRepository(MessageQueue::class)->find($id);
        if (!$queue || $queue->getHash() !== $hash) {
            throw $this->createNotFoundException('Invalid message');
        }
        $toName = $queue->getToName();
        $toEmail = $queue->getToEmail();

        $to = empty($toName) ? new Address($toEmail) : new Address($toEmail, $toName);
        $params = json_decode($queue->getParameters(), true);

        if (!isset($params['type'])) {
            return false;
        }
        $recType = $recipientManager->getMessageRecipientType($params['type']);
        if (!$recType) {
            return false;
        }
        $recType->setParams($params['typeParams'] ?? []);
        if (!isset($params['vars'])) {
            $params['vars'] = [];
        }

        ['bodyHtml' => $bodyHtml] = $mailBuilder->createMessageMail($queue->getMessage(), $to, $queue->getLocale(), true, $params, $recType, false, true, $queue);

        return new Response($bodyHtml);
    }

    /**
     * The unsubscribe action.
     *
     * @Route("/unsubscribe/{token}", name="hgabka_email_message_unsubscribe")
     *
     * @param mixed $token
     *
     * @return Response
     */
    public function unsubscribeAction(Request $request, ManagerRegistry $doctrine, $token)
    {
        $em = $doctrine->getManager();

        $subscr = $em
            ->getRepository(MessageSubscriber::class)
            ->findOneByToken($token)
        ;

        if (!$subscr) {
            throw $this->createNotFoundException('Missing subscriber');
        }

        if ($request->query->has('list_id')) {
            $list = $em
                ->getRepository(MessageList::class)
                ->findOneById($request->query->get('list_id'))
            ;

            if ($list) {
                $sub = $em
                    ->getRepository(MessageListSubscription::class)
                    ->findForSubscriberAndList($subscr, $list)
                ;

                if ($sub) {
                    $em->remove($sub);
                    $em->flush();
                }
            }
        } else {
            $em->remove($subscr);
            $em->flush();
        }

        return $this->render('@HgabkaEmail/Message/unsubscribe.html.twig');
    }

    /**
     * @param $layout
     * @param $subject
     * @param $bodyHtml
     * @param $name
     * @param $email
     *
     * @return string
     */
    protected function applyLayout($layout, $subject, $bodyHtml, $name, $email)
    {
        if (empty($name)) {
            $name = $this->translator->trans($this->get('hgabka_kunstmaan_email.mail_builder')->getConfig()['default_name']);
        }

        return strtr($layout, [
            '%%host%%' => $this->get('hgabka_kunstmaan_extension.kuma_utils')->getSchemeAndHttpHost(),
            '%%styles%%' => '',
            '%%title%%' => $subject,
            '%%content%%' => $bodyHtml,
            '%%name%%' => $name,
            '%%email%%' => $email,
        ]);
    }
}
