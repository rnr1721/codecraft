<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;

/**
 * CodeCraft Facade for Laravel
 *
 * @method static string create(string $filePath, array $options = [])
 * @method static bool write(string $filePath, array $options = [])
 * @method static string edit(string $filePath, array $modifications)
 * @method static bool editFile(string $filePath, array $modifications)
 * @method static array analyze(string $filePath)
 * @method static bool validate(string $filePath)
 * @method static bool supports(string $extension)
 * @method static array getSupportedExtensions()
 * @method static array getHelp(string $extension)
 * @method static void registerAdapter(\rnr1721\CodeCraft\Interfaces\FileAdapterInterface $adapter)
 *
 * @see \rnr1721\CodeCraft\Interfaces\CodeCraftInterface
 */
class CodeCraft extends Facade
{
    /**
     * Get the registered name of the component
     */
    protected static function getFacadeAccessor(): string
    {
        return CodeCraftInterface::class;
    }
}