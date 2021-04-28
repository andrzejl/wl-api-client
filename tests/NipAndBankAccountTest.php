<?php

declare(strict_types=1);

namespace Tests;

use Andrzejl\WlApi\Client as ApiClient;
use Andrzejl\WlApi\Exceptions\IncorrectResponse;
use Psr\Http\Message\RequestInterface;

class NipAndBankAccountTest extends MockHttpClientTestCase
{
    use ApiResponse;

    public function testSuccessYesRequest()
    {
        $rawData = $this->prepareAssignedRawResponse(true);
        $expected = true;

        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->checkNipAndBankAccount('1111111111', '90249000050247256316596736');

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/check/nip/1111111111/bank-account/90249000050247256316596736?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testSuccessNoRequest()
    {
        $rawData = $this->prepareAssignedRawResponse(false);
        $expected = false;

        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->checkNipAndBankAccount('1111111111', '90249000050247256316596736');

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/check/nip/1111111111/bank-account/90249000050247256316596736?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testRequestException()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 404);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->checkNipAndBankAccount('1111111111', '90249000050247256316596736');
    }

    public function testIncorrectResponseException()
    {
        $rawData = $this->prepareAssignedRawResponse('unknown');
        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->checkNipAndBankAccount('1111111111', '90249000050247256316596736');
    }

    public function testMissingAccountAssigned()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse('{"result":{}}', 200);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchBankAccount('90249000050247256316596736');
    }
}
