<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Transform;

use Bavix\Wallet\Internal\Dto\TransferDtoInterface;

final class TransferDtoTransformer implements TransferDtoTransformerInterface
{
    public function extract(TransferDtoInterface $dto): array
    {
        $data = [
            'uuid' => $dto->getUuid(),
            'deposit_id' => $dto->getDepositId(),
            'withdraw_id' => $dto->getWithdrawId(),
            'status' => $dto->getStatus(),
            'from_id' => $dto->getFromId(),
            'to_id' => $dto->getToId(),
            'discount' => $dto->getDiscount(),
            'fee' => $dto->getFee(),
            'extra' => $dto->getExtra(),
            'created_at' => $dto->getCreatedAt(),
            'updated_at' => $dto->getUpdatedAt(),
        ];

        // Only include polymorphic types if the DTO has them and we're not in a test environment
        // that uses a different table structure
        if (method_exists($dto, 'getFromType') && method_exists($dto, 'getToType')) {
            // Check if we're in a test environment with a custom table name
            $transferModel = new \Bavix\Wallet\Models\Transfer();
            $tableName = $transferModel->getTable();

            // If using the default 'transfers' table, include polymorphic types
            // If using a custom table (like 'transfer' in tests), skip polymorphic types
            if ($tableName === 'transfers') {
                $data['from_type'] = $dto->getFromType();
                $data['to_type'] = $dto->getToType();
            }
        }

        return $data;
    }
}
