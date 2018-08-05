<?php

namespace Hgabka\EmailBundle\Recipient;

use Hgabka\EmailBundle\Model\AbstractRecipientType;
use Hgabka\UtilsBundle\Form\Type\LocaleType;
use Hgabka\UtilsBundle\Helper\HgabkaUtils;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class GeneralRecipientType extends AbstractRecipientType
{
    /** @var HgabkaUtils */
    protected $hgabkaUtils;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * GeneralRecipientType constructor.
     *
     * @param HgabkaUtils $hgabkaUtils
     */
    public function __construct(HgabkaUtils $hgabkaUtils, TranslatorInterface $translator)
    {
        $this->hgabkaUtils = $hgabkaUtils;
        $this->translator = $translator;
    }

    public function getName()
    {
        return 'hg_email.recipient_type.general.name';
    }

    public function getTitle()
    {
        return $this->translator->trans('hg_email.recipient_type.general.title', ['%recipient%' => $this->getRecipientDisplay($this->getRecipient())]);
    }

    public function addFormFields(FormBuilderInterface $formBuilder)
    {
        $formBuilder
            ->add('name', TextType::class, [
                'label' => 'hg_email.recipient_type.general.label.name',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'hg_email.recipient_type.general.label.email',
                'required' => false,
                'constraints' => [
                    new NotBlank(),
                    new Email(),
                ],
            ])
        ;

        $locales = $this->hgabkaUtils->getAvailableLocales();
        if (count($locales) > 1) {
            $formBuilder
                ->add('locale', LocaleType::class, [
                    'label' => 'hg_email.recipient_type.general.label.locale',
                    'required' => false,
                ])
            ;
        }
    }

    public function getRecipient()
    {
        return $this->getRecipientText($this->getRecipientEmail(), $this->getRecipientName());
    }

    public function isPublic()
    {
        return true;
    }

    public function getPriority()
    {
        return 1;
    }

    protected function calcRecipients()
    {
        if (empty($this->getRecipientEmail())) {
            return [];
        }

        if (empty($this->getRecipientName())) {
            return $this->getRecipientEmail();
        }

        return [
            'to' => [$this->getRecipientEmail() => $this->getRecipientName()],
            'locale' => $this->getRecipientLocale(),
        ];
    }

    protected function getRecipientName()
    {
        return $this->getParams()['name'] ?? '';
    }

    protected function getRecipientEmail()
    {
        return $this->getParams()['email'] ?? '';
    }

    protected function getRecipientLocale()
    {
        return $this->getParams()['locale'] ?? null;
    }
}
