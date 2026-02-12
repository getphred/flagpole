<?php

declare(strict_types=1);

namespace FlagPole;

/**
 * A rule defines a condition that must be met for a flag to be enabled.
 */
final class Rule
{
    public function __construct(
        public readonly string $attribute,
        public readonly string $operator,
        public readonly mixed $value,
    ) {
    }
}
