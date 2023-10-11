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
            ->children()
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
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
