# FlagPole

Feature flag handling for PHP. Simple, framework-agnostic, and lightweight.

![CI](https://github.com/getphred/flagpole/actions/workflows/ci.yml/badge.svg)
![Packagist](https://img.shields.io/packagist/v/getphred/flagpole.svg)
[![Total Downloads](https://img.shields.io/packagist/dt/getphred/flagpole.svg?style=flat-square)](https://packagist.org/packages/getphred/pairity)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

## Installation

Install via Composer:

```
composer require phred/flagpole
```

## Quick start

```php
use FlagPole\FeatureManager;
use FlagPole\Context;
use FlagPole\Repository\InMemoryFlagRepository;

require __DIR__ . '/vendor/autoload.php';

$repo = InMemoryFlagRepository::fromArray([
    'new-dashboard' => [
        'enabled' => null,               // not a hard on/off
        'rolloutPercentage' => 25,       // 25% gradual rollout
        'allowList' => ['user_1'],       // always on for specific users
        'rules' => [                     // attribute-based rules
            ['attribute' => 'plan', 'operator' => 'eq', 'value' => 'pro'],
        ],
    ],
    'hard-off' => [ 'enabled' => false ],
    'hard-on'  => [ 'enabled' => true ],
]);

$flags = new FeatureManager($repo);

$context = Context::fromArray(['userId' => 'user_42', 'plan' => 'pro']);

if ($flags->isEnabled('new-dashboard', $context, false)) {
    // show the new dashboard
} else {
    // keep the old dashboard
}
```

## Concepts

- Flag: has a `name` and optional strategies:
  - `enabled`: explicit boolean on/off overrides everything.
  - `allowList`: list of user keys that always get the flag enabled.
  - `rules`: complex attribute targeting (e.g. `version > 2.0`, `plan == 'pro'`).
  - `rolloutPercentage`: 0-100 gradual rollout based on a stable hash.
- Context: attributes about the subject (e.g. `userId`, `email`) used for evaluation.
- Repository: source of truth for flags. Provided: `InMemoryFlagRepository`, `JsonFileRepository`.
- Hydration: `FlagHydrator` centralizes flag creation and provides validation for targeting rules.
- Observability: Optional PSR-3 logging of evaluation results and reasons.

## Targeting key

Evaluator looks for a stable key in the context in this order: `key`, `userId`, `id`, `email`.
You can also specify an explicit `targetingKey` per flag to use a specific attribute (e.g. `orgId`).

## Precedence semantics

When evaluating a flag, the following precedence applies:
1. `allowList` — if the targeting key is in the allow-list, the flag is enabled.
2. `enabled` — explicit on/off overrides everything below.
3. `rules` — attribute-based targeting rules.
4. `rolloutPercentage` — uses stable bucketing over the targeting key.
5. Fallback — returns the provided default when none of the above apply.

## Framework integration

FlagPole is framework-agnostic. Wrap `FeatureManager` in your framework's container and bind a repository suitable for your environment.

## License

MIT