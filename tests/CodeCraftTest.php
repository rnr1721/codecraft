<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Tests;

use PHPUnit\Framework\TestCase;
use rnr1721\CodeCraft\CodeCraft;
use rnr1721\CodeCraft\AdapterRegistry;
use rnr1721\CodeCraft\CodeCraftFactory;
use rnr1721\CodeCraft\Adapters\PhpAdapter;
use rnr1721\CodeCraft\Adapters\JsonAdapter;
use rnr1721\CodeCraft\Adapters\JavaScriptAdapter;
use rnr1721\CodeCraft\Adapters\CssAdapter;
use rnr1721\CodeCraft\Adapters\PythonAdapter;
use rnr1721\CodeCraft\Adapters\TypeScriptAdapter;

/**
 * Main test suite for CodeCraft functionality - Simple Path-Based API
 */
class CodeCraftTest extends TestCase
{
    private CodeCraft $codeCraft;
    private string $tempDir;

    protected function setUp(): void
    {
        // Create temp directory for test files
        $this->tempDir = sys_get_temp_dir() . '/codecraft_tests_' . uniqid();
        mkdir($this->tempDir, 0777, true);

        // Setup CodeCraft with all adapters
        $registry = new AdapterRegistry();
        $registry->register(new PhpAdapter());
        $registry->register(new JsonAdapter());
        $registry->register(new JavaScriptAdapter());
        $registry->register(new TypeScriptAdapter());
        $registry->register(new CssAdapter());
        $registry->register(new PythonAdapter());

        $this->codeCraft = new CodeCraft($registry);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        $this->deleteDirectory($this->tempDir);
    }

    /**
     * Test basic CodeCraft functionality
     */
    public function testBasicFunctionality(): void
    {
        // Test supported extensions
        $extensions = $this->codeCraft->getSupportedExtensions();
        $this->assertContains('php', $extensions);
        $this->assertContains('json', $extensions);
        $this->assertContains('js', $extensions);

        // Test supports method
        $this->assertTrue($this->codeCraft->supports('php'));
        $this->assertTrue($this->codeCraft->supports('json'));
        $this->assertFalse($this->codeCraft->supports('unknown'));
    }

