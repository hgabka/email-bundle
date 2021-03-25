<?php

namespace Hgabka\EmailBundle\Layout;

use Hgabka\EmailBundle\Model\AbstractLayoutVar;

class ContentLayoutVar extends AbstractLayoutVar
{
    public function getValue($bodyHtml, $mail, $params, $locale)
    {
        return $bodyHtml;
    }
}
