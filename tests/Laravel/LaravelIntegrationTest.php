<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Tests\Laravel;

use Orchestra\Testbench\TestCase;
use rnr1721\CodeCraft\Laravel\CodeCraftServiceProvider;
use rnr1721\CodeCraft\Laravel\Facades\CodeCraft;
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;
use rnr1721\CodeCraft\Interfaces\CodeCraftFactoryInterface;
use rnr1721\CodeCraft\Interfaces\AdapterRegistryInterface;

/**
 * Laravel integration tests for CodeCraft - Simple Path-Based API
 */
class LaravelIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CodeCraftServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'CodeCraft' => CodeCraft::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Set config inline instead of loading from file
        $app['config']->set('codecraft', [
            'adapters' => [
                \rnr1721\CodeCraft\Adapters\PhpAdapter::class,
                \rnr1721\CodeCraft\Adapters\JsonAdapter::class,
                \rnr1721\CodeCraft\Adapters\JavaScriptAdapter::class,
            ],
            'defaults' => [
                'php' => [
                    'namespace' => 'App',
                    'strict_types' => true,
                ],
                'jsx' => [
                    'functional' => true,
                ],
            ],
        ]);
    }

    /**
     * Override mergeConfigFrom to avoid file loading
     */
    protected function resolveApplicationConfiguration($app): void
    {
        parent::resolveApplicationConfiguration($app);

        // Mock the config file loading to avoid file not found errors
        $app['config']->set('codecraft', $app['config']->get('codecraft', []));
    }

    /**
     * Test service provider registration
     */
    public function testServiceProviderRegistration(): void
    {
        // Test that services are registered
        $this->assertTrue($this->app->bound(AdapterRegistryInterface::class));
        $this->assertTrue($this->app->bound(CodeCraftFactoryInterface::class));
        $this->assertTrue($this->app->bound(CodeCraftInterface::class));
        $this->assertTrue($this->app->bound('codecraft'));

        // Test singleton behavior
        $instance1 = $this->app->make(CodeCraftInterface::class);
        $instance2 = $this->app->make(CodeCraftInterface::class);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test facade functionality
     */
    public function testFacade(): void
    {
        // Test basic facade methods
        $extensions = CodeCraft::getSupportedExtensions();
        $this->assertIsArray($extensions);
        $this->assertContains('php', $extensions);
        $this->assertContains('json', $extensions);
        $this->assertContains('js', $extensions);

        // Test facade supports method
        $this->assertTrue(CodeCraft::supports('php'));
        $this->assertTrue(CodeCraft::supports('json'));
        $this->assertFalse(CodeCraft::supports('unknown'));

        // Test facade create method with path-based API
        $content = CodeCraft::create('app/Models/Test.php', [
            'name' => 'TestClass',
            'namespace' => 'App\\Models'
        ]);

        $this->assertStringContainsString('namespace App\\Models', $content);
        $this->assertStringContainsString('class TestClass', $content);
    }

    /**
     * Test dependency injection
     */
    public function testDependencyInjection(): void
    {
        $codeCraft = $this->app->make(CodeCraftInterface::class);
        $this->assertInstanceOf(CodeCraftInterface::class, $codeCraft);

        $factory = $this->app->make(CodeCraftFactoryInterface::class);
        $this->assertInstanceOf(CodeCraftFactoryInterface::class, $factory);

        $registry = $this->app->make(AdapterRegistryInterface::class);
        $this->assertInstanceOf(AdapterRegistryInterface::class, $registry);
    }

    /**
     * Test configuration loading
     */
    public function testConfiguration(): void
    {
        $adapters = config('codecraft.adapters');
        $this->assertIsArray($adapters);
        $this->assertContains(\rnr1721\CodeCraft\Adapters\PhpAdapter::class, $adapters);

        // Test that adapters are auto-registered from config
        $codeCraft = $this->app->make(CodeCraftInterface::class);
        $this->assertTrue($codeCraft->supports('php'));
        $this->assertTrue($codeCraft->supports('json'));
        $this->assertTrue($codeCraft->supports('js'));
    }

    /**
     * Test Laravel-specific file generation
     */
    public function testLaravelFileGeneration(): void
    {
        $tempDir = sys_get_temp_dir() . '/codecraft_laravel_tests_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            // Test Model generation using path-based API
            $modelPath = $tempDir . '/User.php';
            $success = CodeCraft::write($modelPath, [
                'name' => 'User',
                'namespace' => 'App\\Models',
                'extends' => 'Model',
                'methods' => [
                    [
                        'name' => 'getFullNameAttribute',
                        'visibility' => 'public',
                        'body' => 'return $this->first_name . " " . $this->last_name;'
                    ]
                ]
            ]);

            $this->assertTrue($success);
            $this->assertFileExists($modelPath);

            $content = file_get_contents($modelPath);
            $this->assertStringContainsString('namespace App\\Models', $content);
            $this->assertStringContainsString('class User extends Model', $content);

            // Test Controller generation
            $controllerPath = $tempDir . '/UserController.php';
            $success = CodeCraft::write($controllerPath, [
                'name' => 'UserController',
                'namespace' => 'App\\Http\\Controllers',
                'extends' => 'Controller',
                'methods' => [
                    [
                        'name' => 'index',
                        'visibility' => 'public',
                        'body' => 'return User::all();'
                    ],
                    [
                        'name' => 'show',
                        'visibility' => 'public',
                        'params' => [['name' => 'user', 'type' => 'User']],
                        'body' => 'return $user;'
                    ]
                ]
            ]);

            $this->assertTrue($success);
            $this->assertFileExists($controllerPath);

            // Test JSON config generation
            $configPath = $tempDir . '/config.json';
            $success = CodeCraft::write($configPath, [
                'type' => 'config',
                'app_name' => 'LaravelApp',
                'environment' => 'testing'
            ]);

            $this->assertTrue($success);
            $this->assertFileExists($configPath);

            $configData = json_decode(file_get_contents($configPath), true);
            $this->assertEquals('LaravelApp', $configData['name']);

        } finally {
            // Cleanup
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Test multiple adapter registration
     */
    public function testMultipleAdapterRegistration(): void
    {
        $registry = $this->app->make(AdapterRegistryInterface::class);
        $adapters = $registry->getAllAdapters();

        $this->assertNotEmpty($adapters);

        $adapterNames = array_map(fn ($adapter) => $adapter->getName(), $adapters);
        $this->assertContains('php', $adapterNames);
        $this->assertContains('json', $adapterNames);
        $this->assertContains('javascript', $adapterNames);
    }

    /**
     * Test factory pattern integration
     */
    public function testFactoryIntegration(): void
    {
        $factory = $this->app->make(CodeCraftFactoryInterface::class);

        // Test creating new instances
        $instance1 = $factory->create();
        $instance2 = $factory->createEmpty();

        $this->assertInstanceOf(CodeCraftInterface::class, $instance1);
        $this->assertInstanceOf(CodeCraftInterface::class, $instance2);

        // Empty instance should have no adapters
        $this->assertEmpty($instance2->getSupportedExtensions());

        // Main instance should have adapters from config
        $this->assertNotEmpty($instance1->getSupportedExtensions());
    }

    /**
     * Test path-based file type detection
     */
    public function testPathBasedTypeDetection(): void
    {
        $tempDir = sys_get_temp_dir() . '/codecraft_path_test_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            // Test PHP file detection
            $phpPath = $tempDir . '/TestClass.php';
            $phpContent = CodeCraft::create($phpPath, ['name' => 'TestClass']);
            $this->assertStringContainsString('class TestClass', $phpContent);

            // Test JSON file detection
            $jsonPath = $tempDir . '/config.json';
            $jsonContent = CodeCraft::create($jsonPath, ['type' => 'config', 'app_name' => 'Test']);
            $jsonData = json_decode($jsonContent, true);
            $this->assertEquals('Test', $jsonData['name']);

            // Test JavaScript file detection
            $jsPath = $tempDir . '/utils.js';
            $jsContent = CodeCraft::create($jsPath, ['type' => 'function', 'name' => 'testFunc']);
            $this->assertStringContainsString('function testFunc', $jsContent);

        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Test editing functionality through facade
     */
    public function testEditFunctionality(): void
    {
        $tempDir = sys_get_temp_dir() . '/codecraft_edit_test_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $filePath = $tempDir . '/EditableClass.php';

            // Create initial file
            CodeCraft::write($filePath, [
                'name' => 'EditableClass',
                'namespace' => 'Test'
            ]);

            // Test edit method
            $modifiedContent = CodeCraft::edit($filePath, [
                [
                    'type' => 'add_method',
                    'method' => [
                        'name' => 'testMethod',
                        'visibility' => 'public',
                        'body' => 'return "test";'
                    ]
                ]
            ]);

            $this->assertStringContainsString('testMethod', $modifiedContent);

            // Test editFile method
            $success = CodeCraft::editFile($filePath, [
                [
                    'type' => 'add_method',
                    'method' => [
                        'name' => 'anotherMethod',
                        'visibility' => 'public',
                        'body' => 'return "another";'
                    ]
                ]
            ]);

            $this->assertTrue($success);

            $content = file_get_contents($filePath);
            $this->assertStringContainsString('anotherMethod', $content);

        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Test analysis and validation through facade
     */
    public function testAnalysisAndValidation(): void
    {
        $tempDir = sys_get_temp_dir() . '/codecraft_analysis_test_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $filePath = $tempDir . '/AnalysisClass.php';

            // Create file for analysis
            CodeCraft::write($filePath, [
                'name' => 'AnalysisClass',
                'namespace' => 'Test\\Analysis',
                'methods' => [
                    [
                        'name' => 'testMethod',
                        'visibility' => 'public',
                        'returnType' => 'string',
                        'body' => 'return "test";'
                    ]
                ]
            ]);

            // Test analysis
            $analysis = CodeCraft::analyze($filePath);
            $this->assertEquals('php', $analysis['type']);
            $this->assertEquals('Test\\Analysis', $analysis['namespace']);
            $this->assertNotEmpty($analysis['classes']);

            // Test validation
            $this->assertTrue(CodeCraft::validate($filePath));

        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * Test help system through facade
     */
    public function testHelpSystem(): void
    {
        // Test getting help for specific extension
        $phpHelp = CodeCraft::getHelp('php');
        $this->assertIsArray($phpHelp);
        $this->assertArrayHasKey('description', $phpHelp);
        $this->assertArrayHasKey('examples', $phpHelp);

        $jsonHelp = CodeCraft::getHelp('json');
        $this->assertIsArray($jsonHelp);
        $this->assertArrayHasKey('supported_types', $jsonHelp);
    }

    /**
     * Test error handling in Laravel context
     */
    public function testErrorHandling(): void
    {
        // Test with empty adapters config
        config(['codecraft.adapters' => []]);

        // Should not break the service provider
        $codeCraft = $this->app->make(CodeCraftInterface::class);
        $this->assertInstanceOf(CodeCraftInterface::class, $codeCraft);

        // Should have no supported extensions
        $this->assertEmpty($codeCraft->getSupportedExtensions());
    }

    /**
     * Test adapter registration from config
     */
    public function testAdapterRegistrationFromConfig(): void
    {
        // Test that adapters from config are properly registered
        $codeCraft = $this->app->make(CodeCraftInterface::class);

        $extensions = $codeCraft->getSupportedExtensions();
        $this->assertContains('php', $extensions);
        $this->assertContains('json', $extensions);
        $this->assertContains('js', $extensions);

        // Test that non-configured adapters are not available
        $this->assertNotContains('py', $extensions); // Python not in test config
        $this->assertNotContains('css', $extensions); // CSS not in test config
    }

    /**
     * Test service provider provides method
     */
    public function testServiceProviderProvides(): void
    {
        $provider = new CodeCraftServiceProvider($this->app);
        $provides = $provider->provides();

        $this->assertContains(AdapterRegistryInterface::class, $provides);
        $this->assertContains(CodeCraftFactoryInterface::class, $provides);
        $this->assertContains(CodeCraftInterface::class, $provides);
        $this->assertContains('codecraft', $provides);
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
