<?php

namespace Hgabka\EmailBundle\Model;

interface LayoutVarInterface
{
    public function getPlaceholder();

    public function getLabel();

    public function getValue($bodyHtml, $params, $locale);

    public function setPriority($priority);

    public function getPriority();
}
