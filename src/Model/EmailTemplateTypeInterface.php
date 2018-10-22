<?php

namespace Hgabka\EmailBundle\Model;

interface EmailTemplateTypeInterface
{
    public function getComment();

    public function getDefaultSubject();

    public function getDefaultTextContent();

    public function getDefaultHtmlContent();

    public function getVariables();

    public function getVariableValues();

    public function getDefaultFromName();

    public function getDefaultFromEmail();

    public function getTitle();

    public function getDefaultRecipients();

    public function isPublic();

    public function isToEditable();

    public function isCcEditable();

    public function isBccEditable();

    public function isSenderEditable();

    public function getSenderText();

    public function setLocale($locale);
}
