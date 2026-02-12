<?php

declare(strict_types=1);

namespace FlagPole\Repository;

use FlagPole\Flag;
use FlagPole\FlagHydrator;
use FlagPole\Rule;

/**
 * Simple in-memory repository. Useful for bootstrapping or tests.
 */
final class InMemoryFlagRepository implements FlagRepositoryInterface
{
    /** @var array<string, Flag> */
    private array $flags = [];

    /**
     * @param array<string, array{enabled?:bool|null, rolloutPercentage?:int|null, allowList?:list<string>, rules?:list<array{attribute:string, operator:string, value:mixed}>, targetingKey?:string|null}> $config
     */
    public static function fromArray(array $config): self
    {
        $hydrator = new FlagHydrator();
        $items = [];
        foreach ($config as $name => $def) {
            $items[$name] = $hydrator->hydrate((string)$name, $def);
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
