<?php

declare(strict_types=1);

namespace FlagPole\Tests;

use FlagPole\Context;
use FlagPole\FeatureManager;
use FlagPole\Repository\InMemoryFlagRepository;
use PHPUnit\Framework\TestCase;

final class FeatureManagerTest extends TestCase
{
    public function testExplicitEnabledOverrides(): void
    {
        $repo = InMemoryFlagRepository::fromArray([
            'hard-on' => ['enabled' => true],
            'hard-off' => ['enabled' => false],
        ]);
        $fm = new FeatureManager($repo);

        $this->assertTrue($fm->isEnabled('hard-on'));
        $this->assertFalse($fm->isEnabled('hard-off', null, true));
    }

    public function testAllowListWins(): void
    {
        $repo = InMemoryFlagRepository::fromArray([
            'flag' => [
                'enabled' => false,
                'allowList' => ['user_1'],
            ],
        ]);
        $fm = new FeatureManager($repo);
        $ctx1 = Context::fromArray(['userId' => 'user_1']);
        $ctx2 = Context::fromArray(['userId' => 'user_2']);

        $this->assertTrue($fm->isEnabled('flag', $ctx1));
        $this->assertFalse($fm->isEnabled('flag', $ctx2));
    }

    public function testPercentageRolloutIsStable(): void
    {
        $repo = InMemoryFlagRepository::fromArray([
            'gradual' => [
                'rolloutPercentage' => 10,
            ],
        ]);
        $fm = new FeatureManager($repo);
        $ctx = Context::fromArray(['userId' => 'user_42']);

        $first = $fm->isEnabled('gradual', $ctx);
        $second = $fm->isEnabled('gradual', $ctx);

        $this->assertSame($first, $second, 'Rollout decision should be stable for same user');
    }

    public function testRolloutBoundaryPercentages(): void
    {
        $repo = InMemoryFlagRepository::fromArray([
            'zero' => [ 'rolloutPercentage' => 0 ],
            'hundred' => [ 'rolloutPercentage' => 100 ],
        ]);
        $fm = new FeatureManager($repo);
        $ctx = Context::fromArray(['userId' => 'any_user']);

        $this->assertFalse($fm->isEnabled('zero', $ctx), '0% rollout should always be false when key present');
        $this->assertTrue($fm->isEnabled('hundred', $ctx), '100% rollout should always be true when key present');
    }

    public function testRolloutEdgeCasesOneAndNinetyNinePercent(): void
    {
        $repo = InMemoryFlagRepository::fromArray([
            'one' => [ 'rolloutPercentage' => 1 ],
            'ninetyNine' => [ 'rolloutPercentage' => 99 ],
            'hundred' => [ 'rolloutPercentage' => 100 ],
        ]);
        $fm = new FeatureManager($repo);

        // Find a user that gets bucket < 1 (i.e., enabled at 1%)
        $foundOne = null;
        for ($i = 0; $i < 5000; $i++) {
            $ctx = Context::fromArray(['userId' => 'u'.$i]);
            if ($fm->isEnabled('one', $ctx)) { // enabled under 1%
                $foundOne = $ctx;
                break;
            }
        }
        $this->assertNotNull($foundOne, 'Should find a user enabled at 1% within a reasonable search space');

        // Find a user that is NOT enabled at 99% but is at 100%
        $foundFalseAt99 = null;
        for ($i = 0; $i < 5000; $i++) {
            $ctx = Context::fromArray(['userId' => 'v'.$i]);
            if (!$fm->isEnabled('ninetyNine', $ctx) && $fm->isEnabled('hundred', $ctx)) {
                $foundFalseAt99 = $ctx;
                break;
            }
        }
        $this->assertNotNull($foundFalseAt99, 'Should find a user disabled at 99% but enabled at 100%');
    }

    public function testPercentageRolloutWithMissingTargetingKeyFallsBackToDefault(): void
    {
        $repo = InMemoryFlagRepository::fromArray([
            'gradual' => [ 'rolloutPercentage' => 50 ],
        ]);
        $fm = new FeatureManager($repo);

        // No context provided
        $this->assertFalse($fm->isEnabled('gradual', null, false));
        $this->assertTrue($fm->isEnabled('gradual', null, true));

        // Empty context (no targeting attributes)
        $emptyCtx = Context::fromArray([]);
        $this->assertFalse($fm->isEnabled('gradual', $emptyCtx, false));
        $this->assertTrue($fm->isEnabled('gradual', $emptyCtx, true));
    }
}
