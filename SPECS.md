# FlagPole Specifications

This document describes the current technical specifications and architecture of the FlagPole feature flagging library.

## Core Concepts

### Flag
An immutable data object representing a feature flag and its evaluation strategies.
- **Properties**:
    - `name` (string): Unique identifier.
    - `enabled` (bool|null): Explicit override. If `true`/`false`, evaluation stops here.
    - `rolloutPercentage` (int|null): 0-100 value for gradual rollout.
    - `allowList` (list<string>): Keys that always receive `true`.
    - `rules` (list<Rule>): Complex attribute-based targeting rules.
    - `targetingKey` (string|null): Optional override for the attribute used in rollout/allowList.

### Context
A container for attributes (e.g., `userId`, `email`, `userGroup`) used during evaluation.
- **Targeting Keys**: By default, the evaluator looks for `key`, `userId`, `id`, or `email` (in that order), unless overridden by `Flag->targetingKey`.

### Evaluator
The engine that applies flag strategies against a context.
- **Precedence**:
    1. `allowList`: If the targeting key is in the list, return `true`.
    2. `enabled`: If non-null, return its boolean value.
    3. `rules`: If any rule matches the context attributes, return `true`.
    4. `rolloutPercentage`: Hash-based stable bucketing (0-99).
    5. `fallback`: Returns the user-provided default.

### FeatureManager
The main entry point for the consumer.
- **Method**: `isEnabled(string $flagName, ?Context $context = null, bool $default = false): bool`
- **Logging**: Supports PSR-3 logging of evaluation reasons.

## Technical Details

### Rollout Hashing
- Algorithm: `xxh3(flagName + ":" + targetingKey)`.
- Normalization: `hexdec(substr(hash, 0, 8)) % 100`.
- Bucketing: 0-99.

### Repositories
- `FlagRepositoryInterface`: Defines `get(string $name)` and `all()`.
- `InMemoryFlagRepository`: Provided for testing and simple setups.
- `JsonFileRepository`: Loads flags from a JSON file. Now supports lazy-loading hydration for better performance.

### FlagHydrator
A dedicated component responsible for transforming raw configuration arrays into validated `Flag` and `Rule` objects.
- **Validation**: Ensures all rules have required fields and valid operators (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `in`, `nin`, `contains`).
- **Reuse**: Used by both `InMemoryFlagRepository` and `JsonFileRepository` to ensure consistent flag creation.

## Constraints
- PHP 8.2+ required.
- No external dependencies (PSR interfaces excepted in future).
