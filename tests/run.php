<?php

require __DIR__.'/../src/Level.php';
require __DIR__.'/../src/ClientOptions.php';
require __DIR__.'/../src/Scope.php';
require __DIR__.'/../src/Scrubber.php';
require __DIR__.'/../src/EventFactory.php';
require __DIR__.'/../src/Client.php';
require __DIR__.'/../src/Ase.php';
require __DIR__.'/../src/Transport/Transport.php';
require __DIR__.'/../src/Transport/BufferedTransport.php';
require __DIR__.'/../src/Transport/HmacSigner.php';
require __DIR__.'/../src/Dsn.php';

use ParkWeb\Ase\Ase;
use ParkWeb\Ase\Client;
use ParkWeb\Ase\ClientOptions;
use ParkWeb\Ase\Dsn;
use ParkWeb\Ase\Level;
use ParkWeb\Ase\Transport\BufferedTransport;
use ParkWeb\Ase\Transport\HmacSigner;
use ParkWeb\Ase\Transport\Transport;

final class ArrayTransport implements Transport
{
    public array $events = [];

    public function send(array $event): void { $this->events[] = $event; }

    public function sendBatch(array $events): void { $this->events = array_merge($this->events, $events); }

    public function flush(): void {}
}

$transport = new ArrayTransport;
$client = new Client(ClientOptions::fromArray(['dsn' => 'https://sk_ase_id:secret@example.test/api/v1/ingest/envelope']), $transport);
$client->setUser(['id' => '123', 'email' => 'person@example.test', 'password' => 'secret']);
$client->setTag('app', 'checkout');
$id = $client->captureMessage('Payment warning', Level::Warning);
assert(is_string($id) && str_starts_with($id, 'evt_'));
assert($transport->events[0]['level'] === 'warning');
assert($transport->events[0]['user']['password'] === '[REDACTED]');

$client->withScope(function ($scope, $client): void {
    $scope->setTag('temporary', 'yes');
    $client->captureException(new RuntimeException('Scoped failure'));
});
assert($transport->events[1]['exception']['type'] === RuntimeException::class);
assert(($transport->events[0]['tags']['temporary'] ?? null) === null);

$bufferedInner = new ArrayTransport;
$buffer = new BufferedTransport($bufferedInner, 2);
$buffer->send(['event_id' => 'a']);
assert(count($bufferedInner->events) === 0);
$buffer->send(['event_id' => 'b']);
assert(count($bufferedInner->events) === 2);

$headers = (new HmacSigner)->headers(Dsn::parse('https://sk_ase_id:secret@example.test/api/v1/ingest/event'), 'POST', '/api/v1/ingest/event', '{"ok":true}');
assert(isset($headers['X-ASE-Key-Id'], $headers['X-ASE-Signature']));

$tokenDsn = Dsn::parse('https://sk_ase_1234567890abcdefTOKEN@example.test/api/v1/ingest/envelope');
assert($tokenDsn->keyId === 'sk_ase_1234567890abc');
assert($tokenDsn->secret === 'sk_ase_1234567890abcdefTOKEN');

Ase::init($client);
Ase::captureMessage('Facade works');
assert(count($transport->events) === 3);

echo "php-sdk tests passed\n";
