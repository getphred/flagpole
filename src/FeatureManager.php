<?php

declare(strict_types=1);

namespace FlagPole;

use FlagPole\Repository\FlagRepositoryInterface;
use Psr\Log\LoggerInterface;

final class FeatureManager
{
    private Evaluator $evaluator;

    public function __construct(
        private FlagRepositoryInterface $repository,
        ?Evaluator $evaluator = null,
        ?LoggerInterface $logger = null
    ) {
        $this->evaluator = $evaluator ?? new Evaluator($logger);
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
