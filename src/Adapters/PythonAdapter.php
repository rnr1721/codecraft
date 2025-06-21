<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Adapters;

use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * Python file adapter for creating and editing Python classes, functions, and modules
 */
class PythonAdapter implements FileAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedExtensions(): array
    {
        return ['py', 'pyi'];
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $filePath, array $options = []): string
    {
        $type = $options['type'] ?? 'module';

        switch ($type) {
            case 'class':
                return $this->createClass($options);
            case 'function':
                return $this->createFunction($options);
            case 'dataclass':
                return $this->createDataClass($options);
            case 'fastapi':
                return $this->createFastAPIApp($options);
            case 'django_model':
                return $this->createDjangoModel($options);
            case 'pytest':
                return $this->createPytestFile($options);
            case 'module':
            default:
                return $this->createModule($options);
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

        foreach ($modifications as $modification) {
            $content = $this->applyModification($content, $modification);
        }

        return $content;
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
        $lines = explode("\n", $content);

        $analysis = [
            'type' => 'python',
            'imports' => [],
            'classes' => [],
            'functions' => [],
            'variables' => [],
            'decorators' => []
        ];

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // Analyze imports
            if (preg_match('/^(import|from)\s+(.+)/', $line, $matches)) {
                $analysis['imports'][] = [
                    'line' => $lineNum + 1,
                    'type' => $matches[1],
                    'module' => trim($matches[2])
                ];
            }

            // Analyze class definitions
            if (preg_match('/^class\s+(\w+)(?:\(([^)]*)\))?:/', $line, $matches)) {
                $analysis['classes'][] = [
                    'line' => $lineNum + 1,
                    'name' => $matches[1],
                    'parents' => isset($matches[2]) ? array_map('trim', explode(',', $matches[2])) : []
                ];
            }

            // Analyze function definitions
            if (preg_match('/^def\s+(\w+)\s*\(([^)]*)\)(?:\s*->\s*([^:]+))?:/', $line, $matches)) {
                $analysis['functions'][] = [
                    'line' => $lineNum + 1,
                    'name' => $matches[1],
                    'params' => trim($matches[2]),
                    'return_type' => isset($matches[3]) ? trim($matches[3]) : null
                ];
            }

            // Analyze decorators
            if (preg_match('/^@(\w+)/', $line, $matches)) {
                $analysis['decorators'][] = [
                    'line' => $lineNum + 1,
                    'name' => $matches[1]
                ];
            }
        }

        return $analysis;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $content): bool
    {
        // Basic Python syntax validation
        try {
            // Check for basic Python syntax issues
            $lines = explode("\n", $content);
            $indentStack = [0];

            foreach ($lines as $lineNum => $line) {
                if (trim($line) === '' || substr(trim($line), 0, 1) === '#') {
                    continue;
                }

                $indent = strlen($line) - strlen(ltrim($line));

                // Check indentation consistency
                if ($indent % 4 !== 0) {
                    return false; // Python typically uses 4-space indentation
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCapabilities(): array
    {
        return [
            'create_class' => true,
            'create_function' => true,
            'create_dataclass' => true,
            'create_fastapi' => true,
            'create_django_model' => true,
            'create_pytest' => true,
            'add_method' => true,
            'add_function' => true,
            'add_import' => true,
            'supports_type_hints' => true,
            'supports_decorators' => true,
            'supports_async' => true
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'python';
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
            'description' => 'Python adapter for creating classes, functions, FastAPI apps, Django models, and more',
            'supported_types' => [
                'module' => 'Create Python modules with imports and functions',
                'class' => 'Create Python classes with methods and inheritance',
                'function' => 'Create standalone functions',
                'dataclass' => 'Create Python dataclasses',
                'fastapi' => 'Create FastAPI applications',
                'django_model' => 'Create Django model classes',
                'pytest' => 'Create pytest test files'
            ],
            'examples' => [
                'basic_class' => [
                    'description' => 'Create a Python class',
                    'code' => '$codecraft->create("user.py", [
    "type" => "class",
    "name" => "User",
    "docstring" => "User class for managing user data",
    "parents" => ["BaseModel"],
    "methods" => [
        [
            "name" => "__init__",
            "params" => ["self", "name: str", "email: str"],
            "body" => "self.name = name\nself.email = email"
        ],
        [
            "name" => "get_info",
            "params" => ["self"],
            "return_type" => "dict",
            "body" => "return {\"name\": self.name, \"email\": self.email}"
        ]
    ]
]);'
                ],
                'fastapi_app' => [
                    'description' => 'Create FastAPI application',
                    'code' => '$codecraft->create("main.py", [
    "type" => "fastapi",
    "title" => "My API",
    "endpoints" => [
        [
            "path" => "/users",
            "method" => "GET",
            "function" => "get_users",
            "return_type" => "List[User]"
        ],
        [
            "path" => "/users",
            "method" => "POST", 
            "function" => "create_user",
            "params" => ["user: User"],
            "return_type" => "User"
        ]
    ]
]);'
                ],
                'dataclass' => [
                    'description' => 'Create Python dataclass',
                    'code' => '$codecraft->create("models.py", [
    "type" => "dataclass",
    "name" => "Product",
    "fields" => [
        ["name" => "id", "type" => "int"],
        ["name" => "name", "type" => "str"],
        ["name" => "price", "type" => "float", "default" => "0.0"]
    ]
]);'
                ]
            ],
            'options' => [
                'type' => 'module, class, function, dataclass, fastapi, django_model, pytest',
                'name' => 'Class/function name',
                'docstring' => 'Documentation string',
                'imports' => 'List of import statements',
                'decorators' => 'List of decorators',
                'async' => 'Make function async (boolean)'
            ],
            'class_options' => [
                'name' => 'Class name',
                'parents' => 'List of parent classes',
                'docstring' => 'Class documentation',
                'methods' => 'List of class methods',
                'class_variables' => 'Class-level variables'
            ],
            'method_options' => [
                'name' => 'Method name',
                'params' => 'List of parameters with type hints',
                'return_type' => 'Return type annotation',
                'body' => 'Method body code',
                'decorators' => 'Method decorators',
                'async' => 'Make method async'
            ],
            'fastapi_options' => [
                'title' => 'API title',
                'description' => 'API description',
                'version' => 'API version',
                'endpoints' => 'List of API endpoints'
            ],
            'modification_types' => [
                'add_import' => 'Add import statement',
                'add_function' => 'Add function to module',
                'add_method' => 'Add method to class',
                'add_decorator' => 'Add decorator to function/class'
            ]
        ];
    }

    /**
     * Create Python class
     */
    private function createClass(array $options): string
    {
        $name = $options['name'];
        $parents = $options['parents'] ?? [];
        $docstring = $options['docstring'] ?? null;
        $methods = $options['methods'] ?? [];
        $classVars = $options['class_variables'] ?? [];
        $imports = $options['imports'] ?? [];

        $code = '';

        // Add imports
        if (!empty($imports)) {
            foreach ($imports as $import) {
                $code .= $import . "\n";
            }
            $code .= "\n";
        }

        // Class definition
        $inheritance = !empty($parents) ? '(' . implode(', ', $parents) . ')' : '';
        $code .= "class {$name}{$inheritance}:\n";

        // Docstring
        if ($docstring) {
            $code .= "    \"\"\"{$docstring}\"\"\"\n\n";
        }

        // Class variables
        foreach ($classVars as $var) {
            $code .= "    {$var['name']}: {$var['type']} = {$var['value']}\n";
        }

        if (!empty($classVars)) {
            $code .= "\n";
        }

        // Methods
        if (!empty($methods)) {
            foreach ($methods as $method) {
                $code .= $this->generateMethod($method, '    ');
                $code .= "\n";
            }
        } else {
            $code .= "    pass\n";
        }

        return $code;
    }

    /**
     * Create Python function
     */
    private function createFunction(array $options): string
    {
        $imports = $options['imports'] ?? [];
        $functions = $options['functions'] ?? [$options]; // Support single function or multiple

        $code = '';

        // Add imports
        if (!empty($imports)) {
            foreach ($imports as $import) {
                $code .= $import . "\n";
            }
            $code .= "\n";
        }

        // Functions
        foreach ($functions as $func) {
            $code .= $this->generateFunction($func);
            $code .= "\n\n";
        }

        return rtrim($code);
    }

    /**
     * Create Python dataclass
     */
    private function createDataClass(array $options): string
    {
        $name = $options['name'];
        $fields = $options['fields'] ?? [];
        $docstring = $options['docstring'] ?? null;
        $methods = $options['methods'] ?? [];

        $code = "from dataclasses import dataclass\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $import . "\n";
            }
        }

        $code .= "\n\n@dataclass\n";
        $code .= "class {$name}:\n";

        if ($docstring) {
            $code .= "    \"\"\"{$docstring}\"\"\"\n\n";
        }

        // Fields
        foreach ($fields as $field) {
            $fieldDef = "    {$field['name']}: {$field['type']}";
            if (isset($field['default'])) {
                $fieldDef .= " = {$field['default']}";
            }
            $code .= $fieldDef . "\n";
        }

        // Additional methods
        if (!empty($methods)) {
            $code .= "\n";
            foreach ($methods as $method) {
                $code .= $this->generateMethod($method, '    ');
                $code .= "\n";
            }
        }

        return $code;
    }

    /**
     * Create FastAPI application
     */
    private function createFastAPIApp(array $options): string
    {
        $title = $options['title'] ?? 'FastAPI App';
        $description = $options['description'] ?? '';
        $version = $options['version'] ?? '1.0.0';
        $endpoints = $options['endpoints'] ?? [];

        $code = "from fastapi import FastAPI\n";
        $code .= "from typing import List, Optional\n\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $import . "\n";
            }
            $code .= "\n";
        }

        $code .= "app = FastAPI(\n";
        $code .= "    title=\"{$title}\",\n";
        if ($description) {
            $code .= "    description=\"{$description}\",\n";
        }
        $code .= "    version=\"{$version}\"\n";
        $code .= ")\n\n";

        // Endpoints
        foreach ($endpoints as $endpoint) {
            $method = strtolower($endpoint['method']);
            $path = $endpoint['path'];
            $funcName = $endpoint['function'];
            $params = $endpoint['params'] ?? [];
            $returnType = $endpoint['return_type'] ?? '';

            $code .= "@app.{$method}(\"{$path}\")\n";

            $paramStr = implode(', ', $params);
            $returnTypeStr = $returnType ? " -> {$returnType}" : '';

            $code .= "def {$funcName}({$paramStr}){$returnTypeStr}:\n";

            if (isset($endpoint['body'])) {
                $code .= "    " . str_replace("\n", "\n    ", $endpoint['body']) . "\n";
            } else {
                $code .= "    pass\n";
            }
            $code .= "\n";
        }

        return $code;
    }

    /**
     * Create Django model
     */
    private function createDjangoModel(array $options): string
    {
        $name = $options['name'];
        $fields = $options['fields'] ?? [];
        $meta = $options['meta'] ?? [];

        $code = "from django.db import models\n\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $import . "\n";
            }
            $code .= "\n";
        }

        $code .= "class {$name}(models.Model):\n";

        // Fields
        foreach ($fields as $field) {
            $fieldType = $field['type'] ?? 'CharField';
            $options = $field['options'] ?? [];

            $optionsStr = '';
            if (!empty($options)) {
                $optionPairs = [];
                foreach ($options as $key => $value) {
                    if (is_numeric($key)) {
                        // Handle cases like ['User', 'on_delete=models.CASCADE']
                        $optionPairs[] = $value;
                    } else {
                        // Handle cases like ['max_length' => '200']
                        $optionPairs[] = "{$key}={$value}";
                    }
                }
                $optionsStr = implode(', ', $optionPairs);
            }

            $code .= "    {$field['name']} = models.{$fieldType}({$optionsStr})\n";
        }

        // Meta class
        if (!empty($meta)) {
            $code .= "\n    class Meta:\n";
            foreach ($meta as $key => $value) {
                $code .= "        {$key} = {$value}\n";
            }
        }

        // Methods
        if (isset($options['methods'])) {
            $code .= "\n";
            foreach ($options['methods'] as $method) {
                $code .= $this->generateMethod($method, '    ');
                $code .= "\n";
            }
        }

        return $code;
    }

    /**
     * Create pytest test file
     */
    private function createPytestFile(array $options): string
    {
        $testClass = $options['test_class'] ?? null;
        $tests = $options['tests'] ?? [];
        $fixtures = $options['fixtures'] ?? [];

        $code = "import pytest\n";

        if (!empty($options['imports'])) {
            foreach ($options['imports'] as $import) {
                $code .= $import . "\n";
            }
        }

        $code .= "\n";

        // Fixtures
        foreach ($fixtures as $fixture) {
            $code .= "@pytest.fixture\n";
            $code .= "def {$fixture['name']}():\n";
            if (isset($fixture['body'])) {
                $code .= "    " . str_replace("\n", "\n    ", $fixture['body']) . "\n";
            } else {
                $code .= "    pass\n";
            }
            $code .= "\n";
        }

        // Test class or functions
        if ($testClass) {
            $code .= "class {$testClass}:\n";
            foreach ($tests as $test) {
                $code .= $this->generateMethod($test, '    ');
                $code .= "\n";
            }
        } else {
            foreach ($tests as $test) {
                $code .= $this->generateFunction($test);
                $code .= "\n";
            }
        }

        return $code;
    }

    /**
     * Create module
     */
    private function createModule(array $options): string
    {
        $docstring = $options['docstring'] ?? null;
        $imports = $options['imports'] ?? [];
        $constants = $options['constants'] ?? [];
        $functions = $options['functions'] ?? [];
        $classes = $options['classes'] ?? [];

        $code = '';

        // Module docstring
        if ($docstring) {
            $code .= "\"\"\"{$docstring}\"\"\"\n\n";
        }

        // Imports
        if (!empty($imports)) {
            foreach ($imports as $import) {
                $code .= $import . "\n";
            }
            $code .= "\n";
        }

        // Constants
        foreach ($constants as $constant) {
            $code .= "{$constant['name']} = {$constant['value']}\n";
        }

        if (!empty($constants)) {
            $code .= "\n";
        }

        // Functions
        foreach ($functions as $func) {
            $code .= $this->generateFunction($func);
            $code .= "\n\n";
        }

        // Classes
        foreach ($classes as $class) {
            $code .= $this->createClass($class);
            $code .= "\n\n";
        }

        return trim($code);
    }

    /**
     * Generate method code
     */
    private function generateMethod(array $method, string $indent = ''): string
    {
        $name = $method['name'];
        $params = $method['params'] ?? ['self'];
        $returnType = $method['return_type'] ?? null;
        $body = $method['body'] ?? 'pass';
        $decorators = $method['decorators'] ?? [];
        $docstring = $method['docstring'] ?? null;
        $async = $method['async'] ?? false;

        $code = '';

        // Decorators
        foreach ($decorators as $decorator) {
            $code .= "{$indent}@{$decorator}\n";
        }

        // Method signature
        $asyncKeyword = $async ? 'async ' : '';
        $paramStr = is_array($params) ? implode(', ', $params) : $params;
        $returnTypeStr = $returnType ? " -> {$returnType}" : '';

        $code .= "{$indent}{$asyncKeyword}def {$name}({$paramStr}){$returnTypeStr}:\n";

        // Docstring
        if ($docstring) {
            $code .= "{$indent}    \"\"\"{$docstring}\"\"\"\n";
        }

        // Body
        if (isset($method['body'])) {
            $code .= $this->addMethodBody($method['body'], $indent . '    ');
        }

        return $code;
    }

    /**
     * Generate function code
     */
    private function generateFunction(array $func): string
    {
        return $this->generateMethod($func, '');
    }

    /**
     * Add method body with proper indentation and newline handling
     */
    private function addMethodBody(string $body, string $indent): string
    {
        $body = str_replace('\\n', "\n", $body); // Convert \n to actual newlines
        $bodyLines = explode("\n", $body);
        $code = '';

        foreach ($bodyLines as $line) {
            if (trim($line) !== '') {
                $code .= "{$indent}{$line}\n";
            }
        }

        return $code;
    }

    /**
     * Apply modification to content
     */
    private function applyModification(string $content, array $modification): string
    {
        $type = $modification['type'] ?? null;

        switch ($type) {
            case 'add_import':
                return $this->addImport($content, $modification['import']);
            case 'add_function':
                return $this->addFunction($content, $modification['function']);
            case 'add_method':
                return $this->addMethodToClass($content, $modification);
            default:
                throw new \InvalidArgumentException("Unknown modification type: {$type}");
        }
    }

    /**
     * Add import to file
     */
    private function addImport(string $content, string $import): string
    {
        $lines = explode("\n", $content);
        $insertAt = 0;

        // Find where to insert import (after existing imports)
        foreach ($lines as $index => $line) {
            if (preg_match('/^(import|from)\s+/', trim($line))) {
                $insertAt = $index + 1;
            } elseif (trim($line) === '') {
                continue;
            } else {
                break;
            }
        }

        array_splice($lines, $insertAt, 0, [$import]);
        return implode("\n", $lines);
    }

    /**
     * Add function to module
     */
    private function addFunction(string $content, array $function): string
    {
        $funcCode = $this->generateFunction($function);
        return $content . "\n\n" . $funcCode;
    }

    /**
     * Add method to class
     */
    private function addMethodToClass(string $content, array $modification): string
    {
        $className = $modification['class_name'];
        $method = $modification['method'];

        $lines = explode("\n", $content);
        $classFound = false;
        $insertAt = -1;
        $currentIndent = 0;

        foreach ($lines as $index => $line) {
            if (preg_match("/^class\s+{$className}/", trim($line))) {
                $classFound = true;
                $currentIndent = strlen($line) - strlen(ltrim($line));
                continue;
            }

            if ($classFound) {
                $lineIndent = strlen($line) - strlen(ltrim($line));

                // If we're back to class level or less, this is where we insert
                if (trim($line) !== '' && $lineIndent <= $currentIndent) {
                    $insertAt = $index;
                    break;
                }
            }
        }

        if (!$classFound) {
            throw new \RuntimeException("Class '{$className}' not found");
        }

        if ($insertAt === -1) {
            $insertAt = count($lines);
        }

        $methodCode = $this->generateMethod($method, str_repeat(' ', $currentIndent + 4));
        $methodLines = explode("\n", $methodCode);

        array_splice($lines, $insertAt, 0, array_merge([''], $methodLines));
        return implode("\n", $lines);
    }
}
