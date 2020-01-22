<?php

declare(strict_types=1);

namespace Tests;

use Andrzejl\WlApi\Exceptions\IncorrectParameter;

class ClientTest extends MockHttpClientTestCase
{

    public function testFormatParametersNotFound()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 200);
        $apiClient = new TestClient($httpClient);
        $this->expectException(IncorrectParameter::class);
        $apiClient->formatParametersNotFound();
    }

    public function testFormatParametersNotAnArray()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse(null, 200);
        $apiClient = new TestClient($httpClient);
        $this->expectException(IncorrectParameter::class);
        $apiClient->formatParametersNotAnArray();
    }

    public function testStringResponse()
    {
        $httpClient = $this->prepareHttpClientWithSubjectResponse('{"result":{"value":"random string"}}', 200);
        $apiClient = new TestClient($httpClient);
        $result = $apiClient->fakeRequest();

        /** @var RequestInterface $request */
        $request = $httpClient->getLastRequest();

        $this->assertSame('POST', $request->getMethod(), 'Wrong reuqest method');
        $this->assertSame('https://wl-api.mf.gov.pl/fake?date=' . (new \DateTime('now'))->format('Y-m-d'), $request->getUri()->__toString(), 'Incorrect URI');
        $this->assertSame('random string', $result, 'Incorrect response data');
    }
}
