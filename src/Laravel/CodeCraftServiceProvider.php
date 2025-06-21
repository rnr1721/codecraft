<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Laravel;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use rnr1721\CodeCraft\CodeCraftFactory;
use rnr1721\CodeCraft\AdapterRegistry;
use rnr1721\CodeCraft\Interfaces\AdapterRegistryInterface;
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;
use rnr1721\CodeCraft\Interfaces\CodeCraftFactoryInterface;
use rnr1721\CodeCraft\Interfaces\FileAdapterInterface;

/**
 * Laravel Service Provider for CodeCraft
 */
class CodeCraftServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/codecraft.php',
            'codecraft'
        );

        $this->app->singleton(
            AdapterRegistryInterface::class,
            function ($app) {
                $registry = new AdapterRegistry();

                $adapterClasses = Config::get('codecraft.adapters', []);

                foreach ($adapterClasses as $adapterClass) {
                    if (class_exists($adapterClass)) {
                        $adapter = $app->make($adapterClass);

                        if ($adapter instanceof FileAdapterInterface) {
                            $registry->register($adapter);
                        }
                    }
                }

                return $registry;
            }
        );

        $this->app->singleton(
            CodeCraftFactoryInterface::class,
            function ($app) {
                $registry = $app->make(AdapterRegistryInterface::class);
                return new CodeCraftFactory($registry);
            }
        );

        $this->app->singleton(
            CodeCraftInterface::class,
            function ($app) {
                $factory = $app->make(CodeCraftFactoryInterface::class);
                return $factory->create();
            }
        );

        $this->app->alias(CodeCraftInterface::class, 'codecraft');
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/codecraft.php' => config_path('codecraft.php'),
            ],
            'codecraft-config'
        );

        $this->publishes(
            [
                __DIR__ . '/../../stubs/laravel' => base_path('stubs/codecraft'),
            ],
            'codecraft-stubs'
        );

        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    Commands\CodeCraftGenerateCommand::class,
                ]
            );
        }
    }

    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return [
            AdapterRegistryInterface::class,
            CodeCraftFactoryInterface::class,
            CodeCraftInterface::class,
            'codecraft',
        ];
    }
}
