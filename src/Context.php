<?php

declare(strict_types=1);

namespace FlagPole;

/**
 * Evaluation context passed to the flag evaluator.
 * Wrapper around an associative array of attributes (e.g., userId, email, team).
 */
final class Context
{
    /** @var array<string, scalar|null> */
    private array $attributes;

    /**
     * @param array<string, scalar|null> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param array<string, scalar|null> $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self($attributes);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function all(): array
    {
        return $this->attributes;
    }
}
