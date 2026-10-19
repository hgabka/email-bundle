<?php

namespace Hgabka\EmailBundle\Tests\Helper;

use Hgabka\EmailBundle\Helper\MailHelper;
use Hgabka\EmailBundle\Helper\ParamSubstituter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class ParamSubstituterTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir() . '/hg_email_' . bin2hex(random_bytes(4));
        mkdir($this->projectDir . '/public/img', 0777, true);
        file_put_contents($this->projectDir . '/public/img/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAYAAAC56t6BAAAAEElEQVR4nGNgYGD4z4AGAAARAgEAf2cmSgAAAABJRU5ErkJggg=='));
    }

    protected function tearDown(): void
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->projectDir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->projectDir);
    }

    private function substituter(array|string|null $varChars = '%%', ?Request $request = null): ParamSubstituter
    {
        $stack = new RequestStack();
        if ($request) {
            $stack->push($request);
        }
        $router = $this->createStub(RouterInterface::class);
        $router->method('getContext')->willReturn(new RequestContext('', 'GET', 'example.test', 'https'));

        return new ParamSubstituter($stack, $router, new MailHelper(), sys_get_temp_dir(), $this->projectDir, $varChars);
    }

    public function testVarCharsDefaults(): void
    {
        $this->assertSame(['prefix' => '%%', 'postfix' => '%%'], $this->substituter(null)->getVarChars());
        $this->assertSame(['prefix' => '#', 'postfix' => '#'], $this->substituter('#')->getVarChars());
        $this->assertSame(['prefix' => '[', 'postfix' => ']'], $this->substituter(['prefix' => '[', 'postfix' => ']'])->getVarChars());
        $this->assertSame(['prefix' => '{', 'postfix' => ''], $this->substituter(['prefix' => '{'])->getVarChars());
    }

    public function testAddAndRemoveVarChars(): void
    {
        $substituter = $this->substituter(['prefix' => '[', 'postfix' => ']']);

        $this->assertSame('[name]', $substituter->addVarChars('name'));
        $this->assertSame('[name]', $substituter->addVarChars('[name]'), 'adding twice must not double the characters');
        $this->assertSame('name', $substituter->removeVarChars('[name]'));
        $this->assertSame('name', $substituter->removeVarChars('name'));
        $this->assertSame(['[a]' => 1, '[b]' => 2], $substituter->addVarChars(['a' => 1, 'b' => 2]));
        $this->assertSame(['a' => 1], $substituter->removeVarChars(['[a]' => 1]));
        $this->assertSame([], $substituter->addVarChars([]));
        $this->assertSame(5, $substituter->addVarChars(5));
    }

    public function testStringParamsAreSubstituted(): void
    {
        $text = $this->substituter()->substituteParams('Hello %%name%%, welcome to %%site%%! %%name%%', ['name' => 'Éva', 'site' => 'Acme']);

        $this->assertSame('Hello Éva, welcome to Acme! Éva', $text);
    }

    public function testParamKeysMayAlreadyContainTheVarChars(): void
    {
        $this->assertSame('Hi Ann', $this->substituter()->substituteParams('Hi %%name%%', ['%%name%%' => 'Ann']));
    }

    public function testCustomVarChars(): void
    {
        $this->assertSame('Hi Ann', $this->substituter(['prefix' => '[', 'postfix' => ']'])->substituteParams('Hi [name]', ['name' => 'Ann']));
    }

    public function testEmptyTextIsReturnedAsIs(): void
    {
        $this->assertNull($this->substituter()->substituteParams(null, ['a' => 'b']));
        $this->assertSame('', $this->substituter()->substituteParams('', ['a' => 'b']));
    }

    public function testEmptyParamsReplaceWithAnEmptyString(): void
    {
        $this->assertSame('Hi !', $this->substituter()->substituteParams('Hi %%name%%!', ['name' => null]));
    }

    public function testNormalizeParamsExpandsObjectsIntoDottedKeys(): void
    {
        $user = new class() {
            public string $Email = 'a@example.com';

            public function getName(): string
            {
                return 'Ann';
            }
        };

        $normalized = $this->substituter()->normalizeParams(['%%user%%' => $user, 'plain' => 'x', 'empty' => null]);

        $this->assertSame('a@example.com', $normalized['user.email']);
        $this->assertSame('Ann', $normalized['user.getName']);
        $this->assertSame($user, $normalized['user']);
        $this->assertSame('x', $normalized['plain']);
        $this->assertSame('', $normalized['empty']);
    }

    public function testObjectParamsDoNotBreakSubstitution(): void
    {
        $user = new class() {
            public string $Email = 'a@example.com';

            public function getName(): string
            {
                return 'Ann';
            }
        };

        $text = $this->substituter()->substituteParams('%%user.getName%% <%%user.email%%> %%user%%', ['user' => $user]);

        $this->assertSame('Ann <a@example.com> %%user%%', $text);
    }

    public function testRemoteImageUrlsAreNotTreatedAsLocalFiles(): void
    {
        $html = $this->substituter()->setAbsoluteImageUrls('<img src="//cdn.test/a.png"><img src="img/logo.png">');

        $this->assertStringContainsString('src="//cdn.test/a.png"', $html);
        $this->assertStringContainsString('src="https://example.testimg/logo.png"', $html);
    }

    public function testBlockParamsWrapTheirParagraphInADiv(): void
    {
        $text = $this->substituter()->substituteParams('<p>Before</p><p>%%box%%</p>', ['box' => ['type' => 'block', 'value' => '<ul><li>x</li></ul>']]);

        $this->assertSame('<p>Before</p><div><ul><li>x</li></ul></div>', $text);
    }

    public function testNonStringParamValuesAreIgnored(): void
    {
        $this->assertSame('%%a%%', $this->substituter()->substituteParams('%%a%%', ['a' => ['value' => ['nested']]]));
    }

    public function testRelativeLinksGetTheHostOfTheCurrentRequest(): void
    {
        $substituter = $this->substituter('%%', Request::create('https://shop.test/page'));

        $html = $substituter->transferRelativeLinks('<a href="/orders/1">Orders</a> <a href="https://other.test/x">Other</a>');

        $this->assertStringContainsString('href="https://shop.test/orders/1"', $html);
        $this->assertStringContainsString('href="https://other.test/x"', $html);
    }

    public function testRelativeLinksFallBackToTheRouterContextWithoutARequest(): void
    {
        $html = $this->substituter()->transferRelativeLinks('<a href="/orders/1">Orders</a>');

        $this->assertStringContainsString('href="https://example.test/orders/1"', $html);
    }

    public function testImagesWithExistingFilesGetAbsoluteUrls(): void
    {
        $html = $this->substituter()->setAbsoluteImageUrls('<img alt="" src="img/logo.png"><img src="img/missing.png">');

        $this->assertStringContainsString('src="https://example.test' . 'img/logo.png"', $html);
        $this->assertStringContainsString('src="img/missing.png"', $html);
    }

    public function testImagesAreEmbeddedAsCidParts(): void
    {
        $email = new Email();
        $html = $this->substituter()->embedImages('<img src="img/logo.png"> <img src="img/none.png">', $email);

        $this->assertMatchesRegularExpression('#<img src="cid:[0-9a-f]{32}@hg_email"> <img src="img/none.png">#', $html);
        $this->assertCount(1, $email->getAttachments());
    }

    public function testPrepareHtmlSubstitutesEmbedsAndFixesLinks(): void
    {
        $email = new Email();
        $substituter = $this->substituter('%%', Request::create('https://shop.test/'));

        $html = $substituter->prepareHtml($email, '<p>%%name%%</p><a href="/x">x</a><img src="img/logo.png">', ['name' => 'Ann']);

        $this->assertStringContainsString('<p>Ann</p>', $html);
        $this->assertStringContainsString('href="https://shop.test/x"', $html);
        $this->assertStringContainsString('src="cid:', $html);
    }

    public function testPrepareHtmlWithoutImageEmbedding(): void
    {
        $html = $this->substituter()->prepareHtml(new Email(), '<img src="img/logo.png">', [], false, false);

        $this->assertSame('<img src="img/logo.png">', $html);
    }
}
