<?php

namespace Hgabka\EmailBundle\Controller;

use Hgabka\EmailBundle\Entity\MessageQueue;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class MessageController extends Controller
{
    /**
     * The webversion action.
     *
     * @Route("/{id}/webversion/{hash}", name="hgabka_email_message_webversion", requirements={"id" = "\d+"}, methods={"GET"})
     *
     * @param Request $request
     * @param mixed   $id
     * @param mixed   $hash
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
        $params['vars']['webveriosn'] = $router->generate('hgabka_email_message_webversion', ['id' => $queue->getId(), 'hash' => $queue->getHash()], UrlGeneratorInterface::ABSOLUTE_URL);

        ['bodyHtml' => $bodyHtml] = $mailBuilder->createMessageMail($queue->getMessage(), $to, $queue->getLocale(), true, $params, $recType);

        return new Response($bodyHtml);
    }

    /**
     * The unsubscribe action.
     *
     * @Route("/unsubscribe/{token}", name="hgabka_email_message_unsubscribe")
     *
     * @param Request $request
     * @param mixed   $token
     *
     * @return Response
     */
    public function unsubscribeAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        $subscr = $em
            ->getRepository('HgabkaKunstmaanEmailBundle:MessageSubscriber')
            ->findOneByToken($token)
        ;

        if (!$subscr) {
            throw new $this->createNotFoundException('Missing subscriber');
        }

        if ($request->query->has('list_id')) {
            $list = $em
                ->getRepository('HgabkaKunstmaanEmailBundle:MessageList')
                ->findOneById($request->query->get('list_id'))
            ;

            if ($list) {
                $sub = $em
                    ->getRepository('HgabkaKunstmaanEmailBundle:MessageListSubscription')
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

        return $this->render('HgabkaKunstmaanEmailBundle:Message:unsubscribe.html.twig');
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
