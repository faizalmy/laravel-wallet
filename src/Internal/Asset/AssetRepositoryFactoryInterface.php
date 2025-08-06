<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

use Bavix\Wallet\Internal\Repository\TransactionRepositoryInterface;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Internal\Repository\WalletRepositoryInterface;

/**
 * Interface for creating context-aware repositories in the multi-asset wallet system.
 *
 * This interface provides methods for creating repositories that are aware
 * of the current asset context and use the appropriate models and tables.
 */
interface AssetRepositoryFactoryInterface
{
    /**
     * Create a wallet repository for the current asset context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return WalletRepositoryInterface The wallet repository
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createWalletRepository(?string $assetType = null): WalletRepositoryInterface;

    /**
     * Create a transaction repository for the current asset context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return TransactionRepositoryInterface The transaction repository
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createTransactionRepository(?string $assetType = null): TransactionRepositoryInterface;

    /**
     * Create a transfer repository for the current asset context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return TransferRepositoryInterface The transfer repository
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createTransferRepository(?string $assetType = null): TransferRepositoryInterface;

    /**
     * Get the asset configuration for the current context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return AssetConfig The asset configuration
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function getAssetConfig(?string $assetType = null): AssetConfig;

    /**
     * Check if an asset type is supported.
     *
     * @param string $assetType The asset type identifier
     * @return bool True if the asset type is supported
     */
    public function isAssetTypeSupported(string $assetType): bool;
}