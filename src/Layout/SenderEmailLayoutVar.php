<?php

namespace Hgabka\EmailBundle\Layout;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Model\AbstractLayoutVar;

class SenderEmailLayoutVar extends AbstractLayoutVar
{
    /** @var MailBuilder */
    protected $mailBuilder;

    /**
     * SenderNameLayoutVar constructor.
     *
     * @param $mailBuilder
     */
    public function __construct(MailBuilder $mailBuilder)
    {
        $this->mailBuilder = $mailBuilder;
    }

    public function getPlaceholder()
    {
        return $this->mailBuilder->translateDefaultVariable('hg_email.variables.from') . '_' . $this->mailBuilder->translateDefaultVariable('hg_email.variables.email');
    }
}
