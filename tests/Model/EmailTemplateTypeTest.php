<?php

namespace Hgabka\EmailBundle\Tests\Model;

use Hgabka\EmailBundle\Annotation\TemplateVar;
use Hgabka\EmailBundle\Helper\MessageSender;
use Hgabka\EmailBundle\Tests\Fixtures\WelcomeEmailTemplateType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateTypeTest extends TestCase
{
    public function testTemplateVarAttribute(): void
    {
        $var = new TemplateVar('Label', 'placeholder', 'block');

        $this->assertSame(['Label', 'placeholder', 'block'], [$var->getLabel(), $var->getPlaceholder(), $var->getType()]);
        $var->setLabel('L2')->setPlaceholder('p2')->setType('text');
        $this->assertSame(['L2', 'p2', 'text'], [$var->getLabel(), $var->getPlaceholder(), $var->getType()]);
    }

    public function testVariablesAreCollectedFromPropertiesAndMethods(): void
    {
        $variables = (new WelcomeEmailTemplateType())->getVariables();

        $this->assertEqualsCanonicalizing(['name', 'intro', 'order_total', 'order_number'], array_keys($variables));
        $this->assertSame(['label' => 'Recipient name', 'value' => 'userName'], $variables['name']);
        $this->assertSame('block', $variables['intro']['type']);
        $this->assertSame('mail_template.welcome.variable.intro', $variables['intro']['label']);
        $this->assertSame('mail_template.welcome.variable.order_number', $variables['order_number']['label']);
    }

    public function testVariableValuesAreResolved(): void
    {
        $values = (new WelcomeEmailTemplateType())->setUserName('Éva')->getVariableValues();

        $this->assertSame('Éva', $values['name']['value']);
        $this->assertSame('<p>Hi</p>', $values['intro']['value']);
        $this->assertSame('1 200 Ft', $values['order_total']['value']);
        $this->assertSame('42', $values['order_number']['value']);
    }

    public function testSetParametersUsesThePropertyAccessor(): void
    {
        $type = new WelcomeEmailTemplateType();

        $this->assertSame($type, $type->setParameters(['userName' => 'Béla']));
        $this->assertSame('Béla', $type->getUserName());

        $this->assertSame($type, $type->setParameters(null));
    }

    public function testSetParametersRejectsUnknownProperties(): void
    {
        $this->expectException(\Throwable::class);
        (new WelcomeEmailTemplateType())->setParameters(['doesNotExist' => 1]);
    }

    public function testDefaults(): void
    {
        $type = new WelcomeEmailTemplateType();

        $this->assertSame('', $type->getDefaultSubject());
        $this->assertSame('', $type->getDefaultTextContent());
        $this->assertSame('', $type->getDefaultHtmlContent());
        $this->assertSame([], $type->getDefaultRecipients());
        $this->assertNull($type->getDefaultCc());
        $this->assertNull($type->getDefaultBcc());
        $this->assertNull($type->getSenderText());
        $this->assertTrue($type->isPublic());
        $this->assertTrue($type->isToEditable() && $type->isCcEditable() && $type->isBccEditable() && $type->isSenderEditable());
        $this->assertNull($type->getEntity());
    }

    public function testFluentSetters(): void
    {
        $type = new WelcomeEmailTemplateType();

        $this->assertSame($type, $type->setDefaultFromName('Shop')->setDefaultFromEmail('shop@example.com')->setLocale('hu')->setPriority(3)->setComment('c'));
        $this->assertSame(['Shop', 'shop@example.com', 'hu', 3, 'c'], [$type->getDefaultFromName(), $type->getDefaultFromEmail(), $type->getLocale(), $type->getPriority(), $type->getComment()]);
    }

    public function testTitleIsTranslatedByKey(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->once())->method('trans')->with('mail_template.welcome.title')->willReturn('Üdvözlő levél');
        $type = (new WelcomeEmailTemplateType())->setTranslator($translator);

        $this->assertSame('Üdvözlő levél', $type->getTitle());
    }

    public function testSendDelegatesToTheMessageSender(): void
    {
        $sender = $this->createMock(MessageSender::class);
        $type = (new WelcomeEmailTemplateType())->setMessageSender($sender);
        $sender->expects($this->once())->method('sendTemplateMail')->with($type, [], ['to' => 'x'], 'hu')->willReturn(1);

        $this->assertSame(1, $type->send(['userName' => 'Ann'], ['to' => 'x'], 'hu'));
        $this->assertSame('Ann', $type->getUserName());
    }

    public function testAlterEmailDoesNothingByDefault(): void
    {
        $email = (new Email())->subject('s');

        (new WelcomeEmailTemplateType())->alterEmail($email);

        $this->assertSame('s', $email->getSubject());
    }
}
