<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ActionConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->ignoreExtraKeys()
            ->children()
                ->enumNode('api_version')
                    ->cannotBeEmpty()
                    ->values([
                        'v19.0',
                        'v20.0',
                        'v21.0',
                    ])
                    ->defaultValue(Config::GRAPH_VERSION)
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
