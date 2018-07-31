<?php

namespace Hgabka\EmailBundle\Model;

interface EmailTemplateTypeInterface
{
    public function getComment();

    public function getDefaultSubject();

    public function getDefaultTextContent();

    public function getDefaultHtmlContent();

    public function getVariables($onlyNames = false);

    public function getDefaultFromName();

    public function getDefaultFromEmail();

    public function getTitle();
}
