<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

use Bavix\Wallet\Internal\Repository\TransactionRepository;
use Bavix\Wallet\Internal\Repository\TransactionRepositoryInterface;
use Bavix\Wallet\Internal\Repository\TransferRepository;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Internal\Repository\WalletRepository;
use Bavix\Wallet\Internal\Repository\WalletRepositoryInterface;
use Bavix\Wallet\Internal\Service\JsonServiceInterface;
use Bavix\Wallet\Internal\Transform\TransactionDtoTransformerInterface;
use Bavix\Wallet\Internal\Transform\TransferDtoTransformerInterface;

/**
 * Implementation of AssetRepositoryFactoryInterface for creating context-aware repositories.
 *
 * This class provides factory methods for creating repositories that are aware
 * of the current asset context and use the appropriate models and tables.
 */
final class AssetRepositoryFactory implements AssetRepositoryFactoryInterface
{
    public function __construct(
        private readonly AssetTypeRegistryInterface $assetRegistry,
        private readonly AssetContextInterface $assetContext,
        private readonly TransactionDtoTransformerInterface $transactionDtoTransformer,
        private readonly TransferDtoTransformerInterface $transferDtoTransformer,
        private readonly JsonServiceInterface $jsonService
    ) {
    }

    /**
     * Create a wallet repository for the current asset context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return WalletRepositoryInterface The wallet repository
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createWalletRepository(?string $assetType = null): WalletRepositoryInterface
    {
        $config = $this->getAssetConfig($assetType);
        $walletModel = $this->createModelInstance($config->getWalletModel());

        return new WalletRepository($walletModel);
    }

    /**
     * Create a transaction repository for the current asset context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return TransactionRepositoryInterface The transaction repository
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createTransactionRepository(?string $assetType = null): TransactionRepositoryInterface
    {
        $config = $this->getAssetConfig($assetType);
        $transactionModel = $this->createModelInstance($config->getTransactionModel());

        return new TransactionRepository(
            $this->transactionDtoTransformer,
            $this->jsonService,
            $transactionModel
        );
    }

    /**
     * Create a transfer repository for the current asset context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return TransferRepositoryInterface The transfer repository
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function createTransferRepository(?string $assetType = null): TransferRepositoryInterface
    {
        $config = $this->getAssetConfig($assetType);
        $transferModel = $this->createModelInstance($config->getTransferModel());

        return new TransferRepository(
            $this->transferDtoTransformer,
            $this->jsonService,
            $transferModel
        );
    }

    /**
     * Get the asset configuration for the current context.
     *
     * @param string|null $assetType The asset type identifier or null to use current context
     * @return AssetConfig The asset configuration
     *
     * @throws \InvalidArgumentException If the asset type is not registered
     */
    public function getAssetConfig(?string $assetType = null): AssetConfig
    {
        // Use provided asset type or fall back to current context
        $targetAssetType = $assetType ?? $this->assetContext->getContext();

        if ($targetAssetType === null) {
            // Fall back to default asset type
            $defaultConfig = $this->assetRegistry->getDefault();
            if ($defaultConfig === null) {
                throw new \InvalidArgumentException('No asset context set and no default asset type configured');
            }

            return $defaultConfig;
        }

        $config = $this->assetRegistry->get($targetAssetType);
        if ($config === null) {
            throw new \InvalidArgumentException("Asset type '{$targetAssetType}' is not registered");
        }

        return $config;
    }

    /**
     * Check if an asset type is supported.
     *
     * @param string $assetType The asset type identifier
     * @return bool True if the asset type is supported
     */
    public function isAssetTypeSupported(string $assetType): bool
    {
        return $this->assetRegistry->has($assetType);
    }

    /**
     * Get all registered asset types.
     *
     * @param string $assetType The asset type identifier
     * @return array<string> Array of asset type identifiers
     */
    public function getAllAssetTypes(): array
    {
        return array_keys($this->assetRegistry->getAll());
    }

    /**
     * Create a model instance from a class name.
     *
     * @param string $modelClass The fully qualified model class name
     * @return object The model instance
     *
     * @throws \InvalidArgumentException If the model class cannot be instantiated
     */
    private function createModelInstance(string $modelClass): object
    {
        if (!class_exists($modelClass)) {
            throw new \InvalidArgumentException("Model class '{$modelClass}' does not exist");
        }

        return new $modelClass();
    }
}