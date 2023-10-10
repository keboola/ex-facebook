<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor\Configuration;

use Keboola\Component\Config\BaseConfig;
use Keboola\FacebookExtractor\Configuration\Node\Account;
use Keboola\FacebookExtractor\Configuration\Node\RowConfig;

class Config extends BaseConfig
{
    public const GRAPH_VERSION = '17.0';

    public function getApiVersion(): string
    {
        $value = $this->getStringValue(['parameters', 'api-version'], '');
        return !empty($value) ? $value : self::GRAPH_VERSION;
    }

    /**
     * @return Account[]
     */
    public function getAccounts(): array
    {
        return array_map(
            fn(array $account) => Account::fromArray($account),
            $this->getArrayValue(['parameters', 'accounts']),
        );
    }

    public function getRows(): array
    {
        return array_map(
            fn(array $query) => RowConfig::fromArray($query),
            $this->getArrayValue(['parameters', 'queries']),
        );
    }

    public function getOAuthApiData(): string
    {
        return $this->getStringValue(['authorization', 'oauth_api', 'credentials', '#data'], '');
    }
}
