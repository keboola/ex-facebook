<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public const GRAPH_VERSION = '15.0';

    public function getFoo(): string
    {
        return $this->getStringValue(['parameters', 'foo']);
    }
}
