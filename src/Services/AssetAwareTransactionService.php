<?php

declare(strict_types=1);

namespace Bavix\Wallet\Services;

use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Assembler\TransactionCreatedEventAssemblerInterface;
use Bavix\Wallet\Internal\Dto\TransactionDtoInterface;
use Bavix\Wallet\Internal\Exceptions\RecordNotFoundException;
use Bavix\Wallet\Internal\Service\DispatcherServiceInterface;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Models\Transaction;

/**
 * Asset-aware transaction service that extends TransactionService to provide
 * transparent asset type detection and routing.
 */
final readonly class AssetAwareTransactionService extends TransactionService
{
    public function __construct(
        TransactionCreatedEventAssemblerInterface $transactionCreatedEventAssembler,
        DispatcherServiceInterface $dispatcherService,
        AssistantServiceInterface $assistantService,
        RegulatorServiceInterface $regulatorService,
        PrepareServiceInterface $prepareService,
        CastServiceInterface $castService,
        AtmServiceInterface $atmService,
        private readonly AssetTypeDetector $assetTypeDetector,
        private readonly AssetContextInterface $assetContext,
        private readonly AssetRepositoryFactoryInterface $repositoryFactory
    ) {
        parent::__construct(
            $transactionCreatedEventAssembler,
            $dispatcherService,
            $assistantService,
            $regulatorService,
            $prepareService,
            $castService,
            $atmService
        );
    }

    /**
     * Make a transaction with automatic asset type detection.
     *
     * @param Wallet $wallet The wallet
     * @param string $type The transaction type
     * @param float|int|string $amount The amount
     * @param array<string, mixed>|null $meta Additional metadata
     * @param bool $confirmed Whether the transaction is confirmed
     * @return Transaction The created transaction
     * @throws RecordNotFoundException
     */
    public function makeOne(
        Wallet $wallet,
        string $type,
        float|int|string $amount,
        ?array $meta,
        bool $confirmed = true
    ): Transaction {
        // Detect asset type from wallet
        $assetType = $this->assetTypeDetector->detect($wallet);
        
        if ($assetType !== null) {
            // Use asset context to set the detected asset type
            return $this->assetContext->withContext($assetType, function () use ($wallet, $type, $amount, $meta, $confirmed) {
                return parent::makeOne($wallet, $type, $amount, $meta, $confirmed);
            });
        }

        // Fall back to default behavior
        return parent::makeOne($wallet, $type, $amount, $meta, $confirmed);
    }

    /**
     * Apply transactions with automatic asset type detection.
     *
     * @param array<int, Wallet> $wallets The wallets
     * @param array<int, TransactionDtoInterface> $objects The transaction DTOs
     * @return array<string, Transaction> The created transactions
     * @throws RecordNotFoundException
     */
    public function apply(array $wallets, array $objects): array
    {
        // Detect asset type from first wallet (assuming all wallets are same asset type)
        $firstWallet = reset($wallets);
        $assetType = $firstWallet ? $this->assetTypeDetector->detect($firstWallet) : null;
        
        if ($assetType !== null) {
            // Use asset context to set the detected asset type
            return $this->assetContext->withContext($assetType, function () use ($wallets, $objects) {
                return parent::apply($wallets, $objects);
            });
        }

        // Fall back to default behavior
        return parent::apply($wallets, $objects);
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