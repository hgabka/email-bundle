<?php

namespace Hgabka\EmailBundle\Model;

use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractMessageRecipientType extends AbstractRecipientType implements MessageRecipientTypeInterface
{
    public function getMessageVariables()
    {
        return [];
    }

    public function alterHtmlBody($html, $params, $locale)
    {
        return $html;
    }

    public function getVariableValues($params)
    {
        if (empty($this->getMessageVariables())) {
            return [];
        }
        $result = [];
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach ($this->getMessageVariables() as $placeholder => $varData) {
            if (empty($varData['value'])) {
                continue;
            }

            $result[$placeholder] = \is_callable($varData['value']) ? \call_user_func($varData['value'], $params) : $accessor->getValue($params, '['.$varData['value'].']');
        }

        return $result;
    }

    public function getFormTemplate()
    {
        return null;
    }
}
