<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Laravel\Commands;

use Illuminate\Console\Command;
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;
use Illuminate\Support\Facades\Config;

/**
 * Artisan command for generating files using CodeCraft
 */
class CodeCraftGenerateCommand extends Command
{
    /**
     * The name and signature of the console command
     *
     * @var string
     */
    protected $signature = 'codecraft:generate 
                            {path : File path to generate (e.g., app/Models/User.php)}
                            {--force : Overwrite existing files}
                            {--dry-run : Show what would be generated without creating files}
                            {--help-extension= : Show help for specific file extension}
                            {--options=* : Additional options as key=value pairs}';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Generate code files using CodeCraft adapters (path-based)';

    /**
     * Execute the console command
     *
     * @param CodeCraftInterface $codeCraft
     * @return int
     */
    public function handle(CodeCraftInterface $codeCraft): int
    {
        if ($helpExtension = $this->option('help-extension')) {
            return $this->showExtensionHelp($codeCraft, $helpExtension);
        }

        $filePath = $this->argument('path');
        $additionalOptions = $this->option('options');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if (empty($extension)) {
            $this->error("File path must have an extension: {$filePath}");
            return 1;
        }

        if (!$codeCraft->supports($extension)) {
            $this->error("Unsupported file extension: {$extension}");
            $this->info("Supported extensions: " . implode(', ', $codeCraft->getSupportedExtensions()));
            $this->info("Use --help-extension=<ext> to see specific adapter help");
            return 1;
        }

        $options = $this->buildOptions($filePath, $additionalOptions);

        if (file_exists($filePath) && !$force && !$dryRun) {
            $this->error("File already exists: {$filePath}");
            $this->info("Use --force to overwrite existing files");
            return 1;
        }

        try {
            $this->info("Generating file: {$filePath}");

            if ($dryRun) {
                return $this->handleDryRun($codeCraft, $filePath, $options);
            }

            $success = $codeCraft->write($filePath, $options);

            if ($success) {
                $this->info("✔ File generated successfully: {$filePath}");
                $this->showGeneratedFileInfo($filePath, $options);
                return 0;
            } else {
                $this->error("✗ Failed to write file: {$filePath}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("✗ Error generating file: " . $e->getMessage());

            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Build options array from various sources
     *
     * @param string $filePath
     * @param array $additionalOptions
     * @return array
     */
    private function buildOptions(string $filePath, array $additionalOptions): array
    {
        $options = [];

        $options = $this->detectOptionsFromPath($filePath);

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $defaults = Config::get("codecraft.defaults.{$extension}", []);
        $options = array_merge($options, $defaults);

        foreach ($additionalOptions as $option) {
            if (str_contains($option, '=')) {
                [$key, $value] = explode('=', $option, 2);
                $options[trim($key)] = $this->parseValue(trim($value));
            }
        }

        return $options;
    }

    /**
     * Auto-detect options from file path patterns
     *
     * @param string $filePath
     * @return array
     */
    private function detectOptionsFromPath(string $filePath): array
    {
        $options = [];
        $filename = pathinfo($filePath, PATHINFO_FILENAME);

        $options['name'] = $filename;

        // Laravel-specific
        if (preg_match('#app/Models/([^/]+)\.php$#', $filePath, $matches)) {
            $options['namespace'] = 'App\\Models';
            $options['extends'] = 'Model';
            $options['name'] = $matches[1];
        }

        if (preg_match('#app/Http/Controllers/([^/]+)Controller\.php$#', $filePath, $matches)) {
            $options['namespace'] = 'App\\Http\\Controllers';
            $options['extends'] = 'Controller';
            $options['name'] = $matches[1] . 'Controller';
        }

        if (preg_match('#tests/([^/]+)Test\.php$#', $filePath, $matches)) {
            $options['namespace'] = 'Tests\\Feature';
            $options['extends'] = 'TestCase';
            $options['name'] = $matches[1] . 'Test';
        }

        if (preg_match('#tests/Unit/([^/]+)Test\.php$#', $filePath, $matches)) {
            $options['namespace'] = 'Tests\\Unit';
            $options['extends'] = 'TestCase';
            $options['name'] = $matches[1] . 'Test';
        }

        if (preg_match('#src/components/([^/]+)\.jsx$#', $filePath, $matches)) {
            $options['type'] = 'component';
            $options['name'] = $matches[1];
            $options['functional'] = true;
        }

        return $options;
    }

    /**
     * Handle dry run mode
     *
     * @param CodeCraftInterface $codeCraft
     * @param string $filePath
     * @param array $options
     * @return int
     */
    private function handleDryRun(CodeCraftInterface $codeCraft, string $filePath, array $options): int
    {
        try {
            $content = $codeCraft->create($filePath, $options);

            $this->info("Generated content preview:");
            $this->line("File: {$filePath}");
            $this->line(str_repeat('-', 50));
            $this->line($content);
            $this->line(str_repeat('-', 50));
            $this->info("Lines: " . substr_count($content, "\n"));
            $this->info("Characters: " . strlen($content));

            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Error during dry run: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Show extension help
     *
     * @param CodeCraftInterface $codeCraft
     * @param string $extension
     * @return int
     */
    private function showExtensionHelp(CodeCraftInterface $codeCraft, string $extension): int
    {
        try {
            $help = $codeCraft->getHelp($extension);

            $this->line("");
            $this->line("CodeCraft - .{$extension} files");
            $this->line(str_repeat("=", 50));
            $this->line("");

            $this->line("Description:");
            $this->line("   " . ($help['description'] ?? 'No description available'));
            $this->line("");

            if (!empty($help['examples'])) {
                $this->line("Examples:");
                $this->line("");
                foreach ($help['examples'] as $name => $example) {
                    $this->line("   " . ucfirst(str_replace('_', ' ', $name)) . ":");
                    $this->line("   " . ($example['description'] ?? ''));
                    $this->line("");
                    $this->line("   php artisan codecraft:generate {$example['path']} \\");
                    if (!empty($example['options'])) {
                        foreach ($example['options'] as $key => $value) {
                            $this->line("       --options=\"{$key}={$value}\" \\");
                        }
                    }
                    $this->line("");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("No help available for extension: {$extension}");
            $this->info("Available extensions: " . implode(', ', $codeCraft->getSupportedExtensions()));
            return 1;
        }
    }

    /**
     * Parse option value (convert string booleans, numbers, etc.)
     *
     * @param string $value
     * @return mixed
     */
    private function parseValue(string $value): mixed
    {
        // Handle JSON-like values
        if ((str_starts_with($value, '{') && str_ends_with($value, '}'))
            || (str_starts_with($value, '[') && str_ends_with($value, ']'))
        ) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Booleans
        if (in_array(strtolower($value), ['true', 'yes', '1', 'on'])) {
            return true;
        }

        if (in_array(strtolower($value), ['false', 'no', '0', 'off'])) {
            return false;
        }

        // Numbers
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        // Arrays (comma-separated)
        if (str_contains($value, ',')) {
            return array_map('trim', explode(',', $value));
        }

        return $value;
    }

    /**
     * Show information about the generated file
     *
     * @param string $filePath
     * @param array $options
     * @return void
     */
    private function showGeneratedFileInfo(string $filePath, array $options): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $size = filesize($filePath);
        $lines = count(file($filePath));
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $this->table(
            ['Property', 'Value'],
            [
                ['File Path', $filePath],
                ['Extension', $extension],
                ['File Size', $this->formatBytes($size)],
                ['Lines', $lines],
                ['Name', $options['name'] ?? 'unknown'],
                ['Namespace', $options['namespace'] ?? 'N/A'],
            ]
        );

        // next steps suggestions
        $this->info("\nNext steps:");
        if (str_ends_with($filePath, '.php') && isset($options['namespace'])) {
            $this->line("• Import the class: use {$options['namespace']}\\{$options['name']};");
        }
        if (str_contains($filePath, '/Models/')) {
            $this->line("• Run migrations if database changes are needed");
            $this->line("• Create a factory: php artisan make:factory {$options['name']}Factory");
        }
        if (str_contains($filePath, '/Controllers/')) {
            $this->line("• Add routes to routes/web.php or routes/api.php");
        }
    }

    /**
     * Format bytes for human reading
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
