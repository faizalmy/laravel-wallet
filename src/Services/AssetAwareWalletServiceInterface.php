<?php

declare(strict_types=1);

namespace Bavix\Wallet\Services;

use Bavix\Wallet\Internal\Exceptions\ModelNotFoundException;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

/**
 * Asset-aware wallet service interface for multi-asset wallet creation.
 *
 * This interface extends the standard WalletServiceInterface to provide
 * asset-aware wallet creation capabilities that automatically route wallet
 * creation to asset-specific tables and models.
 *
 * @api
 */
interface AssetAwareWalletServiceInterface extends WalletServiceInterface
{
    /**
     * Create a wallet with automatic asset type detection and routing.
     *
     * This method automatically detects the asset type from the provided data
     * and creates the wallet in the appropriate asset-specific table.
     *
     * @param Model $model The model the wallet belongs to.
     * @param array{
     *     name: string,
     *     slug?: string,
     *     description?: string,
     *     meta?: array<mixed>|null,
     *     decimal_places?: positive-int,
     * } $data The data for the new wallet.
     * @param string|null $assetType The explicit asset type to use (optional, for manual override)
     * @return Wallet The newly created wallet.
     *
     * @throws \InvalidArgumentException If required fields are missing for the detected asset type
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createWithAssetContext(Model $model, array $data, ?string $assetType = null): Wallet;

    /**
     * Create a wallet for a specific asset type.
     *
     * This method creates a wallet explicitly for the specified asset type,
     * bypassing automatic detection.
     *
     * @param Model $model The model the wallet belongs to.
     * @param array{
     *     name: string,
     *     slug?: string,
     *     description?: string,
     *     meta?: array<mixed>|null,
     *     decimal_places?: positive-int,
     * } $data The data for the new wallet.
     * @param string $assetType The asset type identifier
     * @return Wallet The newly created wallet.
     *
     * @throws \InvalidArgumentException If required fields are missing for the asset type
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createForAssetType(Model $model, array $data, string $assetType): Wallet;

    /**
     * Get the asset type for a wallet.
     *
     * @param Wallet $wallet The wallet instance
     * @return string|null The asset type identifier or null if not detected
     */
    public function getAssetType(Wallet $wallet): ?string;

    /**
     * Check if wallet belongs to specific asset type.
     *
     * @param Wallet $wallet The wallet instance
     * @param string $assetType The asset type identifier to check
     * @return bool True if the wallet belongs to the specified asset type
     */
    public function isAssetType(Wallet $wallet, string $assetType): bool;

    /**
     * Find a wallet by slug with asset context awareness.
     *
     * This method searches for a wallet by slug, taking into account the
     * asset context to search in the appropriate asset-specific table.
     *
     * @param Model $model The model the wallet belongs to.
     * @param string $slug The slug of the wallet.
     * @param string|null $assetType The asset type to search in (optional)
     * @return Wallet|null The wallet with the given slug if found, otherwise null.
     */
    public function findBySlugWithAssetContext(Model $model, string $slug, ?string $assetType = null): ?Wallet;

    /**
     * Get a wallet by slug with asset context awareness.
     *
     * This method retrieves a wallet by slug, taking into account the
     * asset context to search in the appropriate asset-specific table.
     *
     * @param Model $model The model the wallet belongs to.
     * @param string $slug The slug of the wallet.
     * @param string|null $assetType The asset type to search in (optional)
     * @return Wallet The wallet with the given slug.
     *
     * @throws ModelNotFoundException If the wallet with the given slug is not found.
     */
    public function getBySlugWithAssetContext(Model $model, string $slug, ?string $assetType = null): Wallet;
}