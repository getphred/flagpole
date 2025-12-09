<?php

declare(strict_types=1);

namespace FlagPole;

use FlagPole\Repository\FlagRepositoryInterface;

final class FeatureManager
{
    public function __construct(
        private FlagRepositoryInterface $repository,
        private Evaluator $evaluator = new Evaluator()
    ) {
    }

    public function isEnabled(string $flagName, ?Context $context = null, bool $default = false): bool
    {
        $flag = $this->repository->get($flagName);
        if ($flag === null) {
            return $default;
        }
        return $this->evaluator->evaluate($flag, $context, $default);
    }
}
