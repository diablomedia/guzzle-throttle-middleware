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

    /**
     * @return float|int
     */
    private function now()
    {
        return $this->useMicroseconds ? microtime(true) : time();
    }

    public function reset(): void
    {
        $this->counter   = 0;
        $this->expiresAt = null;
    }

    /**
     * Increment counter.
     */
    public function increment(): void
    {
        $this->counter++;
        if (1 === $this->counter) {
            $this->expiresAt = $this->now() + $this->expiresIn;
        }
    }

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

    public function isExpired(): bool
    {
        return null !== $this->expiresAt && 0.0 === $this->getRemainingTime();
    }

    /**
     * @return array{'e': ?float, 'm': bool, 'i': float, 'n': int} $serialized
     */
    public function __serialize(): array
    {
        return [
            'm'         => $this->useMicroseconds,
            'i'         => $this->expiresIn,
            'e'         => $this->expiresAt,
            'n'         => $this->counter,
        ];
    }

    /**
     * @param array{'e': ?float, 'm': bool, 'i': float, 'n': int} $serialized
     */
    public function __unserialize(array $serialized): void
    {
        $this->expiresAt       = $serialized['e'];
        $this->expiresIn       = $serialized['i'];
        $this->counter         = $serialized['n'];
        $this->useMicroseconds = $serialized['m'];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'm' => $this->useMicroseconds,
            'i' => $this->expiresIn,
            'e' => $this->expiresAt,
            'n' => $this->counter,
        ];
    }

    /**
     * @return array{'counter': int, 'microseconds': bool, 'expiresIn': float, 'expiresAt': ?float, 'now': float, 'remaining': float|int, 'expired': bool}
     */
    public function __debugInfo(): array
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
