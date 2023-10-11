<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use FacebookAds\Api;
use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Exception\RequestException;
use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\FacebookExtractor\Configuration\ActionConfigDefinition;
use Keboola\FacebookExtractor\Configuration\Config;
use Keboola\FacebookExtractor\Configuration\ConfigDefinition;
use Keboola\FacebookExtractor\Configuration\Node\RowConfig;

class Component extends BaseComponent
{
    protected function run(): void
    {
        $extractor = new FacebookExtractor($this->getClient(), $this->getLogger());

        $outputWriter = new OutputWriter($this->getManifestManager(), $this->getDataDir() . '/out/tables');
        $accountData = [];
        foreach ($this->getConfig()->getAccounts() as $account) {
            $accountData[] = $account->toArray();
        }
        $outputWriter->write(['accounts' => $accountData]);
        try {
            /** @var RowConfig $row */
            foreach ($this->getConfig()->getRows() as $row) {
                foreach ($extractor->exportRow($this->getConfig()->getAccounts(), $row) as $parsedData) {
                    $outputWriter->write((array) $parsedData);
                }
            }
        } catch (AuthorizationException|RequestException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function runAccountsAction(): array
    {
        $extractor = new FacebookExtractor($this->getClient(), $this->getLogger());
        return $extractor->getAccounts('/me/accounts');
    }

    public function runAdAccountsAction(): array
    {
        $extractor = new FacebookExtractor($this->getClient(), $this->getLogger());
        return $extractor->getAccounts('/me/adaccounts', 'account_id,id,business_name,name,currency');
    }

    public function runIgAccountsAction(): array
    {
        $extractor = new FacebookExtractor($this->getClient(), $this->getLogger());
        return $extractor->getAccounts('/me/accounts', 'instagram_business_account,name,category');
    }

    public function getConfig(): Config
    {
        /** @var Config $config */
        $config = parent::getConfig();
        return $config;
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        $rawConfig = $this->getRawConfig();
        $action = $rawConfig['action'] ?? 'run';

        if ($action !== 'run') {
            return ActionConfigDefinition::class;
        }
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
            false,
        );
        $api->setDefaultGraphVersion(substr($this->getConfig()->getApiVersion(), 1));

        return $api;
    }
}
