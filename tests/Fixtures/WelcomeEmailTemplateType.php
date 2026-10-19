<?php

namespace Hgabka\EmailBundle\Tests\Fixtures;

use Hgabka\EmailBundle\Annotation\TemplateVar;
use Hgabka\EmailBundle\Model\AbstractEmailTemplateType;

class WelcomeEmailTemplateType extends AbstractEmailTemplateType
{
    #[TemplateVar(label: 'Recipient name', placeholder: 'name')]
    protected string $userName = 'Ann';

    #[TemplateVar(type: 'block')]
    protected string $intro = '<p>Hi</p>';

    protected string $hidden = 'not exposed';

    #[TemplateVar(placeholder: 'order_total')]
    public function getTotal(): string
    {
        return '1 200 Ft';
    }

    #[TemplateVar]
    public function getOrderNumber(): int
    {
        return 42;
    }

    public function setUserName(string $userName): self
    {
        $this->userName = $userName;

        return $this;
    }

    public function getIntro(): string
    {
        return $this->intro;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }
}
