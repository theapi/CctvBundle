<?php

namespace Theapi\RobocopBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('theapi_robocop');

        $rootNode
            ->children()
                ->scalarNode('save_dir')->defaultValue('/tmp/cctv')->end()
                ->arrayNode('image_settings')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('diff_threshold')->defaultValue(600)->end()
                        ->scalarNode('days_old')->defaultValue(31)->end()
                    ->end()
                ->end()
                ->arrayNode('mailer_sender')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('from')->defaultNull()->end()
                        ->scalarNode('to')->defaultNull()->end()
                    ->end()
                ->end()
             ->end()
        ;

        return $treeBuilder;
    }
}
