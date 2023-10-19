<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getRootDefinition(TreeBuilder $treeBuilder): ArrayNodeDefinition
    {
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode->ignoreExtraKeys(false);

        // @formatter:off
        $rootNode
            ->children()
            ->arrayNode('authorization')
                ->ignoreExtraKeys(false)
                ->isRequired()
                ->children()
                    ->arrayNode('oauth_api')
                        ->ignoreExtraKeys(false)
                        ->isRequired()
                        ->children()
                            ->arrayNode('credentials')
                                ->ignoreExtraKeys(false)
                                ->isRequired()
                                ->children()
                                    ->scalarNode('appKey')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('#appSecret')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('#data')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->append($this->getParametersDefinition());
        // @formatter:on

        return $rootNode;
    }
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->arrayNode('accounts')
                    ->arrayPrototype()
                        ->ignoreExtraKeys(false)
                        ->children()->end()
                    ->end()
                ->end()
                ->enumNode('api_version')
                    ->cannotBeEmpty()
                    ->values([
                        'v10.0',
                        'v11.0',
                        'v12.0',
                        'v13.0',
                        'v14.0',
                        'v15.0',
                        'v16.0',
                        'v17.0',
                    ])
                    ->defaultValue(Config::GRAPH_VERSION)
                ->end()
                ->arrayNode('queries')
                    ->arrayPrototype()
                        ->ignoreExtraKeys()
                        ->children()
                            ->scalarNode('name')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('query')
                                ->isRequired()
                                ->children()
                                    ->scalarNode('path')->end()
                                    ->scalarNode('fields')->end()
                                    ->scalarNode('parameters')->end()
                                    ->scalarNode('limit')->end()
                                    ->scalarNode('ids')->end()
                                    ->scalarNode('since')->end()
                                    ->scalarNode('until')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
