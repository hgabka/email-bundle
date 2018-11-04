<?php

namespace Hgabka\EmailBundle\Layout;

use Hgabka\EmailBundle\Model\AbstractLayoutVar;

class ContentLayoutVar extends AbstractLayoutVar
{
    public function getValue($bodyHtml, $params, $locale)
    {
        return $bodyHtml;
    }
}
