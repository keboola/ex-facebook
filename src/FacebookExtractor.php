<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use FacebookAds\Api;
use FacebookAds\Http\Exception\AuthorizationException;
use FacebookAds\Http\Request;
use FacebookAds\Http\Response;
use FacebookAds\Session;
use Generator;
use Keboola\Component\UserException;
use Keboola\FacebookExtractor\Configuration\Node\Account;
use Keboola\FacebookExtractor\Configuration\Node\QueryConfig;
use Keboola\FacebookExtractor\Configuration\Node\RowConfig;
use Psr\Log\LoggerInterface;

class FacebookExtractor
{
    public function __construct(readonly Api $api, readonly LoggerInterface $logger)
    {
    }

    /**
     * @param Account[] $accounts
     */
    public function exportRow(array $accounts, RowConfig $row): Generator
    {
        /** @var Session $apiSession */
        $apiSession = $this->api->getSession();
        if ($this->requestRequirePageToken($row->getQuery())) {
            $pageTokens = $this->getPagesToken($accounts, $row->getQuery());
            $isPageToken = true;
        } else {
            $pageTokens = array_combine(
                array_map(fn(Account $account) => $account->getId(), $accounts),
                array_fill(0, count($accounts), $apiSession->getAccessToken()),
            );
            $isPageToken = false;
        }

        foreach ($pageTokens as $pageId => $token) {
            $pageId = (string) $pageId;

            $this->logger->info(sprintf(
                'Using %s access token to retrieve data for %s',
                $isPageToken ? 'page' :  'user',
                $pageId,
            ));
            $api = Api::init(
                $apiSession->getAppId(),
                $apiSession->getAppSecret(),
                $token,
                false,
            );

            $pageLoader = new PageLoader($api, $this->logger, $row->getType(), $row->getQuery()->getLimit());
            $outputParser = new OutputParser(
                $pageLoader,
                $pageId,
                $row,
            );
            $fbGraphNode = sprintf(
                '%s%s',
                !$isPageToken ? : 'page_',
                $isPageToken && !$row->getQuery()->hasPath() ? 'insights' : $row->getQuery()->getPath(),
            );
            $page = $pageLoader->loadPage(
                $row->getQuery(),
                $pageId,
            )->getContent();

            if (empty($page)) {
                continue;
            }
            yield $outputParser->parseRow(current($page), $fbGraphNode, $pageId);
        }
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
            $params,
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

    /**
     * @param Account[] $accounts
     */
    private function getPagesToken(array $accounts, QueryConfig $queryConfig): array
    {
        $ids = explode(',', (string) $queryConfig->getIds());
        if (empty($queryConfig->getIds())) {
            $ids = array_map(fn(Account $account) => $account->getId(), $accounts);
        }

        $pageTokens = [];
        foreach ($ids as $id) {
            $request = $this->api->prepareRequest(
                '/' . $id,
                'GET',
                ['fields' => 'access_token'],
            );
            $response = $request->execute();
            /** @var array{'id': string|int, 'access_token': string} $content */
            $content = $response->getContent();

            $pageTokens[(string) $content['id']] = $content['access_token'];
        }

        return $pageTokens;
    }

    private function requestRequirePageToken(QueryConfig $queryConfig): bool
    {
        $checkPath = in_array($queryConfig->getPath(), ['insights', 'feed', 'posts', 'ratings', 'likes']);
        $fields = (string) $queryConfig->getFields();

        return $checkPath ||
            str_contains($fields, 'insights') ||
            str_contains($fields, 'likes') ||
            str_contains($fields, 'from') ||
            str_contains($fields, 'username');
    }
}
