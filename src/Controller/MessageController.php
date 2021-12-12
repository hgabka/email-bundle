<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Entity\MessageList;
use Hgabka\EmailBundle\Entity\MessageListSubscription;
use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class MessageController extends AbstractController
{
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
    public function webversionAction(Request $request, RouterInterface $router, MailBuilder $mailBuilder, RecipientManager $recipientManager, $id, $hash)
    {
        /** @var MessageQueue $queue */
        $queue = $this->getDoctrine()->getRepository(MessageQueue::class)->find($id);
        if (!$queue || $queue->getHash() !== $hash) {
            throw $this->createNotFoundException('Invalid message');
        }
        $toName = $queue->getToName();
        $toEmail = $queue->getToEmail();

        $to = empty($toName) ? $toEmail : [$toEmail => $toName];
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
        $params['vars']['webversion'] = $router->generate('hgabka_email_message_webversion', ['id' => $queue->getId(), 'hash' => $queue->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

        ['bodyHtml' => $bodyHtml] = $mailBuilder->createMessageMail($queue->getMessage(), $to, $queue->getLocale(), true, $params, $recType, false);

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
    public function unsubscribeAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

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
            $name = $this->get('translator')->trans($this->get('hgabka_kunstmaan_email.mail_builder')->getConfig()['default_name']);
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
