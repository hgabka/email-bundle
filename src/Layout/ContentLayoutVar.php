<?php

namespace Hgabka\EmailBundle\Layout;

use Hgabka\EmailBundle\Model\AbstractLayoutVar;

class ContentLayoutVar extends AbstractLayoutVar
{
    public function getValue($layoutHtml, $bodyHtml, $mail, $params, $locale)
    {
        return $bodyHtml;
    }
}
