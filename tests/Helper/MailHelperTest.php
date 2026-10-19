<?php

namespace Hgabka\EmailBundle\Tests\Helper;

use Hgabka\EmailBundle\Helper\MailHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailHelperTest extends TestCase
{
    private MailHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new MailHelper();
    }

    public function testDisplayAddresses(): void
    {
        $this->assertSame('', $this->helper->displayAddresses(null));
        $this->assertSame('', $this->helper->displayAddresses([]));
        $this->assertSame('a@example.com', $this->helper->displayAddresses('a@example.com'));
        $this->assertSame('a@example.com', $this->helper->displayAddresses(new Address('a@example.com')));
        $this->assertSame('a@example.com,b@example.com', $this->helper->displayAddresses(['a@example.com', new Address('b@example.com')]));
        $this->assertSame('a@example.com; b@example.com', $this->helper->displayAddresses(['a@example.com', 'b@example.com'], '; '));
        $this->assertSame('"Ann" <a@example.com>', $this->helper->displayAddresses('Ann <a@example.com>'));
    }

    public function testEmbedIdsAreUniqueContentIds(): void
    {
        $first = $this->helper->generateEmbedId();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}@hg_email$/', $first);
        $this->assertNotSame($first, $this->helper->generateEmbedId());
    }

    public function testTranslateEmailAddress(): void
    {
        $address = new Address('a@example.com', 'Ann');

        $this->assertSame($address, $this->helper->translateEmailAddress($address));
        $this->assertSame('a@example.com', $this->helper->translateEmailAddress('a@example.com')->getAddress());
        $this->assertEquals(new Address('a@example.com', 'Ann'), $this->helper->translateEmailAddress(['email' => 'a@example.com', 'name' => 'Ann']));
        $this->assertEquals(new Address('a@example.com'), $this->helper->translateEmailAddress(['email' => 'a@example.com', 'name' => '']));
        $this->assertEquals(new Address('a@example.com', 'Ann'), $this->helper->translateEmailAddress(['a@example.com' => 'Ann']));
        $this->assertEquals(new Address('a@example.com'), $this->helper->translateEmailAddress(['a@example.com' => '']));
        $this->assertSame($address, $this->helper->translateEmailAddress([$address]));
    }

    public function testHeadersAreAddedFromAnArray(): void
    {
        $email = new Email();

        $this->helper->addHeadersFromArray($email, [
            'X-Mailer' => 'hgabka',
            'X-Campaign' => ['type' => 'text', 'value' => 'spring'],
            'Reply-To' => ['type' => 'mailBoxList', 'value' => 'reply@example.com'],
        ]);

        $this->assertSame('hgabka', $email->getHeaders()->get('X-Mailer')->getBody());
        $this->assertSame('spring', $email->getHeaders()->get('X-Campaign')->getBody());
        $this->assertSame('reply@example.com', $email->getHeaders()->get('Reply-To')->getAddresses()[0]->getAddress());
    }

    public function testIncompleteHeaderDefinitionsAreSkipped(): void
    {
        $email = new Email();

        $this->helper->addHeadersFromArray($email, ['X-A' => ['type' => 'text'], 'X-B' => ['value' => 'v'], 'X-C' => ['type' => 'text', 'value' => '']]);
        $this->helper->addHeadersFromArray($email, null);

        $this->assertFalse($email->getHeaders()->has('X-A'));
        $this->assertFalse($email->getHeaders()->has('X-B'));
        $this->assertFalse($email->getHeaders()->has('X-C'));
    }

    public function testUniqueHeadersAreNotOverwritten(): void
    {
        $email = (new Email())->subject('original');

        $this->helper->addHeadersFromArray($email, ['Subject' => 'other']);

        $this->assertSame('original', $email->getSubject());
    }
}
