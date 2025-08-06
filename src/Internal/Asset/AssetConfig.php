<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Configuration for a single asset type in the multi-asset wallet system.
 *
 * This class represents the complete configuration for an asset type,
 * including table names, model classes, and migration settings.
 */
final class AssetConfig
{
    /**
     * @param string $assetType The unique identifier for this asset type
     * @param string $walletTable The table name for wallets of this asset type
     * @param string $transactionTable The table name for transactions of this asset type
     * @param string $transferTable The table name for transfers of this asset type
     * @param string $walletModel The fully qualified class name for the wallet model
     * @param string $transactionModel The fully qualified class name for the transaction model
     * @param string $transferModel The fully qualified class name for the transfer model
     * @param string|null $migrationPath The path where migrations should be generated (optional)
     * @param array<string, mixed> $meta Additional metadata for this asset type (optional)
     */
    public function __construct(
        private readonly string $assetType,
        private readonly string $walletTable,
        private readonly string $transactionTable,
        private readonly string $transferTable,
        private readonly string $walletModel,
        private readonly string $transactionModel,
        private readonly string $transferModel,
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
        return $this->walletTable;
    }

    /**
     * Get the transaction table name.
     *
     * @return string The transaction table name
     */
    public function getTransactionTable(): string
    {
        return $this->transactionTable;
    }

    /**
     * Get the transfer table name.
     *
     * @return string The transfer table name
     */
    public function getTransferTable(): string
    {
        return $this->transferTable;
    }

    /**
     * Get the wallet model class name.
     *
     * @return string The wallet model class name
     */
    public function getWalletModel(): string
    {
        return $this->walletModel;
    }

    /**
     * Get the transaction model class name.
     *
     * @return string The transaction model class name
     */
    public function getTransactionModel(): string
    {
        return $this->transactionModel;
    }

    /**
     * Get the transfer model class name.
     *
     * @return string The transfer model class name
     */
    public function getTransferModel(): string
    {
        return $this->transferModel;
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

        return new self(
            assetType: $assetType,
            walletTable: $config['wallet_table'],
            transactionTable: $config['transaction_table'],
            transferTable: $config['transfer_table'],
            walletModel: $config['wallet_model'],
            transactionModel: $config['transaction_model'],
            transferModel: $config['transfer_model'],
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
        return [
            'wallet_table' => $this->walletTable,
            'transaction_table' => $this->transactionTable,
            'transfer_table' => $this->transferTable,
            'wallet_model' => $this->walletModel,
            'transaction_model' => $this->transactionModel,
            'transfer_model' => $this->transferModel,
            'migration_path' => $this->migrationPath,
            'meta' => $this->meta,
        ];
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
        return match ($type) {
            'wallet' => $this->walletTable,
            'transaction' => $this->transactionTable,
            'transfer' => $this->transferTable,
            default => throw new \InvalidArgumentException("Invalid table type: {$type}"),
        };
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
        return match ($type) {
            'wallet' => $this->walletModel,
            'transaction' => $this->transactionModel,
            'transfer' => $this->transferModel,
            default => throw new \InvalidArgumentException("Invalid model type: {$type}"),
        };
    }
}