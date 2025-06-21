<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Interfaces;

/**
 * Simple CodeCraft interface - path-based file operations
 */
interface CodeCraftInterface
{
    /**
     * Create file content from path and options
     *
     * @param string $filePath File path with extension (determines type)
     * @param array $options Generation options
     * @return string Generated content
     */
    public function create(string $filePath, array $options = []): string;

    /**
     * Create file and write to disk
     *
     * @param string $filePath File path
     * @param array $options Generation options
     * @return bool Success
     */
    public function write(string $filePath, array $options = []): bool;

    /**
     * Edit existing file
     *
     * @param string $filePath Path to existing file
     * @param array $modifications Modifications to apply
     * @return string Modified content
     */
    public function edit(string $filePath, array $modifications): string;

    /**
     * Edit file and save
     *
     * @param string $filePath Path to existing file
     * @param array $modifications Modifications to apply
     * @return bool Success
     */
    public function editFile(string $filePath, array $modifications): bool;

    /**
     * Analyze file structure
     *
     * @param string $filePath Path to file
     * @return array Analysis data
     */
    public function analyze(string $filePath): array;

    /**
     * Validate file syntax
     *
     * @param string $filePath Path to file
     * @return bool Is valid
     */
    public function validate(string $filePath): bool;

    /**
     * Check if extension is supported
     *
     * @param string $extension File extension
     * @return bool
     */
    public function supports(string $extension): bool;

    /**
     * Get supported extensions
     *
     * @return array
     */
    public function getSupportedExtensions(): array;

    /**
     * Get help for extension
     *
     * @param string $extension File extension
     * @return array
     */
    public function getHelp(string $extension): array;

    /**
     * Register adapter
     *
     * @param FileAdapterInterface $adapter
     * @return void
     */
    public function registerAdapter(FileAdapterInterface $adapter): void;
}
