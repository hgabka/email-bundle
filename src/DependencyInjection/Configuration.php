<?php

namespace Hgabka\EmailBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Configuration implements ConfigurationInterface
{
    /** @var ContainerBuilder */
    private $container;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('hgabka_email');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('email_templates_enabled')->defaultTrue()->end()
                ->booleanNode('messages_enabled')->defaultTrue()->end()
                ->booleanNode('subscribers_enabled')->defaultTrue()->end()
                ->scalarNode('editor_role')->defaultValue('ROLE_EMAIL_ADMIN')->end()
                ->arrayNode('default_sender')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('email')->isRequired()->cannotBeEmpty()->defaultValue('info@example.com')->end()
                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->defaultValue('Acme Company')->end()
                    ->end()
                ->end()

                ->arrayNode('default_recipient')
                    ->addDefaultsIfNotSet()
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('email')->isRequired()->cannotBeEmpty()->defaultValue('info@acme.com')->end()
                        ->scalarNode('name')->isRequired()->cannotBeEmpty()->defaultValue('Acme Subcompany')->end()
                    ->end()
                ->end()
                ->scalarNode('default_name')->defaultValue('email_default_name')->end()
                ->booleanNode('force_queueing')->defaultFalse()->end()
                ->arrayNode('template_var_chars')
                      ->addDefaultsIfNotSet()
                      ->beforeNormalization()
                          ->ifString()
                          ->then(function ($v) { return ['prefix' => $v, 'postfix' => $v]; })
                      ->end()
                      ->children()
                          ->scalarNode('prefix')->defaultValue('%%')->isRequired()->cannotBeEmpty()->end()
                          ->scalarNode('postfix')->defaultValue('%%')->isRequired()->cannotBeEmpty()->end()
                      ->end()
                ->end()
                ->scalarNode('layout_file')->defaultValue($this->container->getParameter('kernel.project_dir').'/var/layout/%locale%/email_layout.html')->end()
                ->arrayNode('message_extra_parameters')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
                ->variableNode('pre_defined_message_recipients')->end()
                ->booleanNode('message_with_cc')->defaultFalse()->end()
                ->booleanNode('message_with_bcc')->defaultFalse()->end()
                ->scalarNode('message_return_path')->defaultNull()->end()
                ->scalarNode('email_return_path')->defaultNull()->end()
                ->booleanNode('editable_lists')->defaultFalse()->end()
                ->booleanNode('editable_layouts')->defaultFalse()->end()
                ->booleanNode('auto_append_unsubscribe_link')->defaultFalse()->end()
                ->scalarNode('return_path')->defaultValue('sfhungary@gmail.com')->end()
                ->arrayNode('add_headers')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
                ->integerNode('send_limit')->defaultValue(50)->end()
                ->integerNode('max_retries')->defaultValue(20)->end()
                ->booleanNode('message_logging')->defaultTrue()->end()
                ->scalarNode('log_path')->defaultValue($this->container->getParameter('kernel.logs_dir').'/message')->end()
                ->booleanNode('use_email_logging')->defaultTrue()->end()
                ->enumNode('email_logging_strategy')->values(['real_send', 'mailer_send'])->defaultValue('mailer_send')->end()
                ->integerNode('delete_sent_messages_after')->defaultValue(2)->end()
                ->arrayNode('redirect')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enable')->defaultTrue()->end()
                        ->arrayNode('recipients')
                            ->prototype('scalar')->end()
                        ->end()
                        ->arrayNode('hosts')
                            ->prototype('scalar')->end()
                        ->end()
                        ->booleanNode('subject_append')->defaultTrue()->end()
                    ->end()
                ->end()

                ->arrayNode('add_recipients')
                  ->addDefaultsIfNotSet()
                  ->children()
                      ->arrayNode('cc')
                        ->prototype('scalar')->end()
                      ->end()
                      ->arrayNode('bcc')
                        ->prototype('scalar')->end()
                      ->end()
                  ->end()
                ->end()

                ->arrayNode('bounce_checking')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('after_process')->values(['delete', 'leave_as_is', 'mark_as_read'])->defaultValue('delete')->end()
                        ->arrayNode('account')
                        ->children()
                            ->scalarNode('host')->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('type')->end()
                            ->scalarNode('address')->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('pass')->end()
                        ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
