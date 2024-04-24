<?php

namespace BenTools\GuzzleHttp\Middleware\Storage;

class Counter implements \JsonSerializable, \Countable
{
    private bool $useMicroseconds;

    private float $expiresIn;

    private ?float $expiresAt;

    private int $counter;

    /**
     * Counter constructor.
     */
    public function __construct(float $expiresIn)
    {
        $this->useMicroseconds = intval($expiresIn) != $expiresIn;
        $this->expiresIn       = $this->useMicroseconds ? $expiresIn : intval($expiresIn);
        $this->reset();
    }

    private function now()
    {
        return $this->useMicroseconds ? microtime(true) : time();
    }

    public function reset()
    {
        $this->counter   = 0;
        $this->expiresAt = null;
    }

    /**
     * Increment counter.
     */
    public function increment()
    {
        $this->counter++;
        if (1 === $this->counter) {
            $this->expiresAt = $this->now() + $this->expiresIn;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        if ($this->isExpired()) {
            $this->reset();
        }
        return $this->counter;
    }

    /**
     * @return int|float
     */
    public function getRemainingTime()
    {
        $remainingTime = (float) max(0, $this->expiresAt - $this->now());
        if (false === $this->useMicroseconds) {
            $remainingTime = ceil($remainingTime);
        }
        return $remainingTime;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return null !== $this->expiresAt && 0.0 === $this->getRemainingTime();
    }

    public function __serialize()
    {
        return [
            'm'         => $this->useMicroseconds,
            'i'         => $this->expiresIn,
            'e'         => $this->expiresAt,
            'n'         => $this->counter,
        ];
    }

    public function __unserialize(array $serialized)
    {
        $this->expiresAt       = $serialized['e'];
        $this->expiresIn       = $serialized['i'];
        $this->counter         = $serialized['n'];
        $this->useMicroseconds = $serialized['m'];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'm' => $this->useMicroseconds,
            'i' => $this->expiresIn,
            'e' => $this->expiresAt,
            'n' => $this->counter,
        ];
    }

    public function __debugInfo()
    {
        return [
            'counter'      => $this->counter,
            'microseconds' => $this->useMicroseconds,
            'expiresIn'    => $this->expiresIn,
            'expiresAt'    => $this->expiresAt,
            'now'          => $this->now(),
            'remaining'    => $this->useMicroseconds ? $this->getRemainingTime() : round($this->getRemainingTime()),
            'expired'      => $this->isExpired(),
        ];
    }
}
