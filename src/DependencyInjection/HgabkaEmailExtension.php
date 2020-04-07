<?php

namespace Hgabka\EmailBundle\DependencyInjection;

use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Helper\TemplateTypeManager;
use Hgabka\EmailBundle\Model\EmailTemplateRecipientTypeInterface;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\EmailBundle\Model\LayoutVarInterface;
use Hgabka\EmailBundle\Model\MessageRecipientTypeInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class HgabkaEmailExtension extends Extension implements PrependExtensionInterface, CompilerPassInterface
{
    /** @var string */
    protected $formTypeTemplate = '@HgabkaEmail/Form/fields.html.twig';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $builderDefinition = $container->getDefinition('hg_email.mail_builder');
        $builderDefinition->addMethodCall('setConfig', [$config]);

        $senderDefinition = $container->getDefinition('hg_email.message_sender');
        $senderDefinition->addMethodCall('setConfig', [$config]);

        $loggerDefinition = $container->getDefinition('hg_email.message_logger');
        $loggerDefinition->replaceArgument(0, $config['log_path']);

        $queueDefinition = $container->getDefinition('hg_email.queue_manager');
        $queueDefinition->replaceArgument(5, $config['bounce_checking']);
        $queueDefinition->replaceArgument(6, $config['max_retries']);
        $queueDefinition->replaceArgument(7, $config['send_limit']);
        $queueDefinition->replaceArgument(8, $config['message_logging']);
        $queueDefinition->replaceArgument(9, $config['delete_sent_messages_after']);
        $queueDefinition->replaceArgument(10, $config['message_return_path']);
        $queueDefinition->replaceArgument(11, $config['email_return_path']);

        $substituterDefinition = $container->getDefinition('hg_email.param_substituter');
        $substituterDefinition->replaceArgument(4, $config['template_var_chars']);

        $mailerSubscriberDefinition = $container->getDefinition('hg_email.mailer_subscriber');
        $mailerSubscriberDefinition->replaceArgument(1, $config['email_logging_strategy']);

        $emailLoggerDefinition = $container->getDefinition('hg_email.email_logger');
        $emailLoggerDefinition->replaceArgument(0, $config['use_email_logging']);

        $redirectPluginDefinition = $container->getDefinition('hg_email.redirect_plugin');
        $redirectPluginDefinition->replaceArgument(0, $config['redirect']['recipients'] ?? []);
        $redirectPluginDefinition->addMethodCall('setRedirectConfig', [$config['redirect']]);
        $addHeadersPluginDefinition = $container->getDefinition('hg_email.add_headers_plugin');
        $addHeadersPluginDefinition->addMethodCall('setConfig', [$config['add_headers']]);

        $addHeadersPluginDefinition = $container->getDefinition('hg_email.add_recipients_plugin');
        $addHeadersPluginDefinition->addMethodCall('setConfig', [$config['add_recipients']]);

        $addReturnPathPluginDefinition = $container->getDefinition('hg_email.add_return_path_plugin');
        $addReturnPathPluginDefinition->addMethodCall('setConfig', [$config['return_path']]);

        $mailReaderDefinition = $container->getDefinition('hg_email.mailbox_reader');
        $bcConfig = $config['bounce_checking'];
        $mailReaderDefinition->replaceArgument(1, $bcConfig['host'] ?? null);
        $mailReaderDefinition->replaceArgument(2, $bcConfig['port'] ?? null);
        $mailReaderDefinition->replaceArgument(3, $bcConfig['user'] ?? null);
        $mailReaderDefinition->replaceArgument(4, $bcConfig['pass'] ?? null);
        $mailReaderDefinition->replaceArgument(6, $bcConfig['type'] ?? null);

        $bounceCheckerDefinition = $container->getDefinition('hg_email.bounce_checker');
        $bounceCheckerDefinition->addMethodCall('setConfig', [$bcConfig]);

        $voterDefinition = $container->getDefinition('hg_email.email_voter');
        $voterDefinition->replaceArgument(1, $config['editor_role']);

        $subscriptionManagerDefinition = $container->getDefinition('hg_email.subscription_manager');
        $subscriptionManagerDefinition->replaceArgument(2, $config['editable_lists']);

        $subscrConfig = $config['subscriptions'];
        $subscriptionManagerDefinition->replaceArgument(3, $subscrConfig['use_names']);

        $container->setParameter('hg_email.subscriptions.use_names', $subscrConfig['use_names']);

        $layoutManagerDefinition = $container->getDefinition('hg_email.layout_manager');
        $layoutManagerDefinition->replaceArgument(2, $config['layout_file']);

        $container->setParameter('hg_email.editor_role', $config['editor_role']);

        $container
            ->registerForAutoconfiguration(EmailTemplateTypeInterface::class)
            ->addTag('hg_email.email_template_type')
        ;
        $container
            ->registerForAutoconfiguration(EmailTemplateRecipientTypeInterface::class)
            ->addTag('hg_email.email_template_recipient_type')
        ;
        $container
            ->registerForAutoconfiguration(MessageRecipientTypeInterface::class)
            ->addTag('hg_email.message_recipient_type')
        ;

        $container
            ->registerForAutoconfiguration(LayoutVarInterface::class)
            ->addTag('hg_email.layout_var')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $this->processConfiguration(new Configuration($container), $configs);
        $this->configureTwigBundle($container);
    }

    public function process(ContainerBuilder $container)
    {
        // always first check if the primary service is defined
        if (!$container->has(TemplateTypeManager::class)) {
            return;
        }

        $definition = $container->findDefinition(TemplateTypeManager::class);

        // find all service IDs with the app.mail_transport tag
        $taggedServices = $container->findTaggedServiceIds('hg_email.email_template_type');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $type = new Reference($id);
                $definition->addMethodCall('addTemplateType', [
                    $type, $attributes['priority'] ?? 0,
                ]);
            }
        }

        $definition = $container->findDefinition(RecipientManager::class);

        // find all service IDs with the hg_email.email_template_recipient_type tag
        $taggedServices = $container->findTaggedServiceIds('hg_email.email_template_recipient_type');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $type = new Reference($id);
                $definition->addMethodCall('addTemplateRecipientType', [
                    $type, $attributes['priority'] ?? null,
                ]);
            }
        }
        // find all service IDs with the hg_email.message_recipient_type tag
        $taggedServices = $container->findTaggedServiceIds('hg_email.message_recipient_type');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $type = new Reference($id);
                $definition->addMethodCall('addMessageRecipientType', [
                    $type, $attributes['priority'] ?? null,
                ]);
            }
        }

        $taggedServices = $container->findTaggedServiceIds('hg_email.layout_var');

        $definition = $container->findDefinition('hg_email.layout_manager');
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $type = new Reference($id);
                $definition->addMethodCall('addLayoutVar', [
                    $type, $attributes['priority'] ?? null,
                ]);
            }
        }
    }

    protected function configureTwigBundle(ContainerBuilder $container)
    {
        foreach (array_keys($container->getExtensions()) as $name) {
            switch ($name) {
                case 'twig':
                    $container->prependExtensionConfig(
                        $name,
                        ['form_themes' => [$this->formTypeTemplate]]
                    );

                    break;
            }
        }
    }
}
