<?php

declare(strict_types=1);

namespace Keboola\FacebookExtractor;

use FacebookAds\Api;
use FacebookAds\Http\RequestInterface;
use FacebookAds\Http\ResponseInterface;
use FacebookAds\Http\Util;
use Keboola\Component\UserException;
use Keboola\FacebookExtractor\Configuration\Node\QueryConfig;
use Psr\Log\LoggerInterface;

class PageLoader
{
    private const TYPE_ASYNC_REQUEST = 'async-insights-query';

    public function __construct(readonly Api $api, readonly LoggerInterface $logger, readonly string $type)
    {
    }

    public function loadPage(
        QueryConfig $query,
        ?string $pageId = null,
    ): ResponseInterface {
        if ($this->type === self::TYPE_ASYNC_REQUEST) {
            $request = $this->api->prepareRequest(
                sprintf('/%s/insights?%s', $pageId, $query->getParameters()),
                'POST',
            );
            return $this->loadPageAsync($request);
        } else {
            $params = [
                'ids' => $pageId,
                'fields' => $query->getFields(),
            ];
            $request = $this->api->prepareRequest(
                '/'. $query->getPath(),
                'GET',
                $params,
            );

            return $request->execute();
        }
    }

    public function loadPageFromUrl(string $url): ResponseInterface
    {
        /** @var array{'host': string, 'query'?: string, 'path': string} $components */
        $components = (array) parse_url($url);

        $request = $this->api->prepareRequest(
            str_replace(sprintf('/v%s', $this->api->getDefaultGraphVersion()), '', $components['path']),
            'GET',
        );
        $request->setDomain($components['host']);
        $query = isset($components['query'])
            ? Util::parseUrlQuery($components['query'])
            : [];
        $request->getQueryParams()->enhance($query);

        if ($this->type === self::TYPE_ASYNC_REQUEST) {
            return $this->loadPageAsync($request);
        } else {
            return $request->execute();
        }
    }

    private function loadPageAsync(RequestInterface $request): ResponseInterface
    {
        /** @var array{'report_run_id': string} $response */
        $response = $request->execute()->getContent();

        $reportId = $response['report_run_id'];
        $this->logger->info(sprintf('Started polling for insights job report: %s', $reportId));

        $isFinished = false;
        while (!$isFinished) {
            $request = $this->api->prepareRequest(
                '/' . $reportId,
                'GET',
            );
            /** @var array{'async_percent_completion': int, 'async_status': string} $response */
            $response = $request->execute()->getContent();

            $isFinished = $response['async_percent_completion'] === 100;
            if (!$isFinished) {
                sleep(5);
            }

            if (in_array($response['async_status'], ['Job Failed', 'Job Skipped'])) {
                throw new UserException(sprintf('Job failed: %s', $response['async_status']));
            }
        }
        $this->logger->info('Polling finished with status: ' . $response['async_status']);

        $request = $this->api->prepareRequest(
            '/' . $reportId . '/insights',
            'GET',
        );
        return $request->execute();
    }
}
