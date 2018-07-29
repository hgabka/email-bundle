<?php

namespace Hgabka\KunstmaanEmailBundle\Twig;

use Hgabka\KunstmaanEmailBundle\Helper\MailBuilder;

class EmailTwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var MailBuilder
     */
    protected $mailBuilder;

    /**
     * PublicTwigExtension constructor.
     *
     * @param MailBuilder $mailBuilder
     */
    public function __construct(MailBuilder $mailBuilder)
    {
        $this->mailBuilder = $mailBuilder;
    }

    public function getGlobals()
    {
        return ['mail_builder' => $this->mailBuilder];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'hgabka_kunstmaanemailbundle_twig_extension';
    }
}
