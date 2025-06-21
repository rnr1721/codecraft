<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Adapters;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * JSON file adapter for creating and editing JSON files
 */
class JsonAdapter implements FileAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['json', 'jsonc'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $filePath, array $options = []): string
    {
        $type = $options['type'] ?? 'object'; // object, array, config, package, schema

        switch ($type) {
            case 'config':
                return $this->createConfig($options);
            case 'package':
                return $this->createPackageJson($options);
            case 'schema':
                return $this->createJsonSchema($options);
            case 'array':
                return $this->createArray($options);
            case 'tsconfig':
                return $this->createTsConfig($options);
            case 'eslint':
                return $this->createEslintConfig($options);
            case 'object':
            default:
                return $this->createObject($options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function edit(string $filePath, array $modifications): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in file: {$filePath}");
        }

        foreach ($modifications as $modification) {
            $data = $this->applyModification($data, $modification);
        }

        return $this->formatJson($data, $options['pretty'] ?? true);
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'type' => 'json',
                'valid' => false,
                'error' => json_last_error_msg()
            ];
        }

        return [
            'type' => 'json',
            'valid' => true,
            'structure' => $this->analyzeStructure($data),
            'keys' => $this->extractKeys($data),
            'depth' => $this->calculateDepth($data),
            'size' => strlen($content),
            'detected_type' => $this->detectJsonType($data)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $content): bool
    {
        json_decode($content);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'create_config' => true,
            'create_package_json' => true,
            'create_schema' => true,
            'merge_objects' => true,
            'set_value' => true,
            'get_value' => true,
            'supports_comments' => false, // JSON doesn't support comments natively
            'supports_jsonc' => true,    // JSON with Comments
            'pretty_print' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getHelp(): array
    {
        return [
            'description' => 'JSON adapter for creating configs, package.json, schemas, and data files',
            'supported_types' => [
                'object' => 'Create basic JSON objects',
                'array' => 'Create JSON arrays',
                'config' => 'Create application configuration files',
                'package' => 'Create package.json for Node.js projects',
                'schema' => 'Create JSON Schema files',
                'tsconfig' => 'Create TypeScript configuration',
                'eslint' => 'Create ESLint configuration'
            ],
            'examples' => [
                'package' => [
                    'description' => 'Create package.json',
                    'code' => '$codecraft->create("package.json", [
    "type" => "package",
    "name" => "my-app",
    "version" => "1.0.0",
    "description" => "My awesome app",
    "dependencies" => [
        "react" => "^18.2.0",
        "express" => "^4.18.0"
    ],
    "scripts" => [
        "dev" => "vite",
        "build" => "vite build"
    ]
]);'
                ],
                'config' => [
                    'description' => 'Create app configuration',
                    'code' => '$codecraft->create("config.json", [
    "type" => "config",
    "app_name" => "MyApp",
    "environment" => "production",
    "settings" => [
        "api_url" => "https://api.example.com",
        "features" => ["auth", "notifications"]
    ]
]);'
                ],
                'editing' => [
                    'description' => 'Edit JSON with dot notation',
                    'code' => '$codecraft->edit("config.json", [
    ["type" => "set", "path" => "database.host", "value" => "localhost"],
    ["type" => "append", "path" => "features", "value" => "export"],
    ["type" => "merge", "data" => ["new_setting" => "value"]]
]);'
                ]
            ],
            'options' => [
                'type' => 'object, array, config, package, schema, tsconfig, eslint',
                'data' => 'JSON data for object type',
                'items' => 'Array items for array type',
                'pretty' => 'Pretty print JSON (boolean)'
            ],
            'package_options' => [
                'name' => 'Package name',
                'version' => 'Package version',
                'description' => 'Package description',
                'main' => 'Entry point file',
                'scripts' => 'NPM scripts object',
                'dependencies' => 'Production dependencies',
                'devDependencies' => 'Development dependencies',
                'author' => 'Author name',
                'license' => 'License type'
            ],
            'config_options' => [
                'app_name' => 'Application name',
                'environment' => 'Environment (development/production)',
                'db_host' => 'Database host',
                'db_port' => 'Database port',
                'cache_driver' => 'Cache driver',
                'settings' => 'Additional settings object'
            ],
            'modification_types' => [
                'set' => 'Set value by dot notation path',
                'unset' => 'Remove value by path',
                'merge' => 'Merge object with existing data',
                'append' => 'Append to array',
                'prepend' => 'Prepend to array'
            ]
        ];
    }

    /**
     * Create a basic JSON object
     */
    private function createObject(array $options): string
    {
        $data = $options['data'] ?? [];
        $schema = $options['schema'] ?? null;

        if ($schema) {
            $data = array_merge($this->createFromSchema($schema), $data);
        }

        return $this->formatJson($data, $options['pretty'] ?? true);
    }

    /**
     * Create a JSON array
     */
    private function createArray(array $options): string
    {
        $items = $options['items'] ?? [];
        $itemType = $options['item_type'] ?? 'mixed'; // string, number, object, mixed

        $data = [];

        foreach ($items as $item) {
            switch ($itemType) {
                case 'string':
                    $data[] = (string)$item;
                    break;
                case 'number':
                    $data[] = is_numeric($item) ? (float)$item : 0;
                    break;
                case 'object':
                    $data[] = is_array($item) ? $item : ['value' => $item];
                    break;
                default:
                    $data[] = $item;
            }
        }

        return $this->formatJson($data, $options['pretty'] ?? true);
    }

    /**
     * Create configuration JSON
     */
    private function createConfig(array $options): string
    {
        $appName = $options['app_name'] ?? 'MyApp';
        $environment = $options['environment'] ?? 'development';

        $config = [
            'name' => $appName,
            'version' => $options['version'] ?? '1.0.0',
            'environment' => $environment,
            'debug' => $environment === 'development',
            'database' => [
                'host' => $options['db_host'] ?? 'localhost',
                'port' => $options['db_port'] ?? 3306,
                'name' => $options['db_name'] ?? 'database',
                'username' => $options['db_user'] ?? 'user',
                'password' => $options['db_pass'] ?? ''
            ],
            'cache' => [
                'driver' => $options['cache_driver'] ?? 'file',
                'ttl' => $options['cache_ttl'] ?? 3600
            ],
            'logging' => [
                'level' => $environment === 'development' ? 'debug' : 'error',
                'file' => $options['log_file'] ?? 'app.log'
            ]
        ];

        // Merge with custom settings
        if (isset($options['settings'])) {
            $config = array_merge_recursive($config, $options['settings']);
        }

        return $this->formatJson($config, $options['pretty'] ?? true);
    }

    /**
     * Create package.json
     */
    private function createPackageJson(array $options): string
    {
        $packageName = $options['name'] ?? 'my-package';
        $version = $options['version'] ?? '1.0.0';

        $package = [
            'name' => $packageName,
            'version' => $version,
            'description' => $options['description'] ?? '',
            'main' => $options['main'] ?? 'index.js',
            'scripts' => $options['scripts'] ?? [
                'test' => 'echo "Error: no test specified" && exit 1'
            ],
            'keywords' => $options['keywords'] ?? [],
            'author' => $options['author'] ?? '',
            'license' => $options['license'] ?? 'MIT'
        ];

        // Add dependencies if provided
        if (isset($options['dependencies'])) {
            $package['dependencies'] = $options['dependencies'];
        }

        if (isset($options['devDependencies'])) {
            $package['devDependencies'] = $options['devDependencies'];
        }

        // Add repository info
        if (isset($options['repository'])) {
            $package['repository'] = $options['repository'];
        }

        return $this->formatJson($package, $options['pretty'] ?? true);
    }

    /**
     * Create JSON Schema
     */
    private function createJsonSchema(array $options): string
    {
        $title = $options['title'] ?? 'Schema';
        $type = $options['schema_type'] ?? 'object';

        $schema = [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            '$id' => $options['id'] ?? 'https://example.com/schema.json',
            'title' => $title,
            'type' => $type
        ];

        if ($type === 'object') {
            $schema['properties'] = $options['properties'] ?? [];
            $schema['required'] = $options['required'] ?? [];
            $schema['additionalProperties'] = $options['additionalProperties'] ?? false;
        } elseif ($type === 'array') {
            $schema['items'] = $options['items'] ?? ['type' => 'string'];
        }

        if (isset($options['description'])) {
            $schema['description'] = $options['description'];
        }

        return $this->formatJson($schema, $options['pretty'] ?? true);
    }

    /**
     * Create TypeScript config
     */
    private function createTsConfig(array $options): string
    {
        $config = [
            'compilerOptions' => [
                'target' => $options['target'] ?? 'ES2020',
                'module' => $options['module'] ?? 'commonjs',
                'lib' => $options['lib'] ?? ['ES2020'],
                'outDir' => $options['outDir'] ?? './dist',
                'rootDir' => $options['rootDir'] ?? './src',
                'strict' => $options['strict'] ?? true,
                'esModuleInterop' => $options['esModuleInterop'] ?? true,
                'skipLibCheck' => $options['skipLibCheck'] ?? true,
                'forceConsistentCasingInFileNames' => true,
                'declaration' => $options['declaration'] ?? false,
                'sourceMap' => $options['sourceMap'] ?? true
            ],
            'include' => $options['include'] ?? ['src/**/*'],
            'exclude' => $options['exclude'] ?? ['node_modules', 'dist']
        ];

        return $this->formatJson($config, $options['pretty'] ?? true);
    }

    /**
     * Create ESLint config
     */
    private function createEslintConfig(array $options): string
    {
        $config = [
            'env' => [
                'browser' => $options['browser'] ?? true,
                'node' => $options['node'] ?? false,
                'es2021' => true
            ],
            'extends' => $options['extends'] ?? [
                'eslint:recommended'
            ],
            'parserOptions' => [
                'ecmaVersion' => $options['ecmaVersion'] ?? 12,
                'sourceType' => $options['sourceType'] ?? 'module'
            ],
            'rules' => $options['rules'] ?? [
                'indent' => ['error', 2],
                'linebreak-style' => ['error', 'unix'],
                'quotes' => ['error', 'single'],
                'semi' => ['error', 'always']
            ]
        ];

        if (isset($options['parser'])) {
            $config['parser'] = $options['parser'];
        }

        return $this->formatJson($config, $options['pretty'] ?? true);
    }

    /**
     * Apply modification to JSON data
     */
    private function applyModification(array $data, array $modification): array
    {
        $type = $modification['type'] ?? null;

        switch ($type) {
            case 'set':
                return $this->setValue($data, $modification['path'], $modification['value']);
            case 'unset':
                return $this->unsetValue($data, $modification['path']);
            case 'merge':
                return array_merge_recursive($data, $modification['data']);
            case 'append':
                return $this->appendToArray($data, $modification['path'], $modification['value']);
            case 'prepend':
                return $this->prependToArray($data, $modification['path'], $modification['value']);
            default:
                throw new \InvalidArgumentException("Unknown modification type: {$type}");
        }
    }

    /**
     * Set value by path (dot notation)
     */
    private function setValue(array $data, string $path, $value): array
    {
        $keys = explode('.', $path);
        $current = &$data;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current = $value;
        return $data;
    }

    /**
     * Unset value by path
     */
    private function unsetValue(array $data, string $path): array
    {
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $current = &$data;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                return $data; // Path doesn't exist
            }
            $current = &$current[$key];
        }

        unset($current[$lastKey]);
        return $data;
    }

    /**
     * Append to array by path
     */
    private function appendToArray(array $data, string $path, $value): array
    {
        $keys = explode('.', $path);
        $current = &$data;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        if (!is_array($current)) {
            $current = [$current];
        }

        $current[] = $value;
        return $data;
    }

    /**
     * Prepend to array by path
     */
    private function prependToArray(array $data, string $path, $value): array
    {
        $keys = explode('.', $path);
        $current = &$data;

        foreach ($keys as $key) {
            if (!isset($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        if (!is_array($current)) {
            $current = [$current];
        }

        array_unshift($current, $value);
        return $data;
    }

    /**
     * Format JSON with pretty printing
     */
    private function formatJson(array $data, bool $pretty = true): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($data, $flags);
    }

    /**
     * Analyze JSON structure
     */
    private function analyzeStructure($data): array
    {
        if (is_array($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            return [
                'type' => $isAssoc ? 'object' : 'array',
                'count' => count($data),
                'keys' => $isAssoc ? array_keys($data) : null
            ];
        }

        return [
            'type' => gettype($data),
            'value' => $data
        ];
    }

    /**
     * Extract all keys from JSON data
     */
    private function extractKeys($data, string $prefix = ''): array
    {
        $keys = [];

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
                $keys[] = $fullKey;

                if (is_array($value)) {
                    $keys = array_merge($keys, $this->extractKeys($value, $fullKey));
                }
            }
        }

        return $keys;
    }

    /**
     * Calculate maximum depth of JSON structure
     */
    private function calculateDepth($data): int
    {
        if (!is_array($data)) {
            return 0;
        }

        $maxDepth = 0;
        foreach ($data as $value) {
            if (is_array($value)) {
                $depth = 1 + $this->calculateDepth($value);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    /**
     * Detect JSON type based on structure
     */
    private function detectJsonType($data): string
    {
        if (!is_array($data)) {
            return 'primitive';
        }

        // Check for package.json
        if (isset($data['name'], $data['version'])) {
            return 'package';
        }

        // Check for JSON Schema
        if (isset($data['$schema'])) {
            return 'schema';
        }

        // Check for TypeScript config
        if (isset($data['compilerOptions'])) {
            return 'tsconfig';
        }

        // Check for ESLint config
        if (isset($data['rules']) || isset($data['extends'])) {
            return 'eslint';
        }

        // Check if it's a plain array
        if (array_keys($data) === range(0, count($data) - 1)) {
            return 'array';
        }

        return 'object';
    }

    /**
     * Create data from JSON Schema (basic implementation)
     */
    private function createFromSchema(array $schema): array
    {
        $data = [];

        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $key => $property) {
                $type = $property['type'] ?? 'string';

                switch ($type) {
                    case 'string':
                        $data[$key] = $property['default'] ?? '';
                        break;
                    case 'number':
                    case 'integer':
                        $data[$key] = $property['default'] ?? 0;
                        break;
                    case 'boolean':
                        $data[$key] = $property['default'] ?? false;
                        break;
                    case 'array':
                        $data[$key] = $property['default'] ?? [];
                        break;
                    case 'object':
                        $data[$key] = $property['default'] ?? [];
                        break;
                    default:
                        $data[$key] = null;
                }
            }
        }

        return $data;
    }
}
