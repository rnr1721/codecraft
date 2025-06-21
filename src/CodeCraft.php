<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft;

use rnr1721\CodeCraft\Interfaces\AdapterRegistryInterface;
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;
use InvalidArgumentException;

/**
 * Simple CodeCraft - path-based file operations
 */
class CodeCraft implements CodeCraftInterface
{
    public function __construct(
        protected AdapterRegistryInterface $registry
    ) {
    }

    /**
     * Create file content from path and options
     *
     * @param string $filePath File path with extension (determines type)
     * @param array $options Generation options
     * @return string Generated content
     */
    public function create(string $filePath, array $options = []): string
    {
        $adapter = $this->registry->getAdapterByFile($filePath);
        return $adapter->create($filePath, $options);
    }

    /**
     * Create file and write to disk
     *
     * @param string $filePath File path
     * @param array $options Generation options
     * @return bool Success
     */
    public function write(string $filePath, array $options = []): bool
    {
        $content = $this->create($filePath, $options);
        return $this->writeFile($filePath, $content);
    }

    /**
     * Edit existing file
     *
     * @param string $filePath Path to existing file
     * @param array $modifications Modifications to apply
     * @return string Modified content
     */
    public function edit(string $filePath, array $modifications): string
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist: {$filePath}");
        }

        $adapter = $this->registry->getAdapterByFile($filePath);
        return $adapter->edit($filePath, $modifications);
    }

    /**
     * Edit file and save
     *
     * @param string $filePath Path to existing file
     * @param array $modifications Modifications to apply
     * @return bool Success
     */
    public function editFile(string $filePath, array $modifications): bool
    {
        $content = $this->edit($filePath, $modifications);
        return $this->writeFile($filePath, $content);
    }

    /**
     * Analyze file structure
     *
     * @param string $filePath Path to file
     * @return array Analysis data
     */
    public function analyze(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("File does not exist: {$filePath}");
        }

        $adapter = $this->registry->getAdapterByFile($filePath);
        return $adapter->analyze($filePath);
    }

    /**
     * Validate file syntax
     *
     * @param string $filePath Path to file
     * @return bool Is valid
     */
    public function validate(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $adapter = $this->registry->getAdapterByFile($filePath);
        $content = file_get_contents($filePath);
        return $adapter->validate($content);
    }

    /**
     * Check if extension is supported
     *
     * @param string $extension File extension
     * @return bool
     */
    public function supports(string $extension): bool
    {
        return $this->registry->supportsExtension($extension);
    }

    /**
     * Get supported extensions
     *
     * @return array
     */
    public function getSupportedExtensions(): array
    {
        return $this->registry->getSupportedExtensions();
    }

    /**
     * Get help for extension
     *
     * @param string $extension File extension
     * @return array
     */
    public function getHelp(string $extension): array
    {
        $adapter = $this->registry->getAdapterByExtension($extension);
        return $adapter->getHelp();
    }

    /**
     * Register adapter
     *
     * @param \rnr1721\CodeCraft\Interfaces\FileAdapterInterface $adapter
     * @return void
     */
    public function registerAdapter(\rnr1721\CodeCraft\Interfaces\FileAdapterInterface $adapter): void
    {
        $this->registry->register($adapter);
    }

    /**
     * Write content to file
     *
     * @param string $filePath
     * @param string $content
     * @return bool
     */
    private function writeFile(string $filePath, string $content): bool
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                return false;
            }
        }

        return file_put_contents($filePath, $content) !== false;
    }
}
