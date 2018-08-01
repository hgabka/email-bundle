<?php

namespace Hgabka\EmailBundle\Twig;

use Hgabka\EmailBundle\Entity\EmailTemplate;
use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Helper\ParamSubstituter;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /** @var MailBuilder */
    protected $mailBuilder;

    /** @var ParamSubstituter */
    protected $paramSubstituter;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * PublicTwigExtension constructor.
     *
     * @param MailBuilder $mailBuilder
     */
    public function __construct(MailBuilder $mailBuilder, ParamSubstituter $paramSubstituter, TranslatorInterface $translator)
    {
        $this->mailBuilder = $mailBuilder;
        $this->paramSubstituter = $paramSubstituter;
        $this->translator = $translator;
    }

    public function getGlobals()
    {
        return ['mail_builder' => $this->mailBuilder];
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
        ];
    }

    public function renderUsableVars(\Twig_Environment $environment, EmailTemplate $template)
    {
        $type = $this->mailBuilder->getTemplateType($template->getType());

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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'hgabka_emailbundle_twig_extension';
    }
}
