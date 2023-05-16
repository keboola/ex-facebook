<?php

declare(strict_types=1);

namespace phpunit;

use FacebookAds\Api;
use FacebookAds\Http\Request;
use FacebookAds\Http\Response;
use Keboola\FacebookExtractor\FacebookExtractor;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class FacebookaExtractorTest extends TestCase
{
    public function testGetAccounts(): void
    {
        $apiMock = $this->getMockBuilder(Api::class)->disableOriginalConstructor()->getMock();

        $requestMock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $responsePageMock = $this->getMockBuilder(Response::class)->getMock();
        $responseAdMock = $this->getMockBuilder(Response::class)->getMock();
        $responseIgMock = $this->getMockBuilder(Response::class)->getMock();

        $responsePageContent = ['data' => ['response page content']];
        $responseAdContent = ['data' => ['response ad content']];
        $responseIgContent = ['data' => ['response instagram content']];

        $responsePageMock->method('getContent')->willReturn($responsePageContent);
        $responseAdMock->method('getContent')->willReturn($responseAdContent);
        $responseIgMock->method('getContent')->willReturn($responseIgContent);

        $apiMock->expects($this->exactly(3))
            ->method('prepareRequest')
            ->willReturnMap([
                ['/me/accounts', 'GET', [], $requestMock],
                ['/me/adaccounts', 'GET', ['fields' => 'account_id,id,business_name,name,currency'], $requestMock],
                ['/me/accounts', 'GET', ['fields' => 'instagram_business_account,name,category'], $requestMock],
            ]);

        $requestMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturnOnConsecutiveCalls($responsePageMock, $responseAdMock, $responseIgMock);

        $extractor = new FacebookExtractor($apiMock);

        $responsePage = $extractor->getAccounts('/me/accounts');
        $responseAd = $extractor->getAccounts('/me/adaccounts', 'account_id,id,business_name,name,currency');
        $responseIg = $extractor->getAccounts('/me/accounts', 'instagram_business_account,name,category');

        Assert::assertEquals($responsePageContent['data'], $responsePage);
        Assert::assertEquals($responseAdContent['data'], $responseAd);
        Assert::assertEquals($responseIgContent['data'], $responseIg);
    }
}
