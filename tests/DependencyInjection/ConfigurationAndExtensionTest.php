<?php

namespace Hgabka\EmailBundle\Tests\DependencyInjection;

use Hgabka\EmailBundle\DependencyInjection\Configuration;
use Hgabka\EmailBundle\DependencyInjection\HgabkaEmailExtension;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\EmailBundle\Model\LayoutVarInterface;
use Hgabka\EmailBundle\Model\MessageRecipientTypeInterface;
use Hgabka\EmailBundle\Model\MessageVarInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ConfigurationAndExtensionTest extends TestCase
{
    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/project');
        $container->setParameter('kernel.logs_dir', '/project/var/log');
        $container->setParameter('kernel.cache_dir', '/project/var/cache');

        return $container;
    }

    private function process(array $config = []): array
    {
        return (new Processor())->processConfiguration(new Configuration($this->container()), [$config]);
    }

    public function testDefaults(): void
    {
        $config = $this->process();

        $this->assertTrue($config['email_templates_enabled']);
        $this->assertTrue($config['messages_enabled']);
        $this->assertSame('ROLE_EMAIL_ADMIN', $config['editor_role']);
        $this->assertSame(['prefix' => '%%', 'postfix' => '%%'], $config['template_var_chars']);
        $this->assertSame('attribute', $config['template_var_reader_type']);
        $this->assertSame('/project/var/layout/%locale%/email_layout.html', $config['layout_file']);
        $this->assertSame(50, $config['send_limit']);
        $this->assertSame(20, $config['max_retries']);
        $this->assertSame(2, $config['delete_sent_messages_after']);
        $this->assertSame('/project/var/log/message', $config['log_path']);
        $this->assertFalse($config['force_queueing']);
        $this->assertSame('delete', $config['bounce_checking']['after_process']);
        $this->assertTrue($config['redirect']['enable']);
    }

    public function testTemplateVarCharsStringAppliesToBothSides(): void
    {
        $this->assertSame(['prefix' => '#', 'postfix' => '#'], $this->process(['template_var_chars' => '#'])['template_var_chars']);
    }

    public function testHeadersDefaultToTextType(): void
    {
        $config = $this->process(['add_headers' => ['Precedence' => ['value' => 'bulk'], 'Sender' => ['type' => 'mailBoxList', 'value' => 'a@example.com']]]);

        $this->assertEquals(['type' => 'text', 'value' => 'bulk'], $config['add_headers']['Precedence']);
        $this->assertSame('mailBoxList', $config['add_headers']['Sender']['type']);
    }

    public function testHeaderNamesKeepTheirDashes(): void
    {
        $config = $this->process(['add_headers' => ['X-Mailer' => ['value' => 'hgabka'], 'List-Unsubscribe' => ['value' => '<mailto:u@example.com>']]]);

        $this->assertSame(['X-Mailer', 'List-Unsubscribe'], array_keys($config['add_headers']));
    }

    public function testInvalidValuesAreRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->process(['template_var_reader_type' => 'yaml']);
    }

    public function testBounceActionIsValidated(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->process(['bounce_checking' => ['after_process' => 'explode']]);
    }

    public function testConfigIsInjectedIntoTheServices(): void
    {
        $container = $this->container();

        (new HgabkaEmailExtension())->load([['send_limit' => 10, 'editor_role' => 'ROLE_MAIL', 'editable_lists' => true, 'subscriptions' => ['use_names' => false], 'template_var_chars' => '#']], $container);

        $this->assertSame('ROLE_MAIL', $container->getParameter('hg_email.editor_role'));
        $this->assertSame(false, $container->getParameter('hg_email.subscriptions.use_names'));
        $this->assertSame('attribute', $container->getParameter('hg_email.template_var_reader_type'));
        $this->assertSame(10, $container->getDefinition('hg_email.queue_manager')->getArgument(8));
        $this->assertSame(['prefix' => '#', 'postfix' => '#'], $container->getDefinition('hg_email.param_substituter')->getArgument(5));
        $this->assertSame('ROLE_MAIL', $container->getDefinition('hg_email.email_voter')->getArgument(2));
        $this->assertTrue($container->getDefinition('hg_email.subscription_manager')->getArgument(2));
        $this->assertSame('/project/var/log/message', $container->getDefinition('hg_email.message_logger')->getArgument(0));
        $this->assertSame('setConfig', $container->getDefinition('hg_email.mail_builder')->getMethodCalls()[0][0]);
    }

    public function testExtensionPointsAreAutoconfigured(): void
    {
        $container = $this->container();

        (new HgabkaEmailExtension())->load([[]], $container);

        $tags = [
            EmailTemplateTypeInterface::class => 'hg_email.email_template_type',
            MessageRecipientTypeInterface::class => 'hg_email.message_recipient_type',
            LayoutVarInterface::class => 'hg_email.layout_var',
            MessageVarInterface::class => 'hg_email.message_var',
        ];
        foreach ($tags as $interface => $tag) {
            $this->assertArrayHasKey($tag, $container->getAutoconfiguredInstanceof()[$interface]->getTags(), $interface);
        }
    }
}
