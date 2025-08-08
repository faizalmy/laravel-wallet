<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Service;

use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;

/**
 * Model Resolver Interface
 *
 * Provides agnostic model resolution for multi-asset systems.
 * Allows applications to dynamically resolve transaction and transfer models
 * based on wallet type without hardcoding specific wallet types in Bavix.
 */
interface ModelResolverInterface
{
    /**
     * Resolve the correct transaction model based on wallet ID
     *
     * @param int $walletId The wallet ID to resolve the transaction model for
     * @return Transaction The appropriate transaction model instance
     */
    public function resolveTransactionModel(int $walletId): Transaction;

    /**
     * Resolve the correct transaction model based on transaction attributes (including meta)
     *
     * @param array $attributes The transaction attributes including meta information
     * @return Transaction The appropriate transaction model instance
     */
    public function resolveTransactionModelByMeta(array $attributes): Transaction;



    /**
     * Resolve the correct transfer model based on transaction IDs
     *
     * @param int $depositId The deposit transaction ID
     * @param int $withdrawId The withdraw transaction ID
     * @return Transfer The appropriate transfer model instance
     */
    public function resolveTransferModelByTransactionIds(int $depositId, int $withdrawId): Transfer;

    /**
     * Find transactions by UUIDs across all transaction tables
     *
     * @param array $uuids Array of transaction UUIDs to find
     * @return array Array of found transaction models
     */
    public function findTransactionsByUuids(array $uuids): array;

    /**
     * Find transfers by UUIDs across all transfer tables
     *
     * @param array $uuids Array of transfer UUIDs to find
     * @return array Array of found transfer models
     */
    public function findTransfersByUuids(array $uuids): array;

    /**
     * Get additional attributes for a transaction based on wallet type
     *
     * @param int $walletId The wallet ID
     * @param array $baseAttributes The base transaction attributes
     * @return array Additional attributes to inject into the transaction
     */
    public function getTransactionAttributes(int $walletId, array $baseAttributes): array;

    /**
     * Get additional attributes for a transfer based on wallet type
     *
     * @param int $fromWalletId The source wallet ID
     * @param int $toWalletId The destination wallet ID
     * @param array $baseAttributes The base transfer attributes
     * @return array Additional attributes to inject into the transfer
     */
    public function getTransferAttributes(int $fromWalletId, int $toWalletId, array $baseAttributes): array;
}
