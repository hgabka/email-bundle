<?php

namespace Hgabka\EmailBundle\Model;

interface EmailTemplateTypeInterface
{
    public function getName();

    public function getComment();

    public function getDefaultSubject();

    public function getDefaultTextContent();

    public function getDefaultHtmlContent();

    public function getVariables();
}
