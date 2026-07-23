<?php

namespace ParkWeb\Ase\Transport;

use ParkWeb\Ase\ClientOptions;
use ParkWeb\Ase\Dsn;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final readonly class SyncTransport implements Transport
{
    public function __construct(
        private ClientOptions $options,
        private Dsn $dsn,
        private ClientInterface $http,
        private RequestFactoryInterface $requests,
        private StreamFactoryInterface $streams,
        private ?LoggerInterface $logger = null,
        private HmacSigner $signer = new HmacSigner,
        private RecursionGuard $guard = new RecursionGuard,
    ) {}

    public function send(array $event): void
    {
        $this->sendBatch([$event]);
    }

    public function sendBatch(array $events): void
    {
        if ($events === []) {
            return;
        }

        $this->guard->run(function () use ($events): void {
            $this->attemptSend($events);
        });
    }

    public function flush(): void {}

    /** @param array<int, array<string, mixed>> $events */
    private function attemptSend(array $events): void
    {
        try {
            $body = json_encode(['sent_at' => gmdate(DATE_ATOM), 'events' => $events], JSON_THROW_ON_ERROR);
            $path = parse_url($this->dsn->endpoint, PHP_URL_PATH) ?: '/api/v1/ingest/envelope';
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'parkweb-ase-php/0.1.0',
            ];

            if ($this->options->gzip && function_exists('gzencode')) {
                $body = gzencode($body) ?: $body;
                $headers['Content-Encoding'] = 'gzip';
            }

            $headers += $this->signer->headers($this->dsn, 'POST', $path, $body);

            $request = $this->requests->createRequest('POST', $this->dsn->endpoint)
                ->withBody($this->streams->createStream($body));
            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            $attempts = max(1, $this->options->maxRetries + 1);
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                $response = $this->http->sendRequest($request);
                $status = $response->getStatusCode();
                if ($status >= 200 && $status < 300) {
                    return;
                }
                if ($status < 500) {
                    $this->logger?->warning('ASE transport rejected event batch', [
                        'status' => $status,
                        'body' => $this->options->debug ? mb_substr((string) $response->getBody(), 0, 2000) : null,
                    ]);

                    return;
                }
            }
        } catch (\Throwable $throwable) {
            $this->logger?->debug('ASE transport failed', ['exception' => $throwable::class, 'message' => $throwable->getMessage()]);
        }
    }
}
