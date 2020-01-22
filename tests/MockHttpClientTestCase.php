<?php

declare(strict_types=1);

namespace Tests;

use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MockHttpClientTestCase extends TestCase
{

    /**
     * Returns fake response
     *
     * @param [type] $rawData
     */
    protected function prepareResponse($rawData, $responseCode = 200)
    {
        $stub = $this->createMock(ResponseInterface::class);
        $stub->method('getStatusCode')->willReturn($responseCode);

        if ($responseCode == 200) {
            $stream = $this->createMock(StreamInterface::class);
            $stream->method('getContents')->willReturn($rawData);

            $stub->method('getBody')->willReturn($stream);
        }

        /** @var ResponseInterface $response */
        $response = $stub;

        return $response;
    }

    /**
     * Returns fake Http Client with fake data.
     *
     * @param string|array $rawData
     * @param integer $responseCode
     */
    protected function prepareHttpClientWithSubjectResponse($rawData, $responseCode = 200)
    {
        $httpClient = new Client();

        if (!is_array($rawData)) {
            $rawData = [['responseCode' => $responseCode, 'rawData' => $rawData]];
        }

        foreach ($rawData as $data) {
            $httpClient->addResponse($this->prepareResponse($data['rawData'], $data['responseCode']));
        }

        return $httpClient;
    }
}
