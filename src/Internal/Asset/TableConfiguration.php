<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Value object for table configuration in the multi-asset wallet system.
 *
 * This class provides type-safe table name management and validation
 * to ensure consistent table naming across the asset system.
 */
final class TableConfiguration
{
    /**
     * @param string $walletTable The wallet table name
     * @param string $transactionTable The transaction table name
     * @param string $transferTable The transfer table name
     */
    public function __construct(
        private readonly string $walletTable,
        private readonly string $transactionTable,
        private readonly string $transferTable
    ) {
        $this->validateTableNames();
    }

    /**
     * Get the wallet table name.
     *
     * @return string The wallet table name
     */
    public function getWalletTable(): string
    {
        return $this->walletTable;
    }

    /**
     * Get the transaction table name.
     *
     * @return string The transaction table name
     */
    public function getTransactionTable(): string
    {
        return $this->transactionTable;
    }

    /**
     * Get the transfer table name.
     *
     * @return string The transfer table name
     */
    public function getTransferTable(): string
    {
        return $this->transferTable;
    }

    /**
     * Get a table name by type.
     *
     * @param string $type The table type (wallet, transaction, transfer)
     * @return string The table name
     *
     * @throws \InvalidArgumentException If the table type is invalid
     */
    public function getTableByType(string $type): string
    {
        return match ($type) {
            'wallet' => $this->walletTable,
            'transaction' => $this->transactionTable,
            'transfer' => $this->transferTable,
            default => throw new \InvalidArgumentException("Invalid table type: {$type}"),
        };
    }

    /**
     * Get all table names as an array.
     *
     * @return array<string, string> Array of table names indexed by type
     */
    public function getAllTables(): array
    {
        return [
            'wallet' => $this->walletTable,
            'transaction' => $this->transactionTable,
            'transfer' => $this->transferTable,
        ];
    }

    /**
     * Check if a table name exists in this configuration.
     *
     * @param string $tableName The table name to check
     * @return bool True if the table name exists
     */
    public function hasTable(string $tableName): bool
    {
        return in_array($tableName, [
            $this->walletTable,
            $this->transactionTable,
            $this->transferTable,
        ], true);
    }

    /**
     * Create TableConfiguration from an array.
     *
     * @param array{
     *     wallet_table: string,
     *     transaction_table: string,
     *     transfer_table: string
     * } $config The configuration array
     * @return self The created TableConfiguration instance
     *
     * @throws \InvalidArgumentException If required keys are missing
     */
    public static function fromArray(array $config): self
    {
        $requiredKeys = ['wallet_table', 'transaction_table', 'transfer_table'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \InvalidArgumentException("Missing required table configuration key: {$key}");
            }
        }

        return new self(
            $config['wallet_table'],
            $config['transaction_table'],
            $config['transfer_table']
        );
    }

    /**
     * Convert the TableConfiguration to an array.
     *
     * @return array{
     *     wallet_table: string,
     *     transaction_table: string,
     *     transfer_table: string
     * } The configuration array
     */
    public function toArray(): array
    {
        return [
            'wallet_table' => $this->walletTable,
            'transaction_table' => $this->transactionTable,
            'transfer_table' => $this->transferTable,
        ];
    }

    /**
     * Validate table names.
     *
     * @throws \InvalidArgumentException If table names are invalid
     */
    private function validateTableNames(): void
    {
        $tables = [$this->walletTable, $this->transactionTable, $this->transferTable];

        foreach ($tables as $table) {
            if (empty($table)) {
                throw new \InvalidArgumentException('Table name cannot be empty');
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
                throw new \InvalidArgumentException("Invalid table name format: {$table}");
            }
        }

        // Check for duplicate table names
        $uniqueTables = array_unique($tables);
        if (count($uniqueTables) !== count($tables)) {
            throw new \InvalidArgumentException('Table names must be unique');
        }
    }
}