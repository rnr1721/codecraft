<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft\Interfaces;

/**
 * Simple CodeCraftFactory interface
 */
interface CodeCraftFactoryInterface
{
    /**
     * Create CodeCraft instance with pre-configured adapters
     *
     * @return CodeCraftInterface
     */
    public function create(): CodeCraftInterface;

    /**
     * Create CodeCraft instance with custom adapter registry
     *
     * @param AdapterRegistryInterface $registry
     * @return CodeCraftInterface
     */
    public function createWithRegistry(AdapterRegistryInterface $registry): CodeCraftInterface;

    /**
     * Create empty CodeCraft instance (no adapters)
     *
     * @return CodeCraftInterface
     */
    public function createEmpty(): CodeCraftInterface;

    /**
     * Get registry with all configured adapters
     *
     * @return AdapterRegistryInterface
     */
    public function getRegistry(): AdapterRegistryInterface;
}
