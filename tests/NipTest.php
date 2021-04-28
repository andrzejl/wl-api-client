<?php

declare(strict_types=1);

namespace Tests;

use Andrzejl\WlApi\Client as ApiClient;
use Andrzejl\WlApi\Exceptions\IncorrectResponse;
use Psr\Http\Message\RequestInterface;

class NipTest extends MockHttpClientTestCase
{
    use ApiResponse;

    public function testSuccessRequest()
    {
        $rawData = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_SINGLE);
        $expected = json_decode($rawData, true)['result']['subject'];

        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchNip('1111111111');

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/nip/1111111111?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testEmptyRequest()
    {
        $rawData = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_SINGLE, 0);
        $expected = [];

        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchNip('1111111111');

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/nip/1111111111?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testRequestException()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 404);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchNip('1111111111');
    }

    public function testMissingSubject()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse('{"result":{}}', 200);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchBankAccount('90249000050247256316596736');
    }
}
