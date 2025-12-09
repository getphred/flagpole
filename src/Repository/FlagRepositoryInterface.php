<?php

declare(strict_types=1);

namespace FlagPole\Repository;

use FlagPole\Flag;

interface FlagRepositoryInterface
{
    public function get(string $name): ?Flag;

    /**
     * @return iterable<string, Flag>
     */
    public function all(): iterable;
}
