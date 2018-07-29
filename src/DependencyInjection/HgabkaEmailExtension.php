<?php

namespace Hgabka\EmailBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class HgabkaKunstmaanEmailExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration($container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $builderDefinition = $container->getDefinition('hgabka_email.mail_builder');
        $builderDefinition->addMethodCall('setConfig', [$config]);

        $senderDefinition = $container->getDefinition('hgabka_email.message_sender');
        $senderDefinition->addMethodCall('setConfig', [$config]);

        $loggerDefinition = $container->getDefinition('hgabka_email.message_logger');
        $loggerDefinition->replaceArgument(1, $config['log_path']);

        $queueDefinition = $container->getDefinition('hgabka_email.queue_manager');
        $queueDefinition->replaceArgument(3, $config['bounce_checking']);
        $queueDefinition->replaceArgument(4, $config['max_retries']);
        $queueDefinition->replaceArgument(5, $config['send_limit']);
        $queueDefinition->replaceArgument(6, $config['message_logging']);
        $queueDefinition->replaceArgument(7, $config['delete_sent_messages_after']);

        $substituterDefinition = $container->getDefinition('hgabka_email.param_substituter');
        $substituterDefinition->replaceArgument(3, $config['template_var_chars']);

        $mailerSubscriberDefinition = $container->getDefinition('hgabka_email.mailer_subscriber');
        $mailerSubscriberDefinition->replaceArgument(1, $config['email_logging_strategy']);

        $redirectPluginDefinition = $container->getDefinition('hgabka_email.redirect_plugin');
        $redirectPluginDefinition->replaceArgument(0, $config['redirect']['recipients'] ?? []);
        $redirectPluginDefinition->addMethodCall('setRedirectConfig', [$config['redirect']]);
        $addHeadersPluginDefinition = $container->getDefinition('hgabka_email.add_headers_plugin');
        $addHeadersPluginDefinition->addMethodCall('setConfig', [$config['add_headers']]);

        $addHeadersPluginDefinition = $container->getDefinition('hgabka_email.add_recipients_plugin');
        $addHeadersPluginDefinition->addMethodCall('setConfig', [$config['add_recipients']]);

        $addReturnPathPluginDefinition = $container->getDefinition('hgabka_email.add_return_path_plugin');
        $addReturnPathPluginDefinition->addMethodCall('setConfig', [$config['return_path']]);

        $mailReaderDefinition = $container->getDefinition('hgabka_email.mailbox_reader');
        $bcConfig = $config['bounce_checking'];
        $mailReaderDefinition->replaceArgument(1, $bcConfig['host'] ?? null);
        $mailReaderDefinition->replaceArgument(2, $bcConfig['port'] ?? null);
        $mailReaderDefinition->replaceArgument(3, $bcConfig['user'] ?? null);
        $mailReaderDefinition->replaceArgument(4, $bcConfig['pass'] ?? null);
        $mailReaderDefinition->replaceArgument(6, $bcConfig['type'] ?? null);

        $bounceCheckerDefinition = $container->getDefinition('hgabka_email.bounce_checker');
        $bounceCheckerDefinition->addMethodCall('setConfig', [$bcConfig]);

        $voterDefinition = $container->getDefinition('hgabka_email.email_voter');
        $voterDefinition->replaceArgument(1, $config['editor_role']);

        $subscriptionManagerDefinition = $container->getDefinition('hgabka_email.subscription_manager');
        $subscriptionManagerDefinition->replaceArgument(2, $config['editable_lists']);

        $container->setParameter('hgabka_email.editor_role', $config['editor_role']);
    }
}
