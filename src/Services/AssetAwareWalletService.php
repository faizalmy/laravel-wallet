<?php

declare(strict_types=1);

namespace Bavix\Wallet\Services;

use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Assembler\WalletCreatedEventAssemblerInterface;
use Bavix\Wallet\Internal\Exceptions\ModelNotFoundException;
use Bavix\Wallet\Internal\Repository\WalletRepositoryInterface;
use Bavix\Wallet\Internal\Service\DispatcherServiceInterface;
use Bavix\Wallet\Internal\Service\IdentifierFactoryServiceInterface;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

/**
 * Asset-aware wallet service for multi-asset wallet creation.
 *
 * This service extends the standard wallet creation functionality to provide
 * asset-aware wallet creation that automatically routes wallet creation to
 * asset-specific tables and models.
 *
 * @internal
 */
final readonly class AssetAwareWalletService implements AssetAwareWalletServiceInterface
{
    public function __construct(
        private AssetContextInterface $assetContext,
        private AssetRepositoryFactoryInterface $repositoryFactory,
        private AssetTypeDetector $assetTypeDetector,
        private IdentifierFactoryServiceInterface $identifierFactory,
        private DispatcherServiceInterface $dispatcherService,
        private WalletCreatedEventAssemblerInterface $walletCreatedEventAssembler,
        private WalletRepositoryInterface $defaultWalletRepository
    ) {
    }

    public function createWithAssetContext(Model $model, array $data, ?string $assetType = null): Wallet
    {
        // Detect asset type from data or use provided type
        $detectedAssetType = $assetType ?? $this->assetTypeDetector->detectFromWalletData($data);

        if ($detectedAssetType !== null) {
            return $this->assetContext->withContext($detectedAssetType, function () use ($model, $data, $detectedAssetType) {
                return $this->createForAssetType($model, $data, $detectedAssetType);
            });
        }

        // Fall back to default behavior using default repository
        return $this->create($model, $data);
    }

    public function createForAssetType(Model $model, array $data, string $assetType): Wallet
    {
        // Validate asset type exists
        if (!$this->repositoryFactory->isAssetTypeSupported($assetType)) {
            throw new \InvalidArgumentException("Asset type '{$assetType}' is not registered");
        }

        // Validate required fields for this asset type
        $this->validateRequiredFields($data, $assetType);

        // Get asset-specific repository
        $walletRepository = $this->repositoryFactory->createWalletRepository($assetType);

        // Create wallet using asset-specific repository
        $wallet = $walletRepository->create(array_merge(
            config('wallet.wallet.creating', []),
            [
                'uuid' => $this->identifierFactory->generate(),
            ],
            $data,
            [
                'holder_type' => $model->getMorphClass(),
                'holder_id' => $model->getKey(),
            ]
        ));

        // Dispatch events and return
        $event = $this->walletCreatedEventAssembler->create($wallet);
        $this->dispatcherService->dispatch($event);
        $this->dispatcherService->lazyFlush();

        return $wallet;
    }

    public function getAssetType(Wallet $wallet): ?string
    {
        return $this->assetTypeDetector->detectFromWallet($wallet);
    }

    public function isAssetType(Wallet $wallet, string $assetType): bool
    {
        $detectedAssetType = $this->getAssetType($wallet);
        return $detectedAssetType === $assetType;
    }

    public function findBySlugWithAssetContext(Model $model, string $slug, ?string $assetType = null): ?Wallet
    {
        if ($assetType !== null) {
            // Search in specific asset type
            if (!$this->repositoryFactory->isAssetTypeSupported($assetType)) {
                return null;
            }

            return $this->assetContext->withContext($assetType, function () use ($model, $slug) {
                $walletRepository = $this->repositoryFactory->createWalletRepository();
                return $walletRepository->findBySlug($model->getMorphClass(), $model->getKey(), $slug);
            });
        }

        // Search in all registered asset types
        $assetTypes = $this->repositoryFactory->getAllAssetTypes();

        foreach ($assetTypes as $type) {
            $wallet = $this->assetContext->withContext($type, function () use ($model, $slug) {
                $walletRepository = $this->repositoryFactory->createWalletRepository();
                return $walletRepository->findBySlug($model->getMorphClass(), $model->getKey(), $slug);
            });

            if ($wallet !== null) {
                return $wallet;
            }
        }

        return null;
    }

    public function getBySlugWithAssetContext(Model $model, string $slug, ?string $assetType = null): Wallet
    {
        $wallet = $this->findBySlugWithAssetContext($model, $slug, $assetType);

        if ($wallet === null) {
            throw new ModelNotFoundException("Wallet with slug '{$slug}' not found");
        }

        return $wallet;
    }

    // Standard WalletServiceInterface methods - delegate to default repository

    public function create(Model $model, array $data): Wallet
    {
        $wallet = $this->defaultWalletRepository->create(array_merge(
            config('wallet.wallet.creating', []),
            [
                'uuid' => $this->identifierFactory->generate(),
            ],
            $data,
            [
                'holder_type' => $model->getMorphClass(),
                'holder_id' => $model->getKey(),
            ]
        ));

        $event = $this->walletCreatedEventAssembler->create($wallet);
        $this->dispatcherService->dispatch($event);
        $this->dispatcherService->lazyFlush();

        return $wallet;
    }

    public function findBySlug(Model $model, string $slug): ?Wallet
    {
        return $this->defaultWalletRepository->findBySlug($model->getMorphClass(), $model->getKey(), $slug);
    }

    public function findByUuid(string $uuid): ?Wallet
    {
        return $this->defaultWalletRepository->findByUuid($uuid);
    }

    public function findById(int $id): ?Wallet
    {
        return $this->defaultWalletRepository->findById($id);
    }

    public function getBySlug(Model $model, string $slug): Wallet
    {
        return $this->defaultWalletRepository->getBySlug($model->getMorphClass(), $model->getKey(), $slug);
    }

    public function getByUuid(string $uuid): Wallet
    {
        return $this->defaultWalletRepository->getByUuid($uuid);
    }

    public function getById(int $id): Wallet
    {
        return $this->defaultWalletRepository->getById($id);
    }

    /**
     * Validate required fields for a specific asset type.
     *
     * @param array<string, mixed> $data The wallet creation data
     * @param string $assetType The asset type identifier
     * @throws \InvalidArgumentException If required fields are missing
     */
    private function validateRequiredFields(array $data, string $assetType): void
    {
        $config = $this->repositoryFactory->getAssetConfig($assetType);
        $requiredFields = $config->getMetaValue('required_fields', []);

        if (empty($requiredFields)) {
            return; // No required fields for this asset type
        }

        $meta = $data['meta'] ?? [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($meta[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $missingFieldsList = implode(', ', $missingFields);
            throw new \InvalidArgumentException(
                "Required fields missing for asset type '{$assetType}': {$missingFieldsList}"
            );
        }
    }
}