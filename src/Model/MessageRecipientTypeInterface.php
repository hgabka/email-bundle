<?php

namespace Hgabka\EmailBundle\Model;

interface MessageRecipientTypeInterface extends RecipientTypeInterface
{
    public function getMessageVariables();

    public function getVariableValues($params);

    public function alterHtmlBody($html, $params);
}
