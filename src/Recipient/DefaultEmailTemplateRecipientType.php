<?php

namespace Hgabka\EmailBundle\Recipient;

use Hgabka\EmailBundle\Model\AbstractEmailTemplateRecipientType;
use Hgabka\UtilsBundle\Form\Type\StaticControlType;
use Symfony\Component\Form\FormBuilderInterface;

class DefaultEmailTemplateRecipientType extends AbstractEmailTemplateRecipientType
{
    public function getName()
    {
        return 'hg_email.recipient_type.default.name';
    }

    public function getTitle()
    {
        return $this->translator->trans('hg_email.recipient_type.default.title', ['%count%' => $this->getRecipientCount()]);
    }

    public function addFormFields(FormBuilderInterface $formBuilder)
    {
        $html = $this->getRecipientCount() > 0 ? '<ul>' : '';
        if ($this->getRecipientCount() > 0) {
            foreach (array_map([$this, 'getRecipientDisplay'], $this->getRecipients()) as $r) {
                $html .= '<li>'.$r.'</li>';
            }
            $html .= '</ul>';
        }

        $formBuilder
            ->add('info', StaticControlType::class, [
                'label' => false,
                'html' => $this->translator->trans(
                    'hg_email.recipient_type.default.info',
                    [
                        '%recipients%' => $html,
                        '%count%' => $this->getRecipientCount(),
                    ]
                ),
            ]);
    }

    public function isPublic()
    {
        return true;
    }

    public function getPriority()
    {
        return 0;
    }

    protected function computeRecipients()
    {
        return $this->builder->getDefaultTo();
    }
}
