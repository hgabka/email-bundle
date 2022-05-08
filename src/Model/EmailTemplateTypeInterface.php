<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\Mime\Email;

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

    public function getPriority();

    public function setPriority($priority);

    public function getDefaultCc();

    public function getDefaultBcc();

    public function setParameters($paramArray);

    public function alterEmail(Email $email): void;
}
