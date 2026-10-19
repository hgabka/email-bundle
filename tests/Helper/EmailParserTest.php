<?php

namespace Hgabka\EmailBundle\Tests\Helper;

use Hgabka\EmailBundle\Helper\EmailParser;
use PHPUnit\Framework\TestCase;

class EmailParserTest extends TestCase
{
    private const RAW = "From: Sender <sender@example.com>\r\nTo: a@example.com,b@example.com\r\nCc: c@example.com\r\nSubject: Hello\r\n there\r\nX-Custom:   padded\r\n\r\nBody line 1\r\nBody line 2\r\n";

    public function testHeaders(): void
    {
        $parser = new EmailParser(self::RAW);

        $this->assertSame('Sender <sender@example.com>', $parser->getHeader('From'));
        $this->assertSame(['a@example.com', 'b@example.com'], $parser->getTo());
        $this->assertSame('Sender <sender@example.com>', $parser->getHeader('from'));
        $this->assertSame('', $parser->getHeader('X-Missing'));
        $this->assertSame(['c@example.com'], $parser->getCc());
    }

    public function testMissingRecipientsAreReported(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Couldn't find the recipients");
        (new EmailParser("Subject: x\r\n\r\nbody"))->getTo();
    }

    public function testMissingCcIsAnEmptyList(): void
    {
        $this->assertSame([], (new EmailParser("To: a@example.com\r\n\r\nx"))->getCc());
    }

    public function testNewLineDetection(): void
    {
        $this->assertTrue(EmailParser::isNewLine(''));
        $this->assertTrue(EmailParser::isNewLine("\r\n"));
        $this->assertFalse(EmailParser::isNewLine('x'));
    }

    public function testPlainTextBody(): void
    {
        $raw = "To: a@example.com\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\nHello\r\nWorld";

        $body = (new EmailParser($raw))->getBody();

        $this->assertStringContainsString('Hello', $body);
        $this->assertStringContainsString('World', $body);
    }
}
