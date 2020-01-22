<?php

namespace Tests;

use Andrzejl\WlApi\Client;

class TestClient extends Client
{
    const API_FAKE = 'fake';

    protected $endpoints = [
        self::API_FAKE => [
            'method' => 'POST',
            'path' => '/fake',
            'response' => 'value',
            'type' => self::TYPE_STRING,
        ],
    ];

    public function formatParametersNotFound()
    {
        return $this->formatParameters(['unknown' => 'unknown']);
    }

    public function formatParametersNotAnArray()
    {
        return $this->formatParameters(['bank-accounts' => 'string']);
    }

    public function fakeRequest()
    {
        return $this->sendRequest(self::API_FAKE, null);
    }
}
