<?php

namespace Hgabka\EmailBundle\Model;

interface LayoutVarInterface
{
    public function getPlaceholder();

    public function getLabel();

    public function getValue($layoutHtml, $bodyHtml, $mail, $params, $locale);

    public function setPriority($priority);

    public function getPriority();
}
