<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;
use rnr1721\CodeCraft\Interfaces\AdapterRegistryInterface;
use InvalidArgumentException;

/**
 * Registry for managing file adapters
 */
class AdapterRegistry implements AdapterRegistryInterface
{
    /**
     * @var FileAdapterInterface[]
     */
    private array $adapters = [];

    /**
     * @var array<string, string> Extension to adapter name mapping
     */
    private array $extensionMap = [];

    /**
     * @inheritDoc
     */
    public function register(FileAdapterInterface $adapter): void
    {
        $name = $adapter->getName();
        $this->adapters[$name] = $adapter;

        foreach ($adapter->getSupportedExtensions() as $extension) {
            $this->extensionMap[$extension] = $name;
        }
    }

    /**
     * @inheritDoc
     */
    public function hasAdapter(string $name): bool
    {
        return isset($this->adapters[$name]);
    }

    /**
     * @inheritDoc
     */
    public function getAdapter(string $name): FileAdapterInterface
    {
        if (!isset($this->adapters[$name])) {
            throw new InvalidArgumentException("Adapter '{$name}' not found");
        }

        return $this->adapters[$name];
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByExtension(string $extension): FileAdapterInterface
    {
        $extension = $this->normalizeExtension($extension);

        if (!isset($this->extensionMap[$extension])) {
            throw new InvalidArgumentException("No adapter found for extension '{$extension}'");
        }

        return $this->adapters[$this->extensionMap[$extension]];
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByFile(string $filePath): FileAdapterInterface
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        return $this->getAdapterByExtension($extension);
    }

    /**
     * @inheritDoc
     */
    public function supportsExtension(string $extension): bool
    {
        $extension = $this->normalizeExtension($extension);
        return isset($this->extensionMap[$extension]);
    }

    /**
     * @inheritDoc
     */
    public function getAllAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedExtensions(): array
    {
        return array_keys($this->extensionMap);
    }

    /**
     * Normalize file extension (remove dot, convert to lowercase)
     *
     * @param  string $extension
     * @return string
     */
    private function normalizeExtension(string $extension): string
    {
        return strtolower(ltrim($extension, '.'));
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->adapters = [];
        $this->extensionMap = [];
    }

    /**
     * @inheritDoc
     */
    public function unregister(string $name): void
    {
        if (isset($this->adapters[$name])) {
            $adapter = $this->adapters[$name];
            unset($this->adapters[$name]);

            foreach ($adapter->getSupportedExtensions() as $extension) {
                unset($this->extensionMap[$extension]);
            }
        }
    }
}
