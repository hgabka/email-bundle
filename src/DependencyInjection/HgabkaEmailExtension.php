<?php

namespace Hgabka\EmailBundle\DependencyInjection;

use Hgabka\EmailBundle\Helper\RecipientManager;
use Hgabka\EmailBundle\Helper\TemplateTypeManager;
use Hgabka\EmailBundle\Model\EmailTemplateRecipientTypeInterface;
use Hgabka\EmailBundle\Model\EmailTemplateTypeInterface;
use Hgabka\EmailBundle\Model\LayoutVarInterface;
use Hgabka\EmailBundle\Model\MessageRecipientTypeInterface;
use Hgabka\EmailBundle\Model\MessageVarInterface;
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
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration($container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $builderDefinition = $container->getDefinition('hg_email.mail_builder');
        $builderDefinition->addMethodCall('setConfig', [$config]);

        $senderDefinition = $container->getDefinition('hg_email.message_sender');
        $senderDefinition->addMethodCall('setConfig', [$config]);

        $loggerDefinition = $container->getDefinition('hg_email.message_logger');
        $loggerDefinition->replaceArgument(0, $config['log_path']);

        $queueDefinition = $container->getDefinition('hg_email.queue_manager');
        $queueDefinition->replaceArgument(6, $config['bounce_checking']);
        $queueDefinition->replaceArgument(7, $config['max_retries']);
        $queueDefinition->replaceArgument(8, $config['send_limit']);
        $queueDefinition->replaceArgument(9, $config['message_logging']);
        $queueDefinition->replaceArgument(10, $config['delete_sent_messages_after']);
        $queueDefinition->replaceArgument(11, $config['message_return_path']);
        $queueDefinition->replaceArgument(12, $config['email_return_path']);

        $substituterDefinition = $container->getDefinition('hg_email.param_substituter');
        $substituterDefinition->replaceArgument(5, $config['template_var_chars']);

        $emailLoggerDefinition = $container->getDefinition('hg_email.email_logger');
        $emailLoggerDefinition->replaceArgument(2, $config['use_email_logging']);

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
        $voterDefinition->replaceArgument(2, $config['editor_role']);

        $recipientManagerDefinition = $container->getDefinition(RecipientManager::class);
        $recipientManagerDefinition->replaceArgument(5, $config['excluded_recipient_classes']);

        $subscriptionManagerDefinition = $container->getDefinition('hg_email.subscription_manager');
        $subscriptionManagerDefinition->replaceArgument(2, $config['editable_lists']);

        $subscrConfig = $config['subscriptions'];
        $subscriptionManagerDefinition->replaceArgument(3, $subscrConfig['use_names']);

        $container->setParameter('hg_email.subscriptions.use_names', $subscrConfig['use_names']);

        $layoutManagerDefinition = $container->getDefinition('hg_email.layout_manager');
        $layoutManagerDefinition->replaceArgument(3, $config['layout_file']);

        $container->setParameter('hg_email.editor_role', $config['editor_role']);
        $container->setParameter('hg_email.redirect_config', $config['redirect']);
        $container->setParameter('hg_email.headers_config', $config['add_headers']);
        $container->setParameter('hg_email.template_var_reader_type', $config['template_var_reader_type']);
        $container->setParameter('hg_email.subscription_enabled', $config['subscription_enabled']);

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
        $container
            ->registerForAutoconfiguration(MessageVarInterface::class)
            ->addTag('hg_email.message_var')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $this->processConfiguration(new Configuration($container), $configs);
        $this->configureTwigBundle($container);
    }

    public function process(ContainerBuilder $container): void
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

        $taggedServices = $container->findTaggedServiceIds('hg_email.message_var');

        $definition = $container->findDefinition('hg_email.mail_builder');
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $type = new Reference($id);
                $definition->addMethodCall('addMessageVar', [
                    $type, $attributes['priority'] ?? null,
                ]);
            }
        }
    }

    protected function configureTwigBundle(ContainerBuilder $container): void
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
