<?php

declare(strict_types=1);

namespace FlagPole\Tests;

use FlagPole\Repository\JsonFileRepository;
use PHPUnit\Framework\TestCase;

final class JsonFileRepositoryTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'flagpole_test_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testLoadsFlagsFromJson(): void
    {
        $json = json_encode([
            'test-flag' => [
                'enabled' => true,
                'rules' => [
                    ['attribute' => 'plan', 'operator' => 'eq', 'value' => 'pro']
                ]
            ]
        ]);
        file_put_contents($this->tempFile, $json);

        $repo = new JsonFileRepository($this->tempFile);
        $flag = $repo->get('test-flag');

        $this->assertNotNull($flag);
        $this->assertEquals('test-flag', $flag->name);
        $this->assertTrue($flag->enabled);
        $this->assertCount(1, $flag->rules);
        $this->assertEquals('plan', $flag->rules[0]->attribute);
    }

    public function testHandlesMissingFile(): void
    {
        $repo = new JsonFileRepository('/non/existent/file.json');
        $this->assertNull($repo->get('any'));
        $this->assertEmpty($repo->all());
    }

    public function testHandlesInvalidJson(): void
    {
        file_put_contents($this->tempFile, 'invalid json');
        $repo = new JsonFileRepository($this->tempFile);
        $this->assertNull($repo->get('any'));
    }

    public function testLazyHydration(): void
    {
        $json = json_encode([
            'flag-1' => ['enabled' => true],
            'flag-2' => ['enabled' => false],
        ]);
        file_put_contents($this->tempFile, $json);

        $repo = new JsonFileRepository($this->tempFile);

        // Access internal property via reflection to verify it's NOT hydrated yet
        $reflection = new \ReflectionClass($repo);
        $flagsProp = $reflection->getProperty('flags');
        $flagsProp->setAccessible(true);

        $this->assertEmpty($flagsProp->getValue($repo));

        // Hydrate flag-1
        $flag1 = $repo->get('flag-1');
        $this->assertNotNull($flag1);
        $this->assertTrue($flag1->enabled);

        $hydratedFlags = $flagsProp->getValue($repo);
        $this->assertArrayHasKey('flag-1', $hydratedFlags);
        $this->assertArrayNotHasKey('flag-2', $hydratedFlags);

        // Hydrate all
        $all = $repo->all();
        $this->assertCount(2, $all);

        $hydratedFlagsAfterAll = $flagsProp->getValue($repo);
        $this->assertArrayHasKey('flag-1', $hydratedFlagsAfterAll);
        $this->assertArrayHasKey('flag-2', $hydratedFlagsAfterAll);
    }
}
