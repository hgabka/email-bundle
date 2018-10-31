<?php

namespace Hgabka\EmailBundle\Recipient;

use Hgabka\EmailBundle\Entity\MessageSubscriber;
use Hgabka\EmailBundle\Helper\SubscriptionManager;
use Hgabka\EmailBundle\Model\AbstractMessageRecipientType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SubscribersMessageRecipientType extends AbstractMessageRecipientType
{
    /** @var SubscriptionManager */
    protected $subscriptionManager;

    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /**
     * SubscribersMessageRecipientType constructor.
     *
     * @param SubscriptionManager $subscriptionManager
     */
    public function __construct(SubscriptionManager $subscriptionManager, HgabkaUtils $hgabkaUtils)
    {
        $this->subscriptionManager = $subscriptionManager;
        $this->hgabkaUtils = $hgabkaUtils;
    }

    public function getName()
    {
        return 'hg_email.recipient_type.subscribers.name';
    }

    public function getTitle()
    {
        return $this->translator->trans('hg_email.recipient_type.subscribers.title', ['%count%' => $this->getRecipientCount()]);
    }

    public function addFormFields(FormBuilderInterface $formBuilder)
    {
        if ($this->subscriptionManager->isEditableLists()) {
            $formBuilder
                ->add('lists', ChoiceType::class, [
                    'label' => 'hg_email.label.lists',
                    'required' => true,
                ]);
        }
        $formBuilder
            ->add('addUnsubscribe', CheckboxType::class, [
                'label' => 'hg_email.label.add_unsubscribe_link',
                'required' => false,
            ])
        ;
        foreach ($this->hgabkaUtils->getAvailableLocales() as $locale) {
            $formBuilder
                ->add(
                    'linkText_'.$locale,
                    TextType::class,
                    [
                'label' => $this->translator->trans('hg_email.label.unsubscribe_link_text', ['%locale%' => $this->hgabkaUtils->getIntlLocale($locale)]),
                'required' => false,
                'attr' => [
                    'class' => 'unsub-link-text',
                ],
                        ]
                )
            ;
        }
    }

    public function isPublic()
    {
        return true;
    }

    public function getPriority()
    {
        return 0;
    }

    public function getParams()
    {
        if (\is_array($this->params)) {
            $this->params['addUnsubscribe'] = array_key_exists('addUnsubscribe', $this->params) ? (bool) $this->params['addUnsubscribe'] : false;
        }

        return $this->params;
    }

    public function getMessageVariables()
    {
        return [
            'date' => [
                'label' => 'hg_email.label.subscription_date',
                'value' => 'subscriptionDate',
            ],
        ];
    }

    public function getParamDefaults()
    {
        $defaults = [
            'addUnsubscribe' => true,
        ];

        foreach ($this->hgabkaUtils->getAvailableLocales() as $locale) {
            $defaults['linkText_'.$locale] = $this->translator->trans('hg_email.title.unsubscribe', [], 'messages', $locale);
        }

        return $defaults;
    }

    public function alterHtmlBody($html, $params)
    {
        $unsub = $this->getParams()['addUnsubscribe'] ?? null;
        if (!$unsub) {
            return $html;
        }

        return $html.'<br />'.$params['token'];
    }

    public function getFormTemplate()
    {
        return '@HgabkaEmail/Admin/Message/subscriber_recipient_form.html.twig';
    }

    protected function computeRecipients()
    {
        $lists = $this->getParams()['list'] ?? null;
        $subscribers = $this->subscriptionManager->getSubscribers($lists);

        $result = [];
        foreach ($subscribers as $subscriber) {
            /** @var MessageSubscriber $subscriber */
            $params = [
                'token' => $subscriber->getToken(),
                'subscriptionDate' => $subscriber->getCreatedAt()->format('Y-m-d'),
            ];

            $result[] = [
                'to' => [$subscriber->getEmail() => $subscriber->getName()],
                'locale' => $subscriber->getLocale(),
                'vars' => $this->getVariableValues($params),
                'params' => $params,
            ];
        }

        return $result;
    }
}
