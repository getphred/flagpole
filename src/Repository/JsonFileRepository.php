<?php

declare(strict_types=1);

namespace FlagPole\Repository;

use FlagPole\Flag;
use FlagPole\FlagHydrator;
use FlagPole\Rule;

/**
 * Loads flags from a JSON file.
 */
final class JsonFileRepository implements FlagRepositoryInterface
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $rawData = null;

    /** @var array<string, Flag> */
    private array $flags = [];

    private ?FlagHydrator $hydrator = null;

    public function __construct(
        private readonly string $filePath
    ) {
    }

    public function get(string $name): ?Flag
    {
        $this->ensureLoaded();

        if (isset($this->flags[$name])) {
            return $this->flags[$name];
        }

        if (isset($this->rawData[$name])) {
            $this->hydrator ??= new FlagHydrator();
            $this->flags[$name] = $this->hydrator->hydrate($name, $this->rawData[$name]);
            return $this->flags[$name];
        }

        return null;
    }

    /**
     * @return iterable<string, Flag>
     */
    public function all(): iterable
    {
        $this->ensureLoaded();

        if ($this->rawData !== null) {
            foreach (array_keys($this->rawData) as $name) {
                $this->get((string)$name);
            }
        }

        return $this->flags;
    }

    private function ensureLoaded(): void
    {
        if ($this->rawData !== null) {
            return;
        }

        if (!file_exists($this->filePath)) {
            $this->rawData = [];
            return;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            $this->rawData = [];
            return;
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->rawData = [];
            return;
        }

        if (!is_array($data)) {
            $this->rawData = [];
            return;
        }

        $this->rawData = $data;
    }
}
