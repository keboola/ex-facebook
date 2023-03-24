<?php

declare(strict_types=1);

namespace MyComponent;

use FacebookAds\Api;
use FacebookAds\Cursor;
use FacebookAds\Object\AbstractCrudObject;
use FacebookAds\Object\AdAccount;
use FacebookAds\Object\Page;
use FacebookAds\Object\PagePost;
use FacebookAds\Object\Post;
use FacebookAds\Object\ProductFeed;
use FacebookAds\Object\User;
use FacebookAdsTest\Object\EmptyObject;
use Keboola\Component\BaseComponent;

class Component extends BaseComponent
{
    protected function run(): void
    {
        $pageId = 1234567890;
        $appId = 178012768920721;
        $appSecret = 'abcdef';
        $accessToken = 'abcdef';

        $requestParams = [
            'limit' => 1,
            'fields' => 'attachments{caption, type, description},message,created_time,shares',
            'ids' => $pageId,
        ];

        $api = Api::init(
            $appId,
            $appSecret,
            $accessToken
        );
        $api->setDefaultGraphVersion('15.0');

        $request = $api->prepareRequest(
            '/feed',
            'GET',
            $requestParams
        );

        $response = $request->execute();

        var_dump($response->getContent()[$pageId]);

        $request = $response->getRequest();
        $request->getQueryParams()->enhance([
            'after' => $response->getContent()[$pageId]['paging']['cursors']['after']
        ]);

        $request->execute();
        var_dump($response->getContent()[$pageId]);

    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }
}
