<?php

namespace BenTools\GuzzleHttp\Middleware;

use BenTools\Psr7\RequestMatcherInterface;
use Psr\Http\Message\RequestInterface;

class ThrottleConfiguration implements RequestMatcherInterface
{
    private RequestMatcherInterface $requestMatcher;

    private int $maxRequests;

    private float $duration;

    private string $storageKey;

    /**
     * ThrottleConfiguration constructor.
     */
    public function __construct(RequestMatcherInterface $requestMatcher, int $maxRequests, float $duration, string $storageKey)
    {
        $this->requestMatcher = $requestMatcher;
        $this->maxRequests    = $maxRequests;
        $this->duration       = $duration;
        $this->storageKey     = $storageKey;
    }

    public function matchRequest(RequestInterface $request)
    {
        return $this->requestMatcher->matchRequest($request);
    }

    public function getMaxRequests(): int
    {
        return $this->maxRequests;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }
}
