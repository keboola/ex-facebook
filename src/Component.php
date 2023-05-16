<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use FacebookAds\Api;
use Keboola\Component\BaseComponent;
use Keboola\FacebookExtractor\Configuration\Config;
use Keboola\FacebookExtractor\Configuration\ConfigDefinition;

class Component extends BaseComponent
{
    protected function run(): void
    {
        //TODO: Implement run() method.
    }

    public function runAccountsAction(): array
    {
        $extractor = new FacebookExtractor($this->getClient());
        return $extractor->getAccounts('/me/accounts');
    }

    public function runAdAccountsAction(): array
    {
        $extractor = new FacebookExtractor($this->getClient());
        return $extractor->getAccounts('/me/adaccounts', 'account_id,id,business_name,name,currency');
    }

    public function runIgAccountsAction(): array
    {
        $extractor = new FacebookExtractor($this->getClient());
        return $extractor->getAccounts('/me/accounts', 'instagram_business_account,name,category');
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    protected function getSyncActions(): array
    {
        return [
            'accounts' => 'runAccountsAction',
            'adaccounts' => 'runAdAccountsAction',
            'igaccounts' => 'runIgAccountsAction',
        ];
    }

    protected function getClient(): Api
    {
        $oauthData = (array) json_decode((string) $this->getConfig()->getOAuthApiData(), true);
        assert(array_key_exists('token', $oauthData));

        $api = Api::init(
            $this->getConfig()->getOAuthApiAppKey(),
            $this->getConfig()->getOAuthApiAppSecret(),
            $oauthData['token'],
            false
        );
        $api->setDefaultGraphVersion(Config::GRAPH_VERSION);

        return $api;
    }
}
