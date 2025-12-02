<?php

namespace Hgabka\EmailBundle\Layout;

use Hgabka\EmailBundle\Helper\MailBuilder;
use Hgabka\EmailBundle\Model\AbstractLayoutVar;

class SenderEmailLayoutVar extends AbstractLayoutVar
{
    public function __construct(protected readonly MailBuilder $mailBuilder)
    {
    }

    public function getPlaceholder(): string
    {
        return $this->mailBuilder->translateDefaultVariable('hg_email.variables.from') . '_' . $this->mailBuilder->translateDefaultVariable('hg_email.variables.email');
    }
}
