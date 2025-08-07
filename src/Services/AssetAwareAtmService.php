<?php

declare(strict_types=1);

namespace Bavix\Wallet\Services;

use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;
use Bavix\Wallet\Internal\Assembler\TransactionQueryAssemblerInterface;
use Bavix\Wallet\Internal\Assembler\TransferQueryAssemblerInterface;
use Bavix\Wallet\Internal\Dto\TransactionDtoInterface;
use Bavix\Wallet\Internal\Dto\TransferDtoInterface;
use Bavix\Wallet\Internal\Repository\TransactionRepositoryInterface;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;

/**
 * Asset-aware ATM service that creates context-aware repositories for multi-asset operations.
 *
 * This service extends the functionality of AtmService by using AssetRepositoryFactory
 * to create repositories that are aware of the current asset context, ensuring that
 * transactions and transfers are created in the correct asset-specific tables.
 */
readonly class AssetAwareAtmService implements AtmServiceInterface
{
    public function __construct(
        private TransactionQueryAssemblerInterface $transactionQueryAssembler,
        private TransferQueryAssemblerInterface $transferQueryAssembler,
        private AssetRepositoryFactoryInterface $repositoryFactory,
        private AssistantServiceInterface $assistantService
    ) {
    }

    /**
     * Create transactions using asset-aware repositories.
     *
     * @param non-empty-array<array-key, TransactionDtoInterface> $objects
     * @return non-empty-array<string, Transaction>
     */
    public function makeTransactions(array $objects): array
    {
        try {
            // Try to create asset-aware transaction repository based on current context
            $transactionRepository = $this->repositoryFactory->createTransactionRepository();
        } catch (\InvalidArgumentException $e) {
            // If no asset context is set, fall back to default repository
            // This maintains backward compatibility for single-asset operations
            $transactionRepository = $this->repositoryFactory->createTransactionRepository(null);
        }

        if (count($objects) === 1) {
            $items = [$transactionRepository->insertOne(reset($objects))];
        } else {
            $transactionRepository->insert($objects);
            $uuids = $this->assistantService->getUuids($objects);
            $query = $this->transactionQueryAssembler->create($uuids);
            $items = $transactionRepository->findBy($query);
        }

        assert($items !== []);

        $results = [];
        foreach ($items as $item) {
            $results[$item->uuid] = $item;
        }

        return $results;
    }

    /**
     * Create transfers using asset-aware repositories.
     *
     * @param non-empty-array<array-key, TransferDtoInterface> $objects
     * @return non-empty-array<string, Transfer>
     */
    public function makeTransfers(array $objects): array
    {
        try {
            // Try to create asset-aware transfer repository based on current context
            $transferRepository = $this->repositoryFactory->createTransferRepository();
        } catch (\InvalidArgumentException $e) {
            // If no asset context is set, fall back to default repository
            // This maintains backward compatibility for single-asset operations
            $transferRepository = $this->repositoryFactory->createTransferRepository(null);
        }

        if (count($objects) === 1) {
            $items = [$transferRepository->insertOne(reset($objects))];
        } else {
            $transferRepository->insert($objects);
            $uuids = $this->assistantService->getUuids($objects);
            $query = $this->transferQueryAssembler->create($uuids);
            $items = $transferRepository->findBy($query);
        }

        assert($items !== []);

        $results = [];
        foreach ($items as $item) {
            $results[$item->uuid] = $item;
        }

        return $results;
    }
}