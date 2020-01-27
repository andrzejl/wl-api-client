WlApiClient
===========

WlApiClient library for WL API ([API Wykazu podatników VAT](https://www.gov.pl/web/kas/api-wykazu-podatnikow-vat))

## Requirements
WlApiClient depends on [HTTPlug](https://github.com/php-http/httplug) and requires virtual package `php-http/client-implementation`. Please check http://docs.php-http.org/en/latest/httplug/users.html before installation.

## Installation
```bash
composer require andrzejl/wl-api-client
```
or with all requirements
```bash
composer require php-http/guzzle6-adapter nyholm/psr7 andrzejl/wl-api-client
```

## Usage
```php
require 'vendor/autoload.php';

use Andrzejl\WlApi\Client;

$client = new Client;
$result = $client->searchBankAccounts(['70506405335016096312945164']);
```

## Testing
```bash
composer test
```

## PHP 5.6 compatibility
Use WlApiClient v0.3 in legacy PHP 5.6 project:

```bash
composer require --ignore-platform-reqs php-http/guzzle6-adapter ^1.0 php-http/httplug ^1.0 php-http/message ^1.0 andrzejl/wl-api-client ^0.3
```