    /**
     * Test PHP adapter functionality
     */
    public function testPhpAdapter(): void
    {
        $filePath = $this->tempDir . '/User.php';

        // Test creating PHP class
        $content = $this->codeCraft->create($filePath, [
            'namespace' => 'App\\Models',
            'name' => 'User',
            'methods' => [
                [
                    'name' => 'getName',
                    'visibility' => 'public',
                    'returnType' => 'string',
                    'body' => 'return $this->name;'
                ]
            ],
            'properties' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'visibility' => 'private'
                ]
            ]
        ]);

        $this->assertStringContainsString('namespace App\\Models', $content);
        $this->assertStringContainsString('class User', $content);
        $this->assertStringContainsString('private string $name', $content);
        $this->assertStringContainsString('public function getName()', $content);
        $this->assertStringContainsString('return $this->name;', $content);

        // Test writing file
        $success = $this->codeCraft->write($filePath, [
            'namespace' => 'App\\Models',
            'name' => 'User'
        ]);
        $this->assertTrue($success);
        $this->assertFileExists($filePath);

        // Test validation
        $this->assertTrue($this->codeCraft->validate($filePath));

        // Test analysis
        $analysis = $this->codeCraft->analyze($filePath);
        $this->assertEquals('php', $analysis['type']);
        $this->assertEquals('App\\Models', $analysis['namespace']);
        $this->assertNotEmpty($analysis['classes']);

        // Test editing - add method
        $modifiedContent = $this->codeCraft->edit($filePath, [
            [
                'type' => 'add_method',
                'method' => [
                    'name' => 'setName',
                    'visibility' => 'public',
                    'params' => [['name' => 'name', 'type' => 'string']],
                    'body' => '$this->name = $name;'
                ]
            ]
        ]);

        $this->assertStringContainsString('public function setName(string $name)', $modifiedContent);
    }

    /**
     * Test JSON adapter functionality
     */
    public function testJsonAdapter(): void
    {
        $filePath = $this->tempDir . '/config.json';

        // Test creating JSON config
        $content = $this->codeCraft->create($filePath, [
            'type' => 'config',
            'app_name' => 'TestApp',
            'environment' => 'testing'
        ]);

        $data = json_decode($content, true);
        $this->assertEquals('TestApp', $data['name']);
        $this->assertEquals('testing', $data['environment']);

        // Test package.json creation
        $packagePath = $this->tempDir . '/package.json';
        $packageContent = $this->codeCraft->create($packagePath, [
            'type' => 'package',
            'name' => 'my-app',
            'version' => '1.0.0',
            'dependencies' => [
                'react' => '^18.0.0'
            ]
        ]);

        $packageData = json_decode($packageContent, true);
        $this->assertEquals('my-app', $packageData['name']);
        $this->assertEquals('^18.0.0', $packageData['dependencies']['react']);

        // Test JSON editing
        file_put_contents($filePath, $content);
        $modifiedContent = $this->codeCraft->edit($filePath, [
            [
                'type' => 'set',
                'path' => 'database.host',
                'value' => 'localhost'
            ]
        ]);

        $modifiedData = json_decode($modifiedContent, true);
        $this->assertEquals('localhost', $modifiedData['database']['host']);
    }

    /**
     * Test JavaScript adapter functionality
     */
    public function testJavaScriptAdapter(): void
    {
        $filePath = $this->tempDir . '/utils.js';

        // Test creating JavaScript function
        $content = $this->codeCraft->create($filePath, [
            'type' => 'function',
            'name' => 'formatDate',
            'params' => ['date', 'format'],
            'body' => 'return new Intl.DateTimeFormat(format).format(date);',
            'arrow' => true,
            'export' => true
        ]);

        $this->assertStringContainsString('const formatDate = (date, format) =>', $content);
        $this->assertStringContainsString('export { formatDate }', $content);

        // Test React component creation
        $componentPath = $this->tempDir . '/Button.jsx';
        $componentContent = $this->codeCraft->create($componentPath, [
            'type' => 'component',
            'name' => 'Button',
            'props' => ['text', 'onClick'],
            'functional' => true
        ]);

        $this->assertStringContainsString('const Button = ({ text, onClick })', $componentContent);
        $this->assertStringContainsString('export default Button', $componentContent);
    }

    /**
     * Test CSS adapter functionality
     */
    public function testCssAdapter(): void
    {
        $filePath = $this->tempDir . '/styles.css';

        // Test creating component styles
        $content = $this->codeCraft->create($filePath, [
            'type' => 'component',
            'name' => 'button',
            'elements' => [
                'text' => [
                    'color' => 'white',
                    'font-weight' => 'bold'
                ]
            ],
            'states' => [
                'hover' => ['background-color' => 'blue']
            ]
        ]);

        $this->assertStringContainsString('.button__text', $content);
        $this->assertStringContainsString('.button--hover', $content);
        $this->assertStringContainsString('color: white', $content);

        // Test utility classes creation
        $utilsPath = $this->tempDir . '/utilities.css';
        $utilsContent = $this->codeCraft->create($utilsPath, [
            'type' => 'utilities',
            'utilities' => [
                'spacing' => [
                    'p-1' => ['padding' => '0.25rem'],
                    'm-2' => ['margin' => '0.5rem']
                ]
            ]
        ]);

        $this->assertStringContainsString('.p-1', $utilsContent);
        $this->assertStringContainsString('padding: 0.25rem', $utilsContent);
    }

    /**
     * Test Python adapter functionality
     */
    public function testPythonAdapter(): void
    {
        $filePath = $this->tempDir . '/user.py';

        // Test creating Python class
        $content = $this->codeCraft->create($filePath, [
            'type' => 'class',
            'name' => 'User',
            'docstring' => 'User class for managing user data',
            'methods' => [
                [
                    'name' => '__init__',
                    'params' => ['self', 'name: str', 'email: str'],
                    'body' => 'self.name = name\\nself.email = email'
                ],
                [
                    'name' => 'get_info',
                    'params' => ['self'],
                    'return_type' => 'dict',
                    'body' => 'return {"name": self.name, "email": self.email}'
                ]
            ]
        ]);

        $this->assertStringContainsString('class User:', $content);
        $this->assertStringContainsString('"""User class for managing user data"""', $content);
        $this->assertStringContainsString('def __init__(self, name: str, email: str):', $content);

        // Test FastAPI creation
        $apiPath = $this->tempDir . '/main.py';
        $apiContent = $this->codeCraft->create($apiPath, [
            'type' => 'fastapi',
            'title' => 'My API',
            'endpoints' => [
                [
                    'path' => '/users',
                    'method' => 'GET',
                    'function' => 'get_users',
                    'return_type' => 'List[User]'
                ]
            ]
        ]);

        $this->assertStringContainsString('from fastapi import FastAPI', $apiContent);
        $this->assertStringContainsString('@app.get("/users")', $apiContent);
        $this->assertStringContainsString('def get_users() -> List[User]:', $apiContent);
    }

    /**
     * Test TypeScript adapter functionality
     */
    public function testTypeScriptAdapter(): void
    {
        // Test TypeScript interface creation
        $interfacePath = $this->tempDir . '/User.ts';
        $interfaceContent = $this->codeCraft->create($interfacePath, [
            'type' => 'interface',
            'name' => 'User',
            'properties' => [
                ['name' => 'id', 'type' => 'number'],
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'email', 'type' => 'string'],
                ['name' => 'avatar', 'type' => 'string', 'optional' => true]
            ],
            'extends' => ['BaseEntity']
        ]);

        $this->assertStringContainsString('export interface User extends BaseEntity', $interfaceContent);
        $this->assertStringContainsString('id: number;', $interfaceContent);
        $this->assertStringContainsString('avatar?: string;', $interfaceContent);

        // Test TypeScript class creation
        $classPath = $this->tempDir . '/UserService.ts';
        $classContent = $this->codeCraft->create($classPath, [
            'type' => 'class',
            'name' => 'UserService',
            'generic_params' => ['T extends BaseEntity'],
            'properties' => [
                [
                    'name' => 'apiClient',
                    'type' => 'AxiosInstance',
                    'visibility' => 'private'
                ]
            ],
            'methods' => [
                [
                    'name' => 'getUser',
                    'params' => [['name' => 'id', 'type' => 'number']],
                    'returnType' => 'Promise<T>',
                    'async' => true,
                    'body' => 'return this.apiClient.get(`/users/${id}`);'
                ]
            ]
        ]);

        $this->assertStringContainsString('export class UserService<T extends BaseEntity>', $classContent);
        $this->assertStringContainsString('private apiClient: AxiosInstance;', $classContent);
        $this->assertStringContainsString('async getUser(id: number): Promise<T>', $classContent);

        // Test React component creation
        $componentPath = $this->tempDir . '/UserCard.tsx';
        $componentContent = $this->codeCraft->create($componentPath, [
            'type' => 'component',
            'name' => 'UserCard',
            'props' => [
                ['name' => 'user', 'type' => 'User'],
                ['name' => 'onEdit', 'type' => '(user: User) => void', 'optional' => true],
                ['name' => 'variant', 'type' => '"primary" | "secondary"', 'default' => '"primary"']
            ],
            'hooks' => ['useState', 'useEffect']
        ]);

        $this->assertStringContainsString('import { React, useState, useEffect }', $componentContent);
        $this->assertStringContainsString('interface UserCardProps', $componentContent);
        $this->assertStringContainsString('const UserCard: React.FC<UserCardProps>', $componentContent);
        $this->assertStringContainsString('onEdit?: (user: User) => void;', $componentContent);
    }

    /**
     * Test TypeScript mapped types
     */
    public function testTypeScriptMappedTypes(): void
    {
        $mappedTypePath = $this->tempDir . '/Optional.ts';

        // Test creating mapped type for optional properties
        $mappedTypeContent = $this->codeCraft->create($mappedTypePath, [
            'type' => 'type',
            'name' => 'Optional',
            'generic_params' => ['T', 'K extends keyof T'],
            'type_category' => 'mapped',
            'key_type' => 'P',
            'source_type' => 'T',
            'modifier' => 'optional',
            'value_type' => 'T[P]'
        ]);

        $this->assertStringContainsString('export type Optional<T, K extends keyof T>', $mappedTypeContent);
        $this->assertStringContainsString('[P in keyof T]?: T[P];', $mappedTypeContent);

        // Test readonly mapped type
        $readonlyTypePath = $this->tempDir . '/Readonly.ts';
        $readonlyContent = $this->codeCraft->create($readonlyTypePath, [
            'type' => 'type',
            'name' => 'ReadonlyEntity',
            'generic_params' => ['T'],
            'type_category' => 'mapped',
            'key_type' => 'K',
            'source_type' => 'T',
            'modifier' => 'readonly',
            'value_type' => 'T[K]'
        ]);

        $this->assertStringContainsString('readonly [K in keyof T]: T[K];', $readonlyContent);
    }

    /**
     * Test TypeScript conditional types
     */
    public function testTypeScriptConditionalTypes(): void
    {
        $conditionalTypePath = $this->tempDir . '/ExtractArrayType.ts';

        // Test conditional type with inference
        $conditionalContent = $this->codeCraft->create($conditionalTypePath, [
            'type' => 'type',
            'name' => 'ExtractArrayType',
            'generic_params' => ['T'],
            'type_category' => 'conditional',
            'check_type' => 'T',
            'extends_type' => '(infer U)[]',
            'true_type' => 'U',
            'false_type' => 'never',
            'inference' => ['name' => 'U', 'placeholder' => 'infer U']
        ]);

        $this->assertStringContainsString('export type ExtractArrayType<T>', $conditionalContent);
        $this->assertStringContainsString('T extends (infer U)[] ? U : never', $conditionalContent);
    }

    /**
     * Test TypeScript utility types
     */
    public function testTypeScriptUtilityTypes(): void
    {
        // Test Pick utility type
        $pickTypePath = $this->tempDir . '/UserUpdate.ts';
        $pickContent = $this->codeCraft->create($pickTypePath, [
            'type' => 'type',
            'name' => 'UserUpdate',
            'type_category' => 'utility',
            'utility_type' => 'Pick',
            'source_type' => 'User',
            'keys' => ['name', 'email']
        ]);

        $this->assertStringContainsString('export type UserUpdate = Pick<User, \'name\' | \'email\'>', $pickContent);

        // Test Partial utility type
        $partialTypePath = $this->tempDir . '/PartialUser.ts';
        $partialContent = $this->codeCraft->create($partialTypePath, [
            'type' => 'type',
            'name' => 'PartialUser',
            'type_category' => 'utility',
            'utility_type' => 'Partial',
            'source_type' => 'User'
        ]);

        $this->assertStringContainsString('export type PartialUser = Partial<User>', $partialContent);
    }

    /**
     * Test CodeCraftFactory functionality
     */
    public function testCodeCraftFactory(): void
    {
        $registry = new AdapterRegistry();
        $factory = new CodeCraftFactory($registry);

        // Test creating instances
        $instance1 = $factory->create();
        $instance2 = $factory->createEmpty();

        $this->assertInstanceOf(CodeCraft::class, $instance1);
        $this->assertInstanceOf(CodeCraft::class, $instance2);

        // Test custom registry
        $customRegistry = new AdapterRegistry();
        $customRegistry->register(new JsonAdapter());

        $customInstance = $factory->createWithRegistry($customRegistry);
        $this->assertTrue($customInstance->supports('json'));
        $this->assertFalse($customInstance->supports('php'));
    }

    /**
     * Test error handling
     */
    public function testErrorHandling(): void
    {
        // Test invalid file path for analysis
        $this->expectException(\InvalidArgumentException::class);
        $this->codeCraft->analyze('/nonexistent/file.php');
    }

    /**
     * Test unsupported extension
     */
    public function testUnsupportedExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->codeCraft->create($this->tempDir . '/test.unknown', []);
    }

    /**
     * Test file validation
     */
    public function testValidation(): void
    {
        $validPhp = '<?php class Test {}';
        $invalidPhp = '<?php class Test { invalid syntax';

        // Create temp files
        $validFile = $this->tempDir . '/valid.php';
        $invalidFile = $this->tempDir . '/invalid.php';

        file_put_contents($validFile, $validPhp);
        file_put_contents($invalidFile, $invalidPhp);

        $this->assertTrue($this->codeCraft->validate($validFile));
        $this->assertFalse($this->codeCraft->validate($invalidFile));
    }

    /**
     * Test help system
     */
    public function testHelp(): void
    {
        $phpHelp = $this->codeCraft->getHelp('php');
        $this->assertArrayHasKey('description', $phpHelp);
        $this->assertArrayHasKey('examples', $phpHelp);
        $this->assertArrayHasKey('options', $phpHelp);

        $jsonHelp = $this->codeCraft->getHelp('json');
        $this->assertArrayHasKey('supported_types', $jsonHelp);
    }

    /**
     * Test write method
     */
    public function testWrite(): void
    {
        $filePath = $this->tempDir . '/TestClass.php';

        // Test write method creates file
        $success = $this->codeCraft->write($filePath, [
            'namespace' => 'Test',
            'name' => 'TestClass'
        ]);

        $this->assertTrue($success);
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('namespace Test', $content);
        $this->assertStringContainsString('class TestClass', $content);
    }

    /**
     * Test editFile method
     */
    public function testEditFile(): void
    {
        $filePath = $this->tempDir . '/EditableClass.php';

        // Create initial file
        $this->codeCraft->write($filePath, [
            'namespace' => 'Test',
            'name' => 'EditableClass'
        ]);

        // Edit the file
        $success = $this->codeCraft->editFile($filePath, [
            [
                'type' => 'add_method',
                'method' => [
                    'name' => 'testMethod',
                    'visibility' => 'public',
                    'body' => 'return "test";'
                ]
            ]
        ]);

        $this->assertTrue($success);

        $content = file_get_contents($filePath);
        $this->assertStringContainsString('testMethod', $content);
    }

    /**
     * Test path-based type detection
     */
    public function testPathBasedTypeDetection(): void
    {
        // PHP file
        $phpContent = $this->codeCraft->create($this->tempDir . '/Model.php', [
            'name' => 'Model'
        ]);
        $this->assertStringContainsString('class Model', $phpContent);

        // JSON file
        $jsonContent = $this->codeCraft->create($this->tempDir . '/config.json', [
            'type' => 'config',
            'app_name' => 'Test'
        ]);
        $jsonData = json_decode($jsonContent, true);
        $this->assertEquals('Test', $jsonData['name']);

        // JavaScript file
        $jsContent = $this->codeCraft->create($this->tempDir . '/utils.js', [
            'type' => 'function',
            'name' => 'utils'
        ]);
        $this->assertStringContainsString('function utils', $jsContent);

        // CSS file
        $cssContent = $this->codeCraft->create($this->tempDir . '/styles.css', [
            'type' => 'component',
            'name' => 'button'
        ]);
        $this->assertStringContainsString('.button', $cssContent);

        // Python file
        $pyContent = $this->codeCraft->create($this->tempDir . '/app.py', [
            'type' => 'module'
        ]);
        $this->assertIsString($pyContent);

        // TypeScript file
        $tsContent = $this->codeCraft->create($this->tempDir . '/types.ts', [
            'type' => 'interface',
            'name' => 'TestInterface'
        ]);
        $this->assertStringContainsString('interface TestInterface', $tsContent);
    }

    /**
     * Test directory creation for nested paths
     */
    public function testDirectoryCreation(): void
    {
        $nestedPath = $this->tempDir . '/deep/nested/path/Class.php';

        $success = $this->codeCraft->write($nestedPath, [
            'name' => 'NestedClass'
        ]);

        $this->assertTrue($success);
        $this->assertFileExists($nestedPath);
        $this->assertDirectoryExists(dirname($nestedPath));
    }

    /**
     * Helper method to delete directory recursively
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
