<?php

declare(strict_types=1);

namespace rnr1721\CodeCraft;

use rnr1721\CodeCraft\Interfaces\AdapterRegistryInterface;
use rnr1721\CodeCraft\Interfaces\CodeCraftFactoryInterface;
use rnr1721\CodeCraft\Interfaces\CodeCraftInterface;

/**
 * Simple CodeCraftFactory
 */
class CodeCraftFactory implements CodeCraftFactoryInterface
{
    /**
     * Constructor
     *
     * @param AdapterRegistryInterface $adapterRegistry Registry with pre-configured adapters
     */
    public function __construct(
        protected AdapterRegistryInterface $adapterRegistry
    ) {
    }

    /**
     * Create CodeCraft instance with pre-configured adapters
     *
     * @return CodeCraftInterface
     */
    public function create(): CodeCraftInterface
    {
        return new CodeCraft($this->adapterRegistry);
    }

    /**
     * Create CodeCraft instance with custom adapter registry
     *
     * @param AdapterRegistryInterface $registry
     * @return CodeCraftInterface
     */
    public function createWithRegistry(AdapterRegistryInterface $registry): CodeCraftInterface
    {
        return new CodeCraft($registry);
    }

    /**
     * Create empty CodeCraft instance (no adapters)
     *
     * @return CodeCraftInterface
     */
    public function createEmpty(): CodeCraftInterface
    {
        return new CodeCraft(new AdapterRegistry());
    }

    /**
     * Get registry with all configured adapters
     *
     * @return AdapterRegistryInterface
     */
    public function getRegistry(): AdapterRegistryInterface
    {
        return $this->adapterRegistry;
    }
}
