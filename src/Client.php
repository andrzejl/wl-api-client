<?php

namespace Andrzejl\WlApi;

use Andrzejl\WlApi\Exceptions\IncorrectParameter;
use Andrzejl\WlApi\Exceptions\IncorrectResponse;
use Andrzejl\WlApi\Exceptions\LimitExceeded;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;

class Client
{

    const API_SEARCH_BANK_ACCOUNT = 'search-bank-account';
    const API_SEARCH_BANK_ACCOUNTS = 'search-bank-accounts';
    const API_CHECK_NIP_AND_BANK_ACCOUNT = 'check-nip-and-bank-account';
    const API_CHECK_REGON_AND_BANK_ACCOUNT = 'check-regon-and-bank-account';
    const API_SEARCH_NIP = 'search-nip';
    const API_SEARCH_NIPS = 'search-nips';
    const API_SEARCH_REGON = 'search-regon';
    const API_SEARCH_REGONS = 'search-regons';

    const TYPE_STRING = 'string';
    const TYPE_ARRAY = 'array';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_OBJECT = 'object';

    /**
     * Endpoints request options list
     *
     * @var array
     */
    protected $endpoints = [
        self::API_SEARCH_BANK_ACCOUNT => [
            'method' => 'GET',
            'path' => '/api/search/bank-account/{bank-account}',
            'response' => 'subjects',
            'type' => self::TYPE_ARRAY,
        ],
        self::API_SEARCH_BANK_ACCOUNTS => [
            'method' => 'GET',
            'path' => '/api/search/bank-accounts/{bank-accounts}',
            'response' => 'subjects',
            'type' => self::TYPE_ARRAY,
        ],
        self::API_CHECK_NIP_AND_BANK_ACCOUNT => [
            'method' => 'GET',
            'path' => '/api/check/nip/{nip}/bank-account/{bank-account}',
            'response' => 'accountAssigned',
            'type' => self::TYPE_BOOLEAN,
        ],
        self::API_CHECK_REGON_AND_BANK_ACCOUNT => [
            'method' => 'GET',
            'path' => '/api/check/regon/{regon}/bank-account/{bank-account}',
            'response' => 'accountAssigned',
            'type' => self::TYPE_BOOLEAN,
        ],
        self::API_SEARCH_NIP => [
            'method' => 'GET',
            'path' => '/api/search/nip/{nip}',
            'response' => 'subject',
            'type' => self::TYPE_OBJECT,
        ],
        self::API_SEARCH_NIPS => [
            'method' => 'GET',
            'path' => '/api/search/nips/{nips}',
            'response' => 'subjects',
            'type' => self::TYPE_ARRAY,
        ],
        self::API_SEARCH_REGON => [
            'method' => 'GET',
            'path' => '/api/search/regon/{regon}',
            'response' => 'subject',
            'type' => self::TYPE_OBJECT,
        ],
        self::API_SEARCH_REGONS => [
            'method' => 'GET',
            'path' => '/api/search/regons/{regons}',
            'response' => 'subjects',
            'type' => self::TYPE_ARRAY,
        ],
    ];

    /**
     * Parameters allowed in requests
     *
     * @var array
     */
    protected $allowedParameters = [
        'bank-account'  => [ 'type' => self::TYPE_STRING ],
        'bank-accounts' => [ 'type' => self::TYPE_ARRAY ],
        'nip'           => [ 'type' => self::TYPE_STRING ],
        'nips'          => [ 'type' => self::TYPE_ARRAY ],
        'regon'         => [ 'type' => self::TYPE_STRING ],
        'regons'        => [ 'type' => self::TYPE_ARRAY ],
    ];

    /**
     * WL API Base URL
     *
     * @var string
     */
    protected $baseUrl = 'https://wl-api.mf.gov.pl/';

    /**
     * Max items (like bank accounts, nips or regons) in query.
     *
     * @var integer
     */
    protected $maxQueryItems = 30;

    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $lastResult = [];

