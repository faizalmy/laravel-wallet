<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Configuration for a single asset type in the multi-asset wallet system.
 *
 * This class represents the complete configuration for an asset type,
 * including table names, model classes, and migration settings.
 * Uses value objects for type-safe configuration management.
 */
final class AssetConfig
{
    /**
     * @param string $assetType The unique identifier for this asset type
     * @param TableConfiguration $tableConfig The table configuration for this asset type
     * @param ModelConfiguration $modelConfig The model configuration for this asset type
     * @param string|null $migrationPath The path where migrations should be generated (optional)
     * @param array<string, mixed> $meta Additional metadata for this asset type (optional)
     */
    public function __construct(
        private readonly string $assetType,
        private readonly TableConfiguration $tableConfig,
        private readonly ModelConfiguration $modelConfig,
        private readonly ?string $migrationPath = null,
        private readonly array $meta = []
    ) {
    }

    /**
     * Get the asset type identifier.
     *
     * @return string The asset type identifier
     */
    public function getAssetType(): string
    {
        return $this->assetType;
    }

    /**
     * Get the wallet table name.
     *
     * @return string The wallet table name
     */
    public function getWalletTable(): string
    {
        return $this->tableConfig->getWalletTable();
    }

    /**
     * Get the transaction table name.
     *
     * @return string The transaction table name
     */
    public function getTransactionTable(): string
    {
        return $this->tableConfig->getTransactionTable();
    }

    /**
     * Get the transfer table name.
     *
     * @return string The transfer table name
     */
    public function getTransferTable(): string
    {
        return $this->tableConfig->getTransferTable();
    }

    /**
     * Get the wallet model class name.
     *
     * @return string The wallet model class name
     */
    public function getWalletModel(): string
    {
        return $this->modelConfig->getWalletModel();
    }

    /**
     * Get the transaction model class name.
     *
     * @return string The transaction model class name
     */
    public function getTransactionModel(): string
    {
        return $this->modelConfig->getTransactionModel();
    }

    /**
     * Get the transfer model class name.
     *
     * @return string The transfer model class name
     */
    public function getTransferModel(): string
    {
        return $this->modelConfig->getTransferModel();
    }

    /**
     * Get the migration path.
     *
     * @return string|null The migration path or null if not set
     */
    public function getMigrationPath(): ?string
    {
        return $this->migrationPath;
    }

    /**
     * Get additional metadata for this asset type.
     *
     * @return array<string, mixed> The metadata array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Get a specific metadata value.
     *
     * @param string $key The metadata key
     * @param mixed $default The default value if key doesn't exist
     * @return mixed The metadata value or default
     */
    public function getMetaValue(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Check if this asset type has a specific metadata key.
     *
     * @param string $key The metadata key to check
     * @return bool True if the key exists
     */
    public function hasMetaValue(string $key): bool
    {
        return array_key_exists($key, $this->meta);
    }

    /**
     * Create an AssetConfig from an array configuration.
     *
     * @param string $assetType The asset type identifier
     * @param array<string, mixed> $config The configuration array
     * @return self The created AssetConfig instance
     *
     * @throws \InvalidArgumentException If required configuration keys are missing
     */
    public static function fromArray(string $assetType, array $config): self
    {
        $requiredKeys = ['wallet_table', 'transaction_table', 'transfer_table', 'wallet_model', 'transaction_model', 'transfer_model'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \InvalidArgumentException("Missing required configuration key: {$key}");
            }
        }

        $tableConfig = TableConfiguration::fromArray([
            'wallet_table' => $config['wallet_table'],
            'transaction_table' => $config['transaction_table'],
            'transfer_table' => $config['transfer_table'],
        ]);

        $modelConfig = ModelConfiguration::fromArray([
            'wallet_model' => $config['wallet_model'],
            'transaction_model' => $config['transaction_model'],
            'transfer_model' => $config['transfer_model'],
        ]);

        return new self(
            assetType: $assetType,
            tableConfig: $tableConfig,
            modelConfig: $modelConfig,
            migrationPath: $config['migration_path'] ?? null,
            meta: $config['meta'] ?? []
        );
    }

    /**
     * Convert the AssetConfig to an array.
     *
     * @return array<string, mixed> The configuration array
     */
    public function toArray(): array
    {
        return array_merge(
            $this->tableConfig->toArray(),
            $this->modelConfig->toArray(),
            [
                'migration_path' => $this->migrationPath,
                'meta' => $this->meta,
            ]
        );
    }

    /**
     * Check if this asset type is the default asset type.
     *
     * @return bool True if this is the default asset type
     */
    public function isDefault(): bool
    {
        return $this->assetType === 'default';
    }

    /**
     * Get a table name by type.
     *
     * @param string $type The table type (wallet, transaction, transfer)
     * @return string The table name
     *
     * @throws \InvalidArgumentException If the table type is invalid
     */
    public function getTableByType(string $type): string
    {
        return $this->tableConfig->getTableByType($type);
    }

    /**
     * Get a model class name by type.
     *
     * @param string $type The model type (wallet, transaction, transfer)
     * @return string The model class name
     *
     * @throws \InvalidArgumentException If the model type is invalid
     */
    public function getModelByType(string $type): string
    {
        return $this->modelConfig->getModelByType($type);
    }
}