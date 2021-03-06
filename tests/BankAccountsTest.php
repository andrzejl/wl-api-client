<?php

declare(strict_types=1);

namespace Tests;

use Andrzejl\WlApi\Client as ApiClient;
use Andrzejl\WlApi\Exceptions\IncorrectResponse;
use Andrzejl\WlApi\Exceptions\LimitExceeded;
use Psr\Http\Message\RequestInterface;

class BankAccountsTest extends MockHttpClientTestCase
{
    use ApiResponse;

    public function testSuccessRequest()
    {
        $rawData = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_ENTRIES, 2, 2);
        $expected = json_decode($this->prepareSubjectRawResponse(ApiResponseType::TYPE_SUBJECTS, 4), true)['result']['subjects'];

        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchBankAccounts(['90249000050247256316596736']);

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/bank-accounts/90249000050247256316596736?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testEmptyRequest()
    {
        $rawData = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_ENTRIES, 0, 0);
        $expected = [];

        $httpClient = $this->prepareHttpClientWithSubjectResponse($rawData, 200);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchBankAccounts(['90249000050247256316596736']);

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('GET', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/bank-accounts/90249000050247256316596736?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testRequestException()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 404);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchBankAccounts(['90249000050247256316596736']);
    }

    public function testLimitExceededException()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 429);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(LimitExceeded::class);
        $apiClient->searchBankAccounts(['90249000050247256316596736']);
    }

    public function testSplittedRequest()
    {
        $rawDataFirst = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_ENTRIES, 1, 30);
        $rawDataSecond = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_ENTRIES, 1, 1);
        $expected = json_decode($this->prepareSubjectRawResponse(ApiResponseType::TYPE_SUBJECTS, 31), true)['result']['subjects'];

        $httpClient = $this->prepareHttpClientWithSubjectResponse(
            [
                ['responseCode' => 200, 'rawData' => $rawDataFirst],
                ['responseCode' => 200, 'rawData' => $rawDataSecond],
            ]);
        $apiClient = new ApiClient($httpClient);
        $result = $apiClient->searchBankAccounts(range(1,31));

        /** @var RequestInterface $request */
        $requests = $httpClient->getRequests();

        $this->assertSame('GET', $requests[0]->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/bank-accounts/' . (implode(',', range(1, 30))) . '?date=' . (new \DateTime('now'))->format('Y-m-d'), $requests[0]->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame('GET', $requests[1]->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/bank-accounts/31?date=' . (new \DateTime('now'))->format('Y-m-d'), $requests[1]->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testSplittedParitalResult()
    {
        $rawDataFirst = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_ENTRIES, 1, 30);
        $rawDataSecond = $this->prepareSubjectRawResponse(ApiResponseType::TYPE_ENTRIES, 1, 1);
        $expected = json_decode($this->prepareSubjectRawResponse(ApiResponseType::TYPE_SUBJECTS, 30), true)['result']['subjects'];

        $httpClient = $this->prepareHttpClientWithSubjectResponse(
            [
                ['responseCode' => 200, 'rawData' => $rawDataFirst],
                ['responseCode' => 429, 'rawData' => $rawDataSecond],
            ]
        );
        $apiClient = new ApiClient($httpClient);
        try {
            $result = $apiClient->searchBankAccounts(range(1, 31));
            $this->fail('Doesn\'t throw LimitExceeded exception');
        } catch (LimitExceeded $e) {
            $result = $apiClient->getLastResult();
        }

        /** @var RequestInterface $request */
        $requests = $httpClient->getRequests();

        $this->assertSame('GET', $requests[0]->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/bank-accounts/' . (implode(',', range(1, 30))) . '?date=' . (new \DateTime('now'))->format('Y-m-d'), $requests[0]->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame('GET', $requests[1]->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/api/search/bank-accounts/31?date=' . (new \DateTime('now'))->format('Y-m-d'), $requests[1]->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame($expected, $result, 'Incorrect response data');
    }

    public function testMissingEntriesNotAnArray()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse('{"result":{"entries":"string"}}', 200);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchBankAccounts(['90249000050247256316596736']);
    }

    public function testMissingSubjectsNotAnArray()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse('{"result":{"entries":[{"subjects":"string"}]}}', 200);
        $apiClient = new ApiClient($httpClient);
        $this->expectException(IncorrectResponse::class);
        $apiClient->searchBankAccounts(['90249000050247256316596736']);
    }
}
