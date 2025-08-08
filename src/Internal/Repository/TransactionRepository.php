<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Repository;

use Bavix\Wallet\Internal\Dto\TransactionDtoInterface;
use Bavix\Wallet\Internal\Query\TransactionQueryInterface;
use Bavix\Wallet\Internal\Service\JsonServiceInterface;
use Bavix\Wallet\Internal\Service\ModelResolverInterface;
use Bavix\Wallet\Internal\Transform\TransactionDtoTransformerInterface;
use Bavix\Wallet\Models\Transaction;

final readonly class TransactionRepository implements TransactionRepositoryInterface
{
    public function __construct(
        private TransactionDtoTransformerInterface $transformer,
        private JsonServiceInterface $jsonService,
        private Transaction $transaction,
        private ?ModelResolverInterface $modelResolver = null
    ) {
    }

    /**
     * @param non-empty-array<int|string, TransactionDtoInterface> $objects
     */
    public function insert(array $objects): void
    {
        if ($this->modelResolver === null) {
            // Fallback to original behavior
            $values = [];
            foreach ($objects as $object) {
                $values[] = array_map(
                    fn ($value) => is_array($value) ? $this->jsonService->encode($value) : $value,
                    $this->transformer->extract($object)
                );
            }

            $this->transaction->newQuery()
                ->insert($values);
        } else {
            // Use model resolver for each transaction
            foreach ($objects as $object) {
                $this->insertOne($object);
            }
        }
    }

    public function insertOne(TransactionDtoInterface $dto): Transaction
    {
        $attributes = $this->transformer->extract($dto);

        if ($this->modelResolver !== null) {
            // Use dynamic model resolution based on meta
            $additionalAttributes = $this->modelResolver->getTransactionAttributes(
                $attributes['wallet_id'],
                $attributes
            );
            $attributes = array_merge($attributes, $additionalAttributes);

            // Use meta-based resolution if available, otherwise fall back to ID-based
            if (isset($attributes['meta']['wallet_type'])) {
                $transactionModel = $this->modelResolver->resolveTransactionModelByMeta($attributes);
            } else {
                $transactionModel = $this->modelResolver->resolveTransactionModel($attributes['wallet_id']);
            }
            $instance = $transactionModel->newInstance($attributes);
        } else {
            // Fallback to original behavior
            $instance = $this->transaction->newInstance($attributes);
        }

        $instance->saveQuietly();

        return $instance;
    }

    /**
     * @return Transaction[]
     */
    public function findBy(TransactionQueryInterface $query): array
    {
        if ($this->modelResolver === null) {
            // Fallback to original behavior
            return $this->transaction->newQuery()
                ->whereIn('uuid', $query->getUuids())
                ->get()
                ->all();
        }

        // Use resolver to search across all transaction tables
        return $this->modelResolver->findTransactionsByUuids($query->getUuids());
    }
}
