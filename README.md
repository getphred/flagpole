# FlagPole

Feature flag handling for PHP. Simple, framework-agnostic, and lightweight.

![CI](https://github.com/getphred/flagpole/actions/workflows/ci.yml/badge.svg)
![Packagist](https://img.shields.io/packagist/v/getphred/flagpole.svg)

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
    ],
    'hard-off' => [ 'enabled' => false ],
    'hard-on'  => [ 'enabled' => true ],
]);

$flags = new FeatureManager($repo);

$context = Context::fromArray(['userId' => 'user_42']);

if ($flags->isEnabled('new-dashboard', $context, false)) {
    // show the new dashboard
} else {
    // keep the old dashboard
}
```

## Concepts

- Flag: has a `name` and optional strategies:
  - `enabled`: explicit boolean on/off overrides everything.
  - `rolloutPercentage`: 0-100 gradual rollout based on a stable hash of the flag name + user key.
  - `allowList`: list of user keys that always get the flag enabled.
- Context: attributes about the subject (e.g. `userId`, `email`) used for evaluation.
- Repository: source of truth for flags. Provided: `InMemoryFlagRepository`. You can implement your own.

## Targeting key

Evaluator looks for a stable key in the context in this order: `key`, `userId`, `id`, `email`.

## Rollout hashing and boundary behavior

- Stable bucketing uses `crc32(flagName:key)` normalized to an unsigned 32-bit integer, then mapped to buckets 0..99.
- This guarantees consistent behavior across 32-bit and 64-bit platforms.
- Boundary rules:
  - 0% rollout always evaluates to `false` when a targeting key is present.
  - 100% rollout always evaluates to `true` when a targeting key is present.
  - If no targeting key is present in the `Context`, percentage rollout falls back to the `default` you pass to `isEnabled()`.

## Precedence semantics

When evaluating a flag, the following precedence applies:
1. `allowList` — if the targeting key is in the allow-list, the flag is enabled.
2. `enabled` — explicit on/off overrides percentage rollout and defaults.
3. `rolloutPercentage` — uses stable bucketing over the targeting key.
4. Fallback — returns the provided default when none of the above apply.

## Framework integration

FlagPole is framework-agnostic. Wrap `FeatureManager` in your framework's container and bind a repository suitable for your environment.

## License

MIT