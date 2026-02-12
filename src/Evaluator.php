<?php

declare(strict_types=1);

namespace FlagPole;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Evaluator
{
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function evaluate(Flag $flag, ?Context $context = null, bool $default = false): bool
    {
        $context ??= new Context();

        // 1) Allow list wins if present
        if (!empty($flag->allowList)) {
            $key = $this->resolveTargetingKey($context, $flag->targetingKey);
            if ($key !== null && in_array((string)$key, $flag->allowList, true)) {
                $this->logger->info(sprintf('Flag "%s" enabled for key "%s" via allowList.', $flag->name, $key));
                return true;
            }
        }

        // 2) Explicit on/off
        if ($flag->enabled !== null) {
            $this->logger->info(sprintf('Flag "%s" evaluated to %s via explicit "enabled" override.', $flag->name, $flag->enabled ? 'TRUE' : 'FALSE'));
            return $flag->enabled;
        }

        // 3) Rules matching
        if (!empty($flag->rules)) {
            foreach ($flag->rules as $rule) {
                if ($this->matchRule($rule, $context)) {
                    $this->logger->info(sprintf('Flag "%s" enabled via rule (attribute: %s, operator: %s).', $flag->name, $rule->attribute, $rule->operator));
                    return true;
                }
            }
        }

        // 4) Percentage rollout if available
        if ($flag->rolloutPercentage !== null) {
            $key = $this->resolveTargetingKey($context, $flag->targetingKey);
            if ($key === null) {
                $this->logger->info(sprintf('Flag "%s" rollout evaluation failed: no targeting key found. Using default: %s.', $flag->name, $default ? 'TRUE' : 'FALSE'));
                return $default;
            }
            $bucket = $this->computeBucket($flag->name, (string)$key);
            $result = $bucket < $flag->rolloutPercentage;
            $this->logger->info(sprintf(
                'Flag "%s" evaluated to %s via rolloutPercentage (%d%%). Bucket: %d, Key: %s.',
                $flag->name,
                $result ? 'TRUE' : 'FALSE',
                $flag->rolloutPercentage,
                $bucket,
                $key
            ));
            return $result;
        }

        // 5) Fallback
        $this->logger->info(sprintf('Flag "%s" using fallback default: %s.', $flag->name, $default ? 'TRUE' : 'FALSE'));
        return $default;
    }

    private function matchRule(Rule $rule, Context $context): bool
    {
        $attributeValue = $context->get($rule->attribute);

        return match ($rule->operator) {
            'eq' => $attributeValue === $rule->value,
            'neq' => $attributeValue !== $rule->value,
            'gt' => $attributeValue > $rule->value,
            'gte' => $attributeValue >= $rule->value,
            'lt' => $attributeValue < $rule->value,
            'lte' => $attributeValue <= $rule->value,
            'in' => is_array($rule->value) && in_array($attributeValue, $rule->value, true),
            'nin' => is_array($rule->value) && !in_array($attributeValue, $rule->value, true),
            'contains' => is_string($attributeValue) && is_string($rule->value) && str_contains($attributeValue, $rule->value),
            default => throw new \LogicException(sprintf('Unsupported operator "%s" encountered during evaluation.', $rule->operator)),
        };
    }

    private function resolveTargetingKey(Context $context, ?string $preferredKey = null): ?string
    {
        if ($preferredKey !== null) {
            $v = $context->get($preferredKey);
            if ($v !== null && $v !== '') {
                return (string)$v;
            }
        }

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
        $hash = hash('xxh3', $flagName . ':' . $key);

        return (int)(hexdec(substr($hash, 0, 8)) % 100);
    }
}
