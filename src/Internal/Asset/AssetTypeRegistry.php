<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Implementation of AssetTypeRegistryInterface for managing asset types.
 *
 * This class provides dynamic asset type registration and management
 * for the multi-asset wallet system.
 */
final class AssetTypeRegistry implements AssetTypeRegistryInterface
{
    /**
     * @var array<string, AssetConfig> Registered asset type configurations
     */
    private array $assetTypes = [];

    /**
     * @var AssetConfig|null Default asset type configuration
     */
    private ?AssetConfig $defaultConfig = null;

    /**
     * @var array<string, string> Model class to asset type mapping
     */
    private array $modelToAssetType = [];

    /**
     * @var array<string, string> Table name to asset type mapping
     */
    private array $tableToAssetType = [];

    /**
     * Register an asset type with its configuration.
     *
     * @param string $assetType The unique identifier for the asset type
     * @param array<string, mixed> $config The configuration array for the asset type
     * @return void
     *
     * @throws \InvalidArgumentException If the configuration is invalid
     */
    public function register(string $assetType, array $config): void
    {
        $assetConfig = AssetConfig::fromArray($assetType, $config);
        $this->registerConfig($assetConfig);
    }

    /**
     * Register an asset type using an AssetConfig object.
     *
     * @param AssetConfig $config The asset configuration object
     * @return void
     */
    public function registerConfig(AssetConfig $config): void
    {
        $assetType = $config->getAssetType();

        // Register the asset type
        $this->assetTypes[$assetType] = $config;

        // Update mappings
        $this->modelToAssetType[$config->getWalletModel()] = $assetType;
        $this->modelToAssetType[$config->getTransactionModel()] = $assetType;
        $this->modelToAssetType[$config->getTransferModel()] = $assetType;

        $this->tableToAssetType[$config->getWalletTable()] = $assetType;
        $this->tableToAssetType[$config->getTransactionTable()] = $assetType;
        $this->tableToAssetType[$config->getTransferTable()] = $assetType;

        // Set as default if it's the default asset type
        if ($config->isDefault()) {
            $this->defaultConfig = $config;
        }
    }

    /**
     * Get the configuration for a specific asset type.
     *
     * @param string $assetType The asset type identifier
     * @return AssetConfig|null The asset configuration or null if not found
     */
    public function get(string $assetType): ?AssetConfig
    {
        return $this->assetTypes[$assetType] ?? null;
    }

    /**
     * Check if an asset type is registered.
     *
     * @param string $assetType The asset type identifier
     * @return bool True if the asset type is registered
     */
    public function has(string $assetType): bool
    {
        return array_key_exists($assetType, $this->assetTypes);
    }

    /**
     * Get all registered asset types.
     *
     * @return array<string, AssetConfig> Array of asset type configurations
     */
    public function getAll(): array
    {
        return $this->assetTypes;
    }

    /**
     * Get all registered asset types excluding the default type.
     *
     * @return array<string, AssetConfig> Array of custom asset type configurations
     */
    public function getCustom(): array
    {
        return array_filter(
            $this->assetTypes,
            fn (AssetConfig $config) => !$config->isDefault()
        );
    }

    /**
     * Remove an asset type registration.
     *
     * @param string $assetType The asset type identifier
     * @return bool True if the asset type was removed, false if it didn't exist
     */
    public function remove(string $assetType): bool
    {
        if (!array_key_exists($assetType, $this->assetTypes)) {
            return false;
        }

        $config = $this->assetTypes[$assetType];

        // Remove from main registry
        unset($this->assetTypes[$assetType]);

        // Remove from mappings
        unset($this->modelToAssetType[$config->getWalletModel()]);
        unset($this->modelToAssetType[$config->getTransactionModel()]);
        unset($this->modelToAssetType[$config->getTransferModel()]);

        unset($this->tableToAssetType[$config->getWalletTable()]);
        unset($this->tableToAssetType[$config->getTransactionTable()]);
        unset($this->tableToAssetType[$config->getTransferTable()]);

        // Clear default if this was the default
        if ($config->isDefault()) {
            $this->defaultConfig = null;
        }

        return true;
    }

    /**
     * Clear all asset type registrations.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->assetTypes = [];
        $this->defaultConfig = null;
        $this->modelToAssetType = [];
        $this->tableToAssetType = [];
    }

    /**
     * Resolve asset type from a model class name.
     *
     * @param string $modelClass The fully qualified model class name
     * @return string|null The asset type identifier or null if not found
     */
    public function resolveFromModel(string $modelClass): ?string
    {
        return $this->modelToAssetType[$modelClass] ?? null;
    }

    /**
     * Resolve asset type from a table name.
     *
     * @param string $tableName The table name
     * @return string|null The asset type identifier or null if not found
     */
    public function resolveFromTable(string $tableName): ?string
    {
        return $this->tableToAssetType[$tableName] ?? null;
    }

    /**
     * Get the default asset type configuration.
     *
     * @return AssetConfig|null The default asset configuration or null if not set
     */
    public function getDefault(): ?AssetConfig
    {
        return $this->defaultConfig;
    }

    /**
     * Set the default asset type configuration.
     *
     * @param AssetConfig $config The default asset configuration
     * @return void
     */
    public function setDefault(AssetConfig $config): void
    {
        $this->defaultConfig = $config;

        // Ensure it's also registered in the main registry
        if (!$this->has($config->getAssetType())) {
            $this->registerConfig($config);
        }
    }

    /**
     * Load asset types from configuration array.
     *
     * @param array<string, array<string, mixed>> $assetsConfig The assets configuration array
     * @return void
     *
     * @throws \InvalidArgumentException If any configuration is invalid
     */
    public function loadFromConfig(array $assetsConfig): void
    {
        foreach ($assetsConfig as $assetType => $config) {
            $this->register($assetType, $config);
        }
    }

    /**
     * Get asset type by model class with fallback to default.
     *
     * @param string $modelClass The model class name
     * @return string|null The asset type or null if not found
     */
    public function getAssetTypeByModel(string $modelClass): ?string
    {
        $assetType = $this->resolveFromModel($modelClass);

        if ($assetType !== null) {
            return $assetType;
        }

        // Fallback to default if available
        return $this->defaultConfig?->getAssetType();
    }

    /**
     * Get asset type by table name with fallback to default.
     *
     * @param string $tableName The table name
     * @return string|null The asset type or null if not found
     */
    public function getAssetTypeByTable(string $tableName): ?string
    {
        $assetType = $this->resolveFromTable($tableName);

        if ($assetType !== null) {
            return $assetType;
        }

        // Fallback to default if available
        return $this->defaultConfig?->getAssetType();
    }

    /**
     * Get the number of registered asset types.
     *
     * @return int The number of registered asset types
     */
    public function count(): int
    {
        return count($this->assetTypes);
    }

    /**
     * Check if any asset types are registered.
     *
     * @return bool True if any asset types are registered
     */
    public function isEmpty(): bool
    {
        return empty($this->assetTypes);
    }

    /**
     * Get asset type names as an array.
     *
     * @return array<string> Array of asset type names
     */
    public function getAssetTypeNames(): array
    {
        return array_keys($this->assetTypes);
    }
}