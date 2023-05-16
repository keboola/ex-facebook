<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use FacebookAds\Api;
use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Request;
use FacebookAds\Http\Response;
use Keboola\Component\UserException;

class FacebookExtractor
{
    public function __construct(readonly Api $api)
    {
    }

    public function getAccounts(string $urlPath, ?string $fields = null): array
    {
        $params = [];
        if ($fields !== null) {
            $params['fields'] = $fields;
        }
        /** @var Request $request */
        $request = $this->api->prepareRequest(
            $urlPath,
            'GET',
            $params
        );

        try {
            /** @var Response $response */
            $response = $request->execute();
        } catch (AuthorizationException $e) {
            throw new UserException('Authorization failed: ' . $e->getMessage(), $e->getCode(), $e);
        }

        if (!$response->getContent()) {
            return [];
        }

        return $response->getContent()['data'];
    }
}
