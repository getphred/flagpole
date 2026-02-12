# FlagPole Milestones

This document outlines the roadmap for enhancing FlagPole with advanced targeting, persistence, and observability features.

## Table of Contents
- [Milestone 1: Advanced Rule Engine](#milestone-1-advanced-rule-engine)
- [Milestone 2: Pluggable Targeting Keys](#milestone-2-pluggable-targeting-keys)
- [Milestone 3: Persistent Repositories](#milestone-3-persistent-repositories)
- [Milestone 4: Observability & Logging](#milestone-4-observability--logging)
- [Milestone 5: Documentation & Polish](#milestone-5-documentation--polish)
- [Milestone 6: Refactoring & DRY](#milestone-6-refactoring--dry)
- [Milestone 7: Validation & Safety](#milestone-7-validation--safety)

## Milestone 1: Advanced Rule Engine
Move beyond simple allow-lists to support complex attribute-based targeting.

- [x] Define `Rule` DTO/Interface (`src/Rule.php`).
- [x] Add `rules` collection to `Flag` DTO.
- [x] Implement rule evaluation logic in `Evaluator` (supporting operators like `eq`, `gt`, `lt`, `in`, `contains`).
- [x] Update `Evaluator::evaluate` precedence: `allowList` > `enabled` > `rules` > `rolloutPercentage`.

## Milestone 2: Pluggable Targeting Keys
Allow explicit control over which context attribute is used for rollout hashing.

- [x] Add optional `targetingKey` property to `Flag`.
- [x] Refactor `Evaluator::resolveTargetingKey` to honor `Flag->targetingKey` if present.
- [x] Modernize hashing implementation in `Evaluator::computeBucket` for PHP 8.2+.

## Milestone 3: Persistent Repositories
Provide out-of-the-box support for non-volatile flag storage.

- [x] Implement `JsonFileRepository` in `src/Repository/JsonFileRepository.php`.
- [x] Ensure robust JSON parsing and mapping to `Flag` objects.

## Milestone 4: Observability & Logging
Provide insights into why flags are being enabled or disabled.

- [x] Integrate PSR-3 `LoggerInterface` into `FeatureManager` and `Evaluator`.
- [x] Implement detailed logging for evaluation outcomes (e.g., which rule or strategy matched).
- [x] (Optional) Create `EvaluationResult` DTO for programmatic access to evaluation reasons.

## Milestone 5: Documentation & Polish
- [x] Update `README.md` with examples for new features.
- [x] Add comprehensive tests for Rule Engine and JSON Repository.
- [x] Verify zero regression for existing simple flag usage.

## Milestone 6: Refactoring & DRY
Centralize logic and remove duplication to improve maintainability.

- [x] Extract flag hydration logic into a dedicated `FlagHydrator` class to be reused across repositories.
- [x] Refactor `InMemoryFlagRepository` to use the new hydration logic.
- [x] Refactor `JsonFileRepository` to use the new hydration logic.

## Milestone 7: Validation & Safety
Enhance the engine to be more robust and developer-friendly.

- [x] Add validation to the rule engine to handle or log unknown operators instead of failing silently.
- [x] Implement configuration validation to ensure `Flag` and `Rule` objects are correctly formed before evaluation.
- [x] Optimize `JsonFileRepository` to avoid unnecessary parsing or consider lazy-loading if the config grows large.
