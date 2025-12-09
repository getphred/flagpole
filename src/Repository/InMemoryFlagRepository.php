<?php

declare(strict_types=1);

namespace FlagPole\Repository;

use FlagPole\Flag;

/**
 * Simple in-memory repository. Useful for bootstrapping or tests.
 */
final class InMemoryFlagRepository implements FlagRepositoryInterface
{
    /** @var array<string, Flag> */
    private array $flags = [];

    /**
     * @param array<string, array{enabled?:bool|null, rolloutPercentage?:int|null, allowList?:list<string>}> $config
     */
    public static function fromArray(array $config): self
    {
        $items = [];
        foreach ($config as $name => $def) {
            $items[$name] = new Flag(
                name: (string)$name,
                enabled: $def['enabled'] ?? null,
                rolloutPercentage: $def['rolloutPercentage'] ?? null,
                allowList: $def['allowList'] ?? []
            );
        }
        return new self($items);
    }

    /**
     * @param array<string, Flag> $flags
     */
    public function __construct(array $flags = [])
    {
        $this->flags = $flags;
    }

    public function get(string $name): ?Flag
    {
        return $this->flags[$name] ?? null;
    }

    /**
     * @return iterable<string, Flag>
     */
    public function all(): iterable
    {
        return $this->flags;
    }
}
