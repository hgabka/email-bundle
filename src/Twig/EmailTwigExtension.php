<?php

namespace Hgabka\EmailBundle\Twig;

use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\ParamSubstituter;
use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Helper\TemplateTypeManager;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var MailBuilder */
    protected $mailBuilder;

    /** @var ParamSubstituter */
    protected $paramSubstituter;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var RecipientManager */
    protected $recipientManager;

    /** @var TemplateTypeManager */
    protected $templateTypeManager;

    /**
     * PublicTwigExtension constructor.
     *
     * @param MailBuilder $mailBuilder
     */
    public function __construct(MailBuilder $mailBuilder, ParamSubstituter $paramSubstituter, TranslatorInterface $translator, RecipientManager $recipientManager, TemplateTypeManager $templateTypeManager)
    {
        $this->mailBuilder = $mailBuilder;
        $this->paramSubstituter = $paramSubstituter;
        $this->translator = $translator;
        $this->recipientManager = $recipientManager;
        $this->templateTypeManager = $templateTypeManager;
    }

    public function getGlobals()
    {
        return [
            'mail_builder' => $this->mailBuilder,
            'template_type_manager' => $this->templateTypeManager,
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'render_usable_vars',
                [$this, 'renderUsableVars'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'render_recipient_selector',
                [$this, 'renderRecipientSelector'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function renderUsableVars(\Twig_Environment $environment, EmailTemplate $template)
    {
        $type = $this->templateTypeManager->getTemplateType($template->getType());

        if (empty($type)) {
            return '';
        }

        $vars = array_flip($this->mailBuilder->getFromToParams());

        foreach ($type->getVariables() as $placeholder => $varData) {
            $vars[$placeholder] = $this->translator->trans($varData['label']);
        }

        $vars = $this->paramSubstituter->addVarChars($vars);

        return $environment->render('@HgabkaEmail/Admin/_usable_vars.html.twig', ['vars' => $vars]);
    }

    public function renderRecipientSelector($id)
    {
        $choices = $this->recipientManager->getTemplateRecipientTypeChoices();
        $html = '<select id="rectype-select_'.$id.'">
                     <option value=""></option>';
        foreach ($choices as $label => $type) {
            $html .= '<option value="'.$type.'">'.$this->translator->trans($label).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'hgabka_emailbundle_twig_extension';
    }
}
