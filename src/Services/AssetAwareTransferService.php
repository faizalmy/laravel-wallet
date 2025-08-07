<?php

declare(strict_types=1);

namespace Bavix\Wallet\Services;

use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Assembler\TransferDtoAssemblerInterface;
use Bavix\Wallet\Internal\Dto\TransferLazyDtoInterface;
use Bavix\Wallet\Internal\Exceptions\ExceptionInterface;
use Bavix\Wallet\Internal\Exceptions\RecordNotFoundException;
use Bavix\Wallet\Internal\Exceptions\TransactionFailedException;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Internal\Service\DatabaseServiceInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;
use Illuminate\Database\RecordsNotFoundException;

/**
 * Asset-aware transfer service that extends TransferService to provide
 * transparent asset type detection and routing for transfer operations.
 */
readonly class AssetAwareTransferService extends TransferService
{
    public function __construct(
        TransferDtoAssemblerInterface $transferDtoAssembler,
        TransferRepositoryInterface $transferRepository,
        TransactionServiceInterface $transactionService,
        DatabaseServiceInterface $databaseService,
        CastServiceInterface $castService,
        AtmServiceInterface $atmService,
        private readonly AssetTypeDetector $assetTypeDetector,
        private readonly AssetContextInterface $assetContext,
        private readonly AssetRepositoryFactoryInterface $repositoryFactory
    ) {
        parent::__construct(
            $transferDtoAssembler,
            $transferRepository,
            $transactionService,
            $databaseService,
            $castService,
            $atmService
        );
    }

    /**
     * Apply transfers with automatic asset type detection.
     *
     * @param non-empty-array<TransferLazyDtoInterface> $objects
     * @return non-empty-array<string, Transfer>
     *
     * @throws RecordNotFoundException
     * @throws RecordsNotFoundException
     * @throws TransactionFailedException
     * @throws ExceptionInterface
     */
    public function apply(array $objects): array
    {
        // Detect asset type from the first transfer object's wallets
        $firstObject = reset($objects);
        $fromWallet = $this->castService->getWallet($firstObject->getFromWallet());
        $detectedAssetType = $this->assetTypeDetector->detect($fromWallet);

        if ($detectedAssetType !== null) {
            // Use detected asset context for the entire transfer operation
            return $this->assetContext->withContext($detectedAssetType, function () use ($objects) {
                return parent::apply($objects);
            });
        }

        // Fall back to default behavior if no asset type detected
        return parent::apply($objects);
    }

    /**
     * Update transfer status by IDs using asset-aware repository.
     *
     * @param int[] $ids
     */
    public function updateStatusByIds(string $status, array $ids): bool
    {
        // Get current asset context
        $assetType = $this->assetContext->getContext();

        if ($assetType !== null) {
            // Use asset-aware repository for status updates
            $transferRepository = $this->repositoryFactory->createTransferRepository($assetType);
            return $ids !== [] && count($ids) === $transferRepository->updateStatusByIds($status, $ids);
        }

        // Fall back to default behavior
        return parent::updateStatusByIds($status, $ids);
    }

    /**
     * Get the current asset type detector for debugging.
     *
     * @return AssetTypeDetector The asset type detector
     */
    public function getAssetTypeDetector(): AssetTypeDetector
    {
        return $this->assetTypeDetector;
    }

    /**
     * Get the current asset context for debugging.
     *
     * @return AssetContextInterface The asset context
     */
    public function getAssetContext(): AssetContextInterface
    {
        return $this->assetContext;
    }

    /**
     * Get the current asset repository factory for debugging.
     *
     * @return AssetRepositoryFactoryInterface The asset repository factory
     */
    public function getAssetRepositoryFactory(): AssetRepositoryFactoryInterface
    {
        return $this->repositoryFactory;
    }
}