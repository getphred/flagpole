<?php

declare(strict_types=1);

namespace FlagPole;

/**
 * Immutable definition of a feature flag.
 */
final class Flag
{
    public function __construct(
        public readonly string $name,
        public readonly ?bool $enabled = null,
        public readonly ?int $rolloutPercentage = null,
        /** @var list<string> */
        public readonly array $allowList = [],
    ) {
        if ($this->rolloutPercentage !== null) {
            if ($this->rolloutPercentage < 0 || $this->rolloutPercentage > 100) {
                throw new \InvalidArgumentException('rolloutPercentage must be between 0 and 100');
            }
        }
    }
}
