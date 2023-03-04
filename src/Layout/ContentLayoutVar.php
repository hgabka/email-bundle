<?php

namespace Hgabka\EmailBundle\Layout;

use Hgabka\EmailBundle\Model\AbstractLayoutVar;
use Symfony\Component\Mime\Email;

class ContentLayoutVar extends AbstractLayoutVar
{
    public function getValue(
        ?string $layoutHtml,
        ?string $bodyHtml,
        ?Email $mail,
        ?array $params,
        ?string $locale,
        bool $webversion = false,
    ): ?string {
        return $bodyHtml;
    }
}
