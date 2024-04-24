<?php

namespace BenTools\GuzzleHttp\Middleware;

use BenTools\GuzzleHttp\Middleware\Storage\Adapter\ArrayAdapter;
use BenTools\GuzzleHttp\Middleware\Storage\Counter;
use BenTools\GuzzleHttp\Middleware\Storage\ThrottleStorageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class ThrottleMiddleware
{
    /**
     * @var ThrottleConfiguration[]
     */
    private array $configurations = [];

    private ThrottleStorageInterface $storage;

    private LoggerInterface $logger;

    private string $logLevel;

    /**
     * ThrottleMiddleware constructor.
     */
    public function __construct(ThrottleStorageInterface $storage = null, ?LoggerInterface $logger = null, string $logLevel = LogLevel::INFO)
    {
        $this->storage  = $storage ?? new ArrayAdapter();
        $this->logger   = $logger ?? new NullLogger();
        $this->logLevel = $logLevel;
    }

    public function registerConfiguration(ThrottleConfiguration $configuration): void
    {
        $this->configurations[$configuration->getStorageKey()] = $configuration;
    }


    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            foreach ($this->configurations as $configuration) {
                if ($configuration->matchRequest($request)) {
                    $this->processConfiguration($configuration);
                    break;
                }
            }
            return $handler($request, $options);
        };
    }

    private function processConfiguration(ThrottleConfiguration $configuration): void
    {
        try {
            $counter = $this->storage->getCounter($configuration->getStorageKey());
            if ($counter === null) {
                $counter = new Counter($configuration->getDuration());
            }
        } catch (\TypeError $e) {
            $counter = new Counter($configuration->getDuration());
        }

        if (!$counter->isExpired()) {
            if ($counter->count() >= $configuration->getMaxRequests()) {
                $this->logger->log(
                    $this->logLevel,
                    sprintf(
                        '%d out of %d requests reach. Sleeping %s seconds before trying again...',
                        $counter->count(),
                        $configuration->getMaxRequests(),
                        $counter->getRemainingTime()
                    )
                );
                $this->sleep($counter->getRemainingTime());
                $this->processConfiguration($configuration);
                return;
            }
        } else {
            $counter->reset();
        }

        $counter->increment();
        $this->storage->saveCounter($configuration->getStorageKey(), $counter, $configuration->getDuration());
    }

    private function sleep(float $value): void
    {
        $values       = explode('.', (string) $value);
        $seconds      = array_shift($values);
        $milliseconds = array_shift($values);
        \sleep((int) $seconds);
        if (null !== $milliseconds) {
            $milliseconds = ((float) sprintf('0.%s', $milliseconds)) * 1000;
            usleep((int) ($milliseconds * 1000));
        }
    }
}
