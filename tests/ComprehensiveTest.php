<?php

declare(strict_types=1);

namespace FlagPole\Tests;

use FlagPole\Context;
use FlagPole\Evaluator;
use FlagPole\Flag;
use FlagPole\FlagHydrator;
use FlagPole\Rule;
use PHPUnit\Framework\TestCase;

final class ComprehensiveTest extends TestCase
{
    public function testFlagValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('rolloutPercentage must be between 0 and 100');
        new Flag('invalid', rolloutPercentage: 101);
    }

    public function testFlagValidationNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('rolloutPercentage must be between 0 and 100');
        new Flag('invalid', rolloutPercentage: -1);
    }

    public function testHydratorMissingFields(): void
    {
        $hydrator = new FlagHydrator();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid rule definition for flag "test". Missing attribute, operator, or value.');
        $hydrator->hydrate('test', [
            'rules' => [
                ['attribute' => 'plan'] // missing operator and value
            ]
        ]);
    }

    public function testHydratorInvalidOperator(): void
    {
        $hydrator = new FlagHydrator();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator "invalid_op" for flag "test".');
        $hydrator->hydrate('test', [
            'rules' => [
                ['attribute' => 'plan', 'operator' => 'invalid_op', 'value' => 'pro']
            ]
        ]);
    }

    /**
     * @dataProvider operatorProvider
     */
    public function testAllOperators(string $operator, mixed $ruleValue, mixed $contextValue, bool $expected): void
    {
        $evaluator = new Evaluator();
        $flag = new Flag('test', rules: [
            new Rule('attr', $operator, $ruleValue)
        ]);
        $context = Context::fromArray(['attr' => $contextValue]);

        $this->assertSame($expected, $evaluator->evaluate($flag, $context));
    }

    public static function operatorProvider(): array
    {
        return [
            'eq match' => ['eq', 'val', 'val', true],
            'eq mismatch' => ['eq', 'val', 'other', false],
            'neq match' => ['neq', 'val', 'other', true],
            'neq mismatch' => ['neq', 'val', 'val', false],
            'gt match' => ['gt', 10, 11, true],
            'gt mismatch' => ['gt', 10, 10, false],
            'gte match eq' => ['gte', 10, 10, true],
            'gte match gt' => ['gte', 10, 11, true],
            'gte mismatch' => ['gte', 10, 9, false],
            'lt match' => ['lt', 10, 9, true],
            'lt mismatch' => ['lt', 10, 10, false],
            'lte match eq' => ['lte', 10, 10, true],
            'lte match lt' => ['lte', 10, 9, true],
            'lte mismatch' => ['lte', 10, 11, false],
            'in match' => ['in', ['a', 'b'], 'a', true],
            'in mismatch' => ['in', ['a', 'b'], 'c', false],
            'nin match' => ['nin', ['a', 'b'], 'c', true],
            'nin mismatch' => ['nin', ['a', 'b'], 'a', false],
            'contains match' => ['contains', 'foo', 'foobar', true],
            'contains mismatch' => ['contains', 'baz', 'foobar', false],
        ];
    }

    public function testRuntimeUnsupportedOperator(): void
    {
        $evaluator = new Evaluator();
        // We bypass the hydrator which has validation to test the evaluator safety
        $flag = new Flag('test', rules: [
            new Rule('attr', 'unsupported', 'val')
        ]);
        $context = Context::fromArray(['attr' => 'val']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported operator "unsupported" encountered during evaluation.');
        $evaluator->evaluate($flag, $context);
    }
}
