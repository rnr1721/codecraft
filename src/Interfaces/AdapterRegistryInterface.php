<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Interfaces;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * Interface for adapter registry implementations
 */
interface AdapterRegistryInterface
{
    /**
     * Register a new adapter
     *
     * @param  FileAdapterInterface $adapter
     * @return void
     */
    public function register(FileAdapterInterface $adapter): void;

    /**
     * Check if adapter exists by name
     *
     * @param  string $name
     * @return boolean
     */
    public function hasAdapter(string $name): bool;

    /**
     * Get adapter by name
     *
     * @param  string $name
     * @return FileAdapterInterface
     * @throws \InvalidArgumentException When adapter not found
     */
    public function getAdapter(string $name): FileAdapterInterface;

    /**
     * Get adapter by file extension
     *
     * @param  string $extension
     * @return FileAdapterInterface
     * @throws \InvalidArgumentException When no adapter found for extension
     */
    public function getAdapterByExtension(string $extension): FileAdapterInterface;

    /**
     * Get adapter by file path
     *
     * @param  string $filePath
     * @return FileAdapterInterface
     * @throws \InvalidArgumentException When no adapter found for file
     */
    public function getAdapterByFile(string $filePath): FileAdapterInterface;

    /**
     * Check if extension is supported
     *
     * @param  string $extension
     * @return boolean
     */
    public function supportsExtension(string $extension): bool;

    /**
     * Get all registered adapters
     *
     * @return FileAdapterInterface[]
     */
    public function getAllAdapters(): array;

    /**
     * Get all supported extensions
     *
     * @return string[]
     */
    public function getSupportedExtensions(): array;

    /**
     * Unregister adapter by name
     *
     * @param  string $name
     * @return void
     */
    public function unregister(string $name): void;

    /**
     * Clear all adapters
     *
     * @return void
     */
    public function clear(): void;
}
