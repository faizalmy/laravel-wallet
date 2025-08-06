<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Interface for managing asset types in the multi-asset wallet system.
 *
 * This interface provides methods for registering, retrieving, and managing
 * asset type configurations dynamically.
 */
interface AssetTypeRegistryInterface
{
    /**
     * Register an asset type with its configuration.
     *
     * @param string $assetType The unique identifier for the asset type
     * @param array<string, mixed> $config The configuration array for the asset type
     * @return void
     *
     * @throws \InvalidArgumentException If the configuration is invalid
     */
    public function register(string $assetType, array $config): void;

    /**
     * Register an asset type using an AssetConfig object.
     *
     * @param AssetConfig $config The asset configuration object
     * @return void
     */
    public function registerConfig(AssetConfig $config): void;

    /**
     * Get the configuration for a specific asset type.
     *
     * @param string $assetType The asset type identifier
     * @return AssetConfig|null The asset configuration or null if not found
     */
    public function get(string $assetType): ?AssetConfig;

    /**
     * Check if an asset type is registered.
     *
     * @param string $assetType The asset type identifier
     * @return bool True if the asset type is registered
     */
    public function has(string $assetType): bool;

    /**
     * Get all registered asset types.
     *
     * @return array<string, AssetConfig> Array of asset type configurations
     */
    public function getAll(): array;

    /**
     * Get all registered asset types excluding the default type.
     *
     * @return array<string, AssetConfig> Array of custom asset type configurations
     */
    public function getCustom(): array;

    /**
     * Remove an asset type registration.
     *
     * @param string $assetType The asset type identifier
     * @return bool True if the asset type was removed, false if it didn't exist
     */
    public function remove(string $assetType): bool;

    /**
     * Clear all asset type registrations.
     *
     * @return void
     */
    public function clear(): void;

    /**
     * Resolve asset type from a model class name.
     *
     * @param string $modelClass The fully qualified model class name
     * @return string|null The asset type identifier or null if not found
     */
    public function resolveFromModel(string $modelClass): ?string;

    /**
     * Resolve asset type from a table name.
     *
     * @param string $tableName The table name
     * @return string|null The asset type identifier or null if not found
     */
    public function resolveFromTable(string $tableName): ?string;

    /**
     * Get the default asset type configuration.
     *
     * @return AssetConfig|null The default asset configuration or null if not set
     */
    public function getDefault(): ?AssetConfig;

    /**
     * Set the default asset type configuration.
     *
     * @param AssetConfig $config The default asset configuration
     * @return void
     */
    public function setDefault(AssetConfig $config): void;

    /**
     * Load asset types from configuration array.
     *
     * @param array<string, array<string, mixed>> $assetsConfig The assets configuration array
     * @return void
     *
     * @throws \InvalidArgumentException If any configuration is invalid
     */
    public function loadFromConfig(array $assetsConfig): void;
}