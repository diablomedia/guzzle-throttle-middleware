<?php

namespace BenTools\GuzzleHttp\Middleware\Tests;

use BenTools\GuzzleHttp\Middleware\DurationHeaderMiddleware;
use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\ThrottleConfiguration;
use BenTools\GuzzleHttp\Middleware\ThrottleMiddleware;
use BenTools\Psr7\RequestMatcherInterface;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ThrottleMiddlewareTest extends TestCase
{
    private ArrayAdapter $adapter;

    public function setUp(): void
    {
        $this->adapter = new ArrayAdapter();
    }

    public function testMiddleware(): void
    {
        $maxRequests       = 1;
        $durationInSeconds = 0.5;
        $client            = $this->createConfiguredClient($maxRequests, $durationInSeconds);

        // The counter should not exist
        $response = $client->get('/foo');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist and block
        $response = $client->get('/bar');
        $this->assertGreaterThan($this->getExpectedDuration($durationInSeconds), $this->getRequestDuration($response));

        usleep((int) ($durationInSeconds * 1000000));

        $counter = $this->adapter->getCounter('foo');
        $this->assertNotNull($counter);

        // The counter should exist and not block
        $this->assertTrue($counter->isExpired());
        $response = $client->get('/baz');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));
    }

    public function testMiddlewareWithMultipleRequests(): void
    {
        $maxRequests       = 3;
        $durationInSeconds = 0.5;
        $client            = $this->createConfiguredClient($maxRequests, $durationInSeconds);

        // The counter should not exist: 0/3
        $this->assertFalse($this->adapter->hasCounter('foo'));
        $response = $client->get('/php');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        $counter = $this->adapter->getCounter('foo');
        $this->assertNotNull($counter);

        // The counter should exist: 1/3
        $this->assertEquals(1, $counter->count());
        $response = $client->get('/javascript');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 2/3
        $this->assertEquals(2, $counter->count());
        $response = $client->get('/html');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist and block: 3/3
        $this->assertEquals(3, $counter->count());
        $response = $client->get('/css');
        $this->assertGreaterThan($this->getExpectedDuration($durationInSeconds), $this->getRequestDuration($response));

        usleep((int) ($durationInSeconds * 1000000));

        // The counter should have been reset: 0/3
        $response = $client->get('/python');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 1/3
        $this->assertEquals(1, $counter->count());
        $response = $client->get('/java');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist: 2/3
        $this->assertEquals(2, $counter->count());
        $response = $client->get('/go');
        $this->assertLessThan(0.005, $this->getRequestDuration($response));

        // The counter should exist and block: 3/3
        $this->assertEquals(3, $counter->count());
        $response = $client->get('/ruby');
        $this->assertGreaterThan($this->getExpectedDuration($durationInSeconds), $this->getRequestDuration($response));
    }

    private function getExpectedDuration(float $durationInSeconds): float
    {
        return $durationInSeconds - 0.3; // We have to minus 0.03 because sometimes PHP is a little faster :)
    }

    private function getRequestDuration(ResponseInterface $response): float
    {
        return (float) $response->getHeaderLine('X-Request-Duration');
    }

    private function createConfiguredClient(int $maxRequests, float $duration, string $storageKey = 'foo'): Client
    {
        $stack = HandlerStack::create(function (RequestInterface $request, array $options) {
            return new FulfilledPromise(new Response());
        });
        $middleware = new ThrottleMiddleware($this->adapter);
        $stack->push(new DurationHeaderMiddleware(), 'duration');
        $stack->push($middleware, 'throttle');
        $client = new Client([
            'handler' => $stack,
        ]);

        $middleware->registerConfiguration(new ThrottleConfiguration($this->createRequestMatcher(function () {
            return true;
        }), $maxRequests, $duration, $storageKey));
        return $client;
    }

    private function createRequestMatcher(callable $requestMatcher): RequestMatcherInterface
    {
        return new class($requestMatcher) implements RequestMatcherInterface {
            /**
             * @var callable
             */
            private $requestMatcher;

            /**
             * @param callable $requestMatcher
             */
            public function __construct($requestMatcher)
            {
                $this->requestMatcher = $requestMatcher;
            }

            public function matchRequest(RequestInterface $request)
            {
                $callable = $this->requestMatcher;
                return $callable($request);
            }

        };
    }

}
