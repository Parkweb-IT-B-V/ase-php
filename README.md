# parkweb/ase-php

Generic PHP SDK for All Seeing Eye.

```bash
composer require parkweb/ase-php php-http/discovery guzzlehttp/guzzle nyholm/psr7
```

```php
use ParkWeb\Ase\Ase;
use ParkWeb\Ase\Client;
use ParkWeb\Ase\ClientOptions;
use ParkWeb\Ase\Dsn;
use ParkWeb\Ase\ErrorHandler;
use ParkWeb\Ase\Level;
use ParkWeb\Ase\Transport\BufferedTransport;
use ParkWeb\Ase\Transport\SyncTransport;

$options = ClientOptions::fromArray([
    'dsn' => $_ENV['ASE_DSN'],
    'release' => $_ENV['ASE_RELEASE'] ?? null,
    'sample_rate' => 1.0,
    'timeout' => 1.5,
    'max_retries' => 1,
    'gzip' => true,
    'send_default_pii' => false,
]);

$dsn = Dsn::parse($options->dsn);
$transport = new BufferedTransport(new SyncTransport($options, $dsn, $psr18Client, $requestFactory, $streamFactory, $logger));
$client = new Client($options, $transport);
Ase::init($client);
(new ErrorHandler($client))->register();

Ase::setUser(['id' => '123']);
Ase::setTag('tenant', 'acme');
Ase::addBreadcrumb(['category' => 'checkout', 'message' => 'Payment submitted']);
Ase::captureMessage('Slow checkout dependency', Level::Warning);
Ase::captureException($throwable);
Ase::flush();
```

DSN format:

```text
https://{server-key-id}:{server-secret}@api-ase.parkwebit.nl/api/v1/ingest/envelope
```

Safety defaults:

- SDK failures are swallowed.
- Short timeout and one retry by default.
- Authorization, cookies, tokens, passwords, private keys and card-like values are scrubbed.
- Request bodies are not captured unless the integration explicitly adds them.
- HMAC headers are generated per request and the server key is never logged.

Run package smoke tests:

```bash
php tests/run.php
```
