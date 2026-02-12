<?php

declare(strict_types=1);

namespace FlagPole;

/**
 * Hydrates Flag objects from raw configuration arrays.
 */
final class FlagHydrator
{
    private const VALID_OPERATORS = [
        'eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'nin', 'contains'
    ];

    /**
     * @param array<string, mixed> $data
     */
    public function hydrate(string $name, array $data): Flag
    {
        $rules = [];
        if (isset($data['rules']) && is_array($data['rules'])) {
            foreach ($data['rules'] as $ruleDef) {
                if (!is_array($ruleDef) || !isset($ruleDef['attribute'], $ruleDef['operator'], $ruleDef['value'])) {
                    throw new \InvalidArgumentException(sprintf('Invalid rule definition for flag "%s". Missing attribute, operator, or value.', $name));
                }

                if (!in_array($ruleDef['operator'], self::VALID_OPERATORS, true)) {
                    throw new \InvalidArgumentException(sprintf('Invalid operator "%s" for flag "%s".', $ruleDef['operator'], $name));
                }

                $rules[] = new Rule(
                    attribute: $ruleDef['attribute'],
                    operator: $ruleDef['operator'],
                    value: $ruleDef['value']
                );
            }
        }

        return new Flag(
            name: $name,
            enabled: $data['enabled'] ?? null,
            rolloutPercentage: $data['rolloutPercentage'] ?? null,
            allowList: $data['allowList'] ?? [],
            rules: $rules,
            targetingKey: $data['targetingKey'] ?? null
        );
    }
}
