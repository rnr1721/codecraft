# CodeCraft

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen.svg)]()
[![Latest Version](https://img.shields.io/packagist/v/rnr1721/codecraft.svg)](https://packagist.org/packages/rnr1721/codecraft)

**Universal code generation and manipulation library for modern development**

CodeCraft is an experimental, AI-friendly library that provides a unified API for creating, editing, and analyzing code across multiple programming languages. Think of it as the "Swiss Army knife" for code generation - one simple interface for all your code generation needs.

I wouldn't write this library if I didn't want to test these features in my cyclic AI agent that can execute code - https://github.com/rnr1721/depthnet . But I haven't found anything similar that fits. I don't plan to actively develop this library (at the moment), so if someone likes the idea, I'll be happy if this project is continued or a new one is created, I'll gladly use it :)

## Features

- **Simple Path-Based API** - Work with real file paths, not abstract types
- **AI-Friendly** - Built for AI agents and automated code generation
- **AST-Powered** - Uses Abstract Syntax Trees for precise PHP code manipulation
- **6 Languages Supported** - PHP, JavaScript, TypeScript, Python, CSS, JSON
- **Extensible** - Easy to add new language adapters
- **Validation** - Built-in syntax validation for all languages

## Supported Languages & Features

| Language | Classes | Functions | Components | Models | Tests | Config | AST | Validation |
|----------|---------|-----------|------------|--------|-------|--------|-----|------------|
| **PHP** | ✓ Classes, Interfaces, Traits | ✓ Methods, Functions | ✘ | ✘ | ✘ | ✓ | ✓ | ✓ php -l |
| **JavaScript** | ✓ ES6 Classes | ✓ Functions, Arrow Functions | ✓ React Components | ✘ | ✘ | ✘ | ✘ | ✓ Syntax |
| **TypeScript** | ✓ Classes, Generics | ✓ Functions, Async | ✓ React, Hooks | ✓ Api, Clients | ✘ | ✓ tsconfig | ✘ | ✓ Syntax |
| **Python** | ✓ Classes, DataClasses | ✓ Functions, Async | ✘ | ✓ Django, FastAPI | ✓ Pytest | ✘ | ✘ | ✓ Syntax |
| **CSS** | ✘ | ✘ | ✓ BEM Components | ✘ | ✘ | ✘ | ✘ | ✓ Syntax |
| **JSON** | ✘ | ✘ | ✘ | ✘ | ✘ | ✓ Configs, Package.json | ✘ | ✓ JSON |

## Quick Start

### Installation

```bash
composer require rnr1721/codecraft
```

### Basic Usage

```php
<?php

use rnr1721\CodeCraft\CodeCraft;
use rnr1721\CodeCraft\AdapterRegistry;
use rnr1721\CodeCraft\Adapters\PhpAdapter;

// Create registry and register adapters
$registry = new AdapterRegistry();
$registry->register(new PhpAdapter());
$codecraft = new CodeCraft($registry);

// Create a PHP class - type detected from file extension
$phpClass = $codecraft->create('app/Models/User.php', [
    'namespace' => 'App\\Models',
    'name' => 'User',
    'extends' => 'Model',
    'properties' => [
        [
            'name' => 'name',
            'type' => 'string',
            'visibility' => 'private'
        ]
    ],
    'methods' => [
        [
            'name' => 'getName',
            'visibility' => 'public',
            'returnType' => 'string',
            'body' => 'return $this->name;'
        ]
    ]
]);

// Write to file immediately
$codecraft->write('app/Models/User.php', [
    'namespace' => 'App\\Models',
    'name' => 'User',
    'extends' => 'Model'
]);

// Edit existing file
$codecraft->editFile('app/Models/User.php', [
    [
        'type' => 'add_method',
        'method' => [
            'name' => 'setName',
            'visibility' => 'public',
            'params' => [['name' => 'name', 'type' => 'string']],
            'body' => '$this->name = $name; return $this;'
        ]
    ]
]);
```

## Factory Pattern Usage

```php
use rnr1721\CodeCraft\CodeCraftFactory;
use rnr1721\CodeCraft\AdapterRegistry;
use rnr1721\CodeCraft\Adapters\PhpAdapter;

// Using factory for easier setup
$registry = new AdapterRegistry();
$registry->register(new PhpAdapter());

$factory = new CodeCraftFactory($registry);
$codecraft = $factory->create();

// Or create with no adapters
$emptyCodecraft = $factory->createEmpty();
```

## Language-Specific Examples

### PHP - Laravel Model (AST-Powered)

```php
$userModel = $codecraft->create('app/Models/User.php', [
    'namespace' => 'App\\Models',
    'name' => 'User',
    'extends' => 'Authenticatable',
    'implements' => ['MustVerifyEmail'],
    'properties' => [
        [
            'name' => 'fillable',
            'visibility' => 'protected',
            'type' => 'array',
            'default' => "['name', 'email', 'password']"
        ]
    ],
    'methods' => [
        [
            'name' => 'posts',
            'visibility' => 'public',
            'returnType' => 'HasMany',
            'body' => 'return $this->hasMany(Post::class);'
        ]
    ]
]);
```

### Python - FastAPI Application

```php
$fastApiApp = $codecraft->create('app/main.py', [
    'type' => 'fastapi',
    'title' => 'User Management API',
    'description' => 'RESTful API for user management',
    'version' => '1.0.0',
    'endpoints' => [
        [
            'path' => '/users',
            'method' => 'GET',
            'function' => 'get_users',
            'return_type' => 'List[User]',
            'body' => 'return user_service.get_all_users()'
        ],
        [
            'path' => '/users',
            'method' => 'POST',
            'function' => 'create_user',
            'params' => ['user: UserCreate'],
            'return_type' => 'User',
            'body' => 'return user_service.create_user(user)'
        ]
    ]
]);
```

### JavaScript - React Component

```php
$reactComponent = $codecraft->create('src/components/UserCard.jsx', [
    'type' => 'component',
    'name' => 'UserCard',
    'props' => ['user', 'onEdit', 'onDelete'],
    'hooks' => ['useState', 'useEffect'],
    'functional' => true
]);
```

### CSS - BEM Component

```php
$buttonStyles = $codecraft->create('src/styles/button.css', [
    'type' => 'component',
    'name' => 'button',
    'elements' => [
        'text' => [
            'color' => 'white',
            'font-weight' => 'bold'
        ],
        'icon' => [
            'margin-right' => '0.5rem'
        ]
    ],
    'states' => [
        'primary' => ['background-color' => '#007bff'],
        'disabled' => ['opacity' => '0.5']
    ]
]);
```

### JSON - Package Configuration

```php
$packageJson = $codecraft->create('package.json', [
    'type' => 'package',
    'name' => 'my-awesome-app',
    'version' => '1.0.0',
    'description' => 'An awesome web application',
    'dependencies' => [
        'react' => '^18.2.0',
        'express' => '^4.18.0'
    ],
    'scripts' => [
        'dev' => 'vite',
        'build' => 'vite build',
        'test' => 'jest'
    ]
]);
```

## Core API Methods

### Creating Files

```php
// Generate content only
$content = $codecraft->create('app/Models/User.php', $options);

// Generate and write to file
$success = $codecraft->write('app/Models/User.php', $options);
```

### Editing Files

```php
// Edit and return content
$newContent = $codecraft->edit('app/Models/User.php', $modifications);

// Edit and save file
$success = $codecraft->editFile('app/Models/User.php', $modifications);
```

### Analysis and Validation

```php
// Analyze file structure (AST-based for PHP)
$analysis = $codecraft->analyze('app/Models/User.php');

echo "Classes found: " . count($analysis['classes']);
echo "Methods found: " . count($analysis['classes'][0]['methods']);
echo "Namespace: " . $analysis['namespace'];

// Validate syntax
$isValid = $codecraft->validate('app/Models/User.php');
```

### Utility Methods

```php
// Check support
$canHandle = $codecraft->supports('php');
$extensions = $codecraft->getSupportedExtensions();

// Get help for specific file type
$help = $codecraft->getHelp('php');
```

## Advanced Usage

### Batch Modifications

```php
// Multiple modifications at once
$codecraft->editFile('app/Models/User.php', [
    ['type' => 'add_property', 'property' => ['name' => 'email', 'type' => 'string']],
    ['type' => 'add_method', 'method' => ['name' => 'getEmail', 'body' => 'return $this->email;']],
    ['type' => 'replace_method', 'method_name' => 'getName', 'method' => [...]]
]);
```

### JSON Manipulation

```php
// Edit JSON files with modifications
$codecraft->editFile('config.json', [
    ['type' => 'set', 'path' => 'database.host', 'value' => 'localhost'],
    ['type' => 'set', 'path' => 'app.name', 'value' => 'MyApp'],
    ['type' => 'append', 'path' => 'features', 'value' => 'notifications'],
    ['type' => 'merge', 'data' => ['new_setting' => 'value']]
]);
```

## AI Integration

CodeCraft is designed to be AI-friendly with simple, predictable behavior:

```php
// Simple path-based operations that AI can easily understand
$codecraft->write('app/Models/Post.php', [
    'namespace' => 'App\\Models',
    'name' => 'Post',
    'extends' => 'Model'
]);

// Get help for any file type
$help = $codecraft->getHelp('jsx');

// Check what's supported
$extensions = $codecraft->getSupportedExtensions();

// Validate before creating
if ($codecraft->supports('py')) {
    $codecraft->write('app/services/user_service.py', $options);
}
```

## Creating Custom Adapters

```php
use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

class RustAdapter implements FileAdapterInterface
{
    public function getName(): string
    {
        return 'rust';
    }
    
    public function getSupportedExtensions(): array
    {
        return ['rs'];
    }
    
    public function create(string $filePath, array $options = []): string
    {
        // Implementation for creating Rust files
    }
    
    public function edit(string $filePath, array $modifications): string
    {
        // Implementation for editing Rust files
    }
    
    public function analyze(string $filePath): array
    {
        // Implementation for analyzing Rust files
    }
    
    public function validate(string $content): bool
    {
        // Implementation for validating Rust syntax
    }
    
    public function getCapabilities(): array
    {
        return [
            'create_struct' => true,
            'create_trait' => true,
            'create_enum' => true
        ];
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function getHelp(): array
    {
        return [
            'description' => 'Rust adapter for structs, traits, and enums',
            'supported_types' => [
                'struct' => 'Create Rust structs',
                'trait' => 'Create Rust traits',
                'enum' => 'Create Rust enums'
            ],
            'examples' => [
                // ... comprehensive examples
            ],
            'options' => [
                // ... available options
            ]
        ];
    }
}

// Register your adapter
$codecraft->registerAdapter(new RustAdapter());
```

## Use Cases

### AI Code Generation
- AI agents and assistants
- Automated code generation
- Template-based development
- Code completion tools

### Development Tools
- Laravel Artisan commands
- Custom scaffolding tools
- Project generators
- Migration utilities

### Migration & Transformation
- Converting between frameworks
- API code generation from specs
- Database model generation
- Legacy code modernization

## Framework Integration

### Laravel Integration

```php
// In an Artisan command
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;

class MakeAdvancedModelCommand extends Command
{
    protected $signature = 'make:advanced-model {name}';
    
    public function handle(CodeCraftInterface $codecraft)
    {
        $name = $this->argument('name');
        $path = "app/Models/{$name}.php";
        
        $success = $codecraft->write($path, [
            'namespace' => 'App\\Models',
            'name' => $name,
            'extends' => 'Model',
            'properties' => [
                ['name' => 'fillable', 'visibility' => 'protected', 'type' => 'array']
            ]
        ]);
        
        if ($success) {
            $this->info("Model {$name} created successfully!");
        }
    }
}
```

### Dependency Injection

```php
// In any Laravel class (Controller, Service, etc.)
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;

class CodeGeneratorService
{
    public function __construct(
        private CodeCraftInterface $codecraft
    ) {}
    
    public function generateUserModel(): bool
    {
        return $this->codecraft->write('app/Models/User.php', [
            'namespace' => 'App\\Models',
            'name' => 'User',
            'extends' => 'Model'
        ]);
    }
}
```

## Testing

```bash
# Run all tests
composer test

# Test specific adapters
vendor/bin/phpunit --filter PhpAdapterTest
vendor/bin/phpunit --filter PythonAdapterTest
```

## Development

### Setup

```bash
git clone https://github.com/rnr1721/codecraft
cd codecraft
composer install
composer test
```

### Adding a New Language Adapter

1. Implement `FileAdapterInterface`
2. Add comprehensive help system with examples
3. Write tests covering all functionality
4. Update documentation and README
5. Submit a pull request

### Code Quality

```bash
# Run PHP CS Fixer
composer cs-fix

# Run PHPStan
composer phpstan
```

## Requirements

- **PHP 8.2+**
- **Composer**
- **Extensions**: `ext-json`, `ext-mbstring`

### Dependencies

- **nikic/php-parser** - For PHP AST manipulation
- **PHPUnit** - Testing framework

### Optional Dependencies

- **Xdebug** - For code coverage reports
- **symfony/console** - For CLI integration
- **laravel/framework** - For Laravel integration

## FAQ

**Q: How is CodeCraft different from existing code generators?**  
A: CodeCraft provides a simple, path-based API that's intuitive for both humans and AI. It focuses on real file operations rather than abstract concepts.

**Q: Why path-based instead of type-based?**  
A: Paths are more intuitive and provide context. `app/Models/User.php` immediately tells you what type of file it is and where it belongs.

**Q: How reliable is the AST-based PHP adapter?**  
A: Very reliable! It uses nikic/php-parser (the same library used by PHPStan, Psalm, and PHP-CS-Fixer) for accurate PHP code generation and manipulation.

**Q: How do I add support for a new language?**  
A: Implement the `FileAdapterInterface` and register your adapter. See the "Creating Custom Adapters" section for a complete example.

**Q: Is CodeCraft suitable for AI agents?**  
A: Yes! CodeCraft was designed with AI in mind. The simple, path-based API is easy for AI models to understand and use correctly.

## License

CodeCraft is open-sourced software licensed under the [MIT license](LICENSE).
