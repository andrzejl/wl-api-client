<?php

declare(strict_types=1);

namespace Tests;

use Andrzejl\WlApi\Client as ApiClient;
use Andrzejl\WlApi\Exceptions\IncorrectResponse;
use Psr\Http\Message\RequestInterface;

class RegonsTest extends MockHttpClientTestCase
{
    use ApiResponse;

    public function testSuccessRequest()
    {
        $rawData = $this->prepareSubjectRawResponse(2, false);
        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchRegons(['123456785']);

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/regons/123456785?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame(json_decode($rawData, true)['result']['subjects'], $result, 'Incorrect response data');
    }

    public function testEmptyRequest()
    {
        $rawData = $this->prepareSubjectRawResponse(0, false);
        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchRegons(['123456785']);

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/regons/123456785?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame([], $result, 'Incorrect response data');
    }

    public function testRequestException()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 404);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchRegons(['123456785']);
    }
}
