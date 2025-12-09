<?php

declare(strict_types=1);

namespace FlagPole;

final class Evaluator
{
    public function evaluate(Flag $flag, ?Context $context = null, bool $default = false): bool
    {
        $context ??= new Context();

        // 1) Allow list wins if present
        if (!empty($flag->allowList)) {
            $key = $this->resolveTargetingKey($context);
            if ($key !== null && in_array((string)$key, $flag->allowList, true)) {
                return true;
            }
        }

        // 2) Explicit on/off
        if ($flag->enabled !== null) {
            return $flag->enabled;
        }

        // 3) Percentage rollout if available
        if ($flag->rolloutPercentage !== null) {
            $key = $this->resolveTargetingKey($context);
            if ($key === null) {
                return $default;
            }
            $bucket = $this->computeBucket($flag->name, (string)$key);
            return $bucket < $flag->rolloutPercentage;
        }

        // 4) Fallback
        return $default;
    }

    private function resolveTargetingKey(Context $context): ?string
    {
        $candidates = ['key', 'userId', 'id', 'email'];
        foreach ($candidates as $attr) {
            $v = $context->get($attr);
            if ($v !== null && $v !== '') {
                return (string)$v;
            }
        }
        return null;
    }

    private function computeBucket(string $flagName, string $key): int
    {
        $hash = crc32($flagName . ':' . $key);
        // Normalize to unsigned 32-bit to avoid negative values on some platforms
        $unsigned = (int) sprintf('%u', $hash);
        // Map to 0..99
        return (int)($unsigned % 100);
    }
}