    /**
     * @param HttpClient $client
     * @param MessageFactory $messageFactory
     */
    public function __construct(HttpClient $client = null, MessageFactory $messageFactory = null)
    {
        $this->client = $client ?: HttpClientDiscovery::find();
        $this->messageFactory = $messageFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * Returns base URL of the WL API used in the library.
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * Returns parameters mapping array.
     *
     * @param array $parameters
     * @return array
     */
    protected function formatParameters($parameters)
    {
        if (!is_array($parameters)) {
            return [];
        }

        $result = [];
        foreach ($parameters as $parameter => $value) {
            if (!array_key_exists($parameter, $this->allowedParameters)) {
                throw new IncorrectParameter(sprintf('Parameter %s not found', $parameter));
            }
            $options = $this->allowedParameters[$parameter];

            switch ($options['type']) {
                case self::TYPE_ARRAY:
                    if (!is_array($value)) {
                        throw new IncorrectParameter(sprintf('Parameter %s is not an array', $parameter));
                    }
                    $result['{' . $parameter . '}'] = implode(',', $value);
                    break;
                case self::TYPE_STRING:
                    $result['{' . $parameter . '}'] = $value;
                    break;
            }
        }
        return $result;
    }

    /**
     * Sends request to API and returns subjects from response.
     *
     * @param mixed $type
     * @param array $parameters
     */
    protected function sendRequest($type, $parameters)
    {
        $options = $this->endpoints[$type];

        $mapping = $this->formatParameters($parameters);
        $path = ltrim(str_replace(array_keys($mapping), $mapping, $options['path']), '/');

        $url = $this->getBaseUrl() . $path . '?' . http_build_query([
            'date' => (new \DateTime('now'))->format('Y-m-d')
        ]);

        $request = $this->messageFactory->createRequest($options['method'], $url);
        $response = $this->client->sendRequest($request);
        if ($response->getStatusCode() === 429) {
            throw new LimitExceeded;
        }
        if ($response->getStatusCode() !== 200) {
            throw new IncorrectResponse;
        }
        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['result'])) {
            throw new IncorrectResponse;
        }

        if (!isset($data['result'][$options['response']])) {
            throw new IncorrectResponse;
        }

        $value = $data['result'][$options['response']];
        $result = null;
        switch ($options['type']) {
            case self::TYPE_ARRAY:
                if (!is_array($value)) {
                    throw new IncorrectResponse;
                }
                $result = $value;
                break;

            case self::TYPE_BOOLEAN:
                $value = strtolower($value);
                if (!in_array($value, ['tak', 'nie'])) {
                    throw new IncorrectResponse;
                }
                $result = ($value === 'tak') ? true : false;
                break;

            case self::TYPE_OBJECT:
                $result = $value;
                break;

            case self::TYPE_STRING:
                $result = $value;
                break;
        }

        $this->lastResult = $result;
        return $result;
    }

    /**
     * Sends request splitted in chuncks and returns combined response.
     *
     * @param array $items
     * @param integer $max
     */
    protected function sendSplittedRequest($type, $parameterName, $values)
    {
        $result = [];
        $splittedValues = array_chunk($values, $this->maxQueryItems);
        foreach ($splittedValues as $valuesChunk) {
            $this->lastResult = $result;
            $result = array_merge($result, $this->sendRequest($type, [$parameterName => $valuesChunk]));
        }

        $this->lastResult = $result;
        return $result;
    }

    /**
     * Returns subjects found by account number.
     *
     * @param string $accountNumber
     */
    public function searchBankAccount(string $accountNumber)
    {
        return $this->sendRequest(self::API_SEARCH_BANK_ACCOUNT, ['bank-account' => $accountNumber]);
    }

    /**
     * Returns subjects found by account numbers.
     *
     * @param array $accountNumbers
     */
    public function searchBankAccounts(array $accountNumbers)
    {
        return $this->sendSplittedRequest(self::API_SEARCH_BANK_ACCOUNTS, 'bank-accounts', $accountNumbers);
    }

    /**
     * Checks if NIP and account number belongs to same subject.
     *
     * @param string $nip
     * @param string $accountNumber
     */
    public function checkNipAndBankAccount(string $nip, string $accountNumber)
    {
        return $this->sendRequest(self::API_CHECK_NIP_AND_BANK_ACCOUNT, ['nip' => $nip, 'bank-account' => $accountNumber]);
    }

    /**
     * Checks if NIP and account number belongs to same subject.
     *
     * @param string $regon
     * @param string $accountNumber
     */
    public function checkRegonAndBankAccount(string $regon, string $accountNumber)
    {
        return $this->sendRequest(self::API_CHECK_REGON_AND_BANK_ACCOUNT, ['regon' => $regon, 'bank-account' => $accountNumber]);
    }

    /**
     * Returns subjects found by nip.
     *
     * @param string $nip
     */
    public function searchNip(string $nip)
    {
        return $this->sendRequest(self::API_SEARCH_NIP, ['nip' => $nip]);
    }

    /**
     * Returns subjects found by nips.
     *
     * @param array $nips
     */
    public function searchNips(array $nips)
    {
        return $this->sendRequest(self::API_SEARCH_NIPS, ['nips' => $nips]);
    }

    /**
     * Returns subjects found by regon.
     *
     * @param string $regon
     */
    public function searchRegon(string $regon)
    {
        return $this->sendRequest(self::API_SEARCH_REGON, ['regon' => $regon]);
    }

    /**
     * Returns subjects found by regons.
     *
     * @param array $regons
     */
    public function searchRegons(array $regons)
    {
        return $this->sendRequest(self::API_SEARCH_REGONS, ['regons' => $regons]);
    }

    /**
     * Returns last result
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }
}
