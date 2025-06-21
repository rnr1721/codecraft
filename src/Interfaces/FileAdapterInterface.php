<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Interfaces;

/**
 * Extended interface for file adapters with full functionality
 */
interface FileAdapterInterface
{
    /**
     * Get adapter name
     *
     * @return string Adapter name (e.g., 'php', 'javascript', 'json')
     */
    public function getName(): string;

    /**
     * Get adapter version
     *
     * @return string Version string (e.g., '1.0.0')
     */
    public function getVersion(): string;

    /**
     * Get supported file extensions
     *
     * @return string[] Array of supported extensions without dots (e.g., ['php', 'phtml'])
     */
    public function getSupportedExtensions(): array;

    /**
     * Get adapter capabilities
     *
     * @return array<string, bool> Capabilities map (e.g., ['create_class' => true, 'supports_namespace' => true])
     */
    public function getCapabilities(): array;

    /**
     * Create new file content
     *
     * @param  string               $filePath Target file path
     * @param  array<string, mixed> $options  Creation options
     * @return string Generated content
     * @throws \InvalidArgumentException When options are invalid
     */
    public function create(string $filePath, array $options = []): string;

    /**
     * Edit existing file content
     *
     * @param  string                           $filePath      Path to existing file
     * @param  array<int, array<string, mixed>> $modifications Array of modifications to apply
     * @return string Modified content
     * @throws \InvalidArgumentException When file doesn't exist or modifications are invalid
     * @throws \RuntimeException When modification fails
     */
    public function edit(string $filePath, array $modifications): string;

    /**
     * Analyze file structure and content
     *
     * @param  string $filePath Path to file to analyze
     * @return array<string, mixed> Analysis results
     * @throws \InvalidArgumentException When file doesn't exist
     */
    public function analyze(string $filePath): array;

    /**
     * Validate file content
     *
     * @param  string $content File content to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(string $content): bool;

    /**
     * Get help information for this adapter
     *
     * @return array<string, mixed> Help information including examples, options, etc.
     */
    public function getHelp(): array;
}
