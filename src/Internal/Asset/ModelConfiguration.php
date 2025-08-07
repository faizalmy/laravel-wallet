<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Value object for model configuration in the multi-asset wallet system.
 *
 * This class provides type-safe model class management and validation
 * to ensure consistent model usage across the asset system.
 */
final class ModelConfiguration
{
    /**
     * @param string $walletModel The wallet model class name
     * @param string $transactionModel The transaction model class name
     * @param string $transferModel The transfer model class name
     */
    public function __construct(
        private readonly string $walletModel,
        private readonly string $transactionModel,
        private readonly string $transferModel
    ) {
        $this->validateModelClasses();
    }

    /**
     * Get the wallet model class name.
     *
     * @return string The wallet model class name
     */
    public function getWalletModel(): string
    {
        return $this->walletModel;
    }

    /**
     * Get the transaction model class name.
     *
     * @return string The transaction model class name
     */
    public function getTransactionModel(): string
    {
        return $this->transactionModel;
    }

    /**
     * Get the transfer model class name.
     *
     * @return string The transfer model class name
     */
    public function getTransferModel(): string
    {
        return $this->transferModel;
    }

    /**
     * Get a model class name by type.
     *
     * @param string $type The model type (wallet, transaction, transfer)
     * @return string The model class name
     *
     * @throws \InvalidArgumentException If the model type is invalid
     */
    public function getModelByType(string $type): string
    {
        return match ($type) {
            'wallet' => $this->walletModel,
            'transaction' => $this->transactionModel,
            'transfer' => $this->transferModel,
            default => throw new \InvalidArgumentException("Invalid model type: {$type}"),
        };
    }

    /**
     * Get all model classes as an array.
     *
     * @return array<string, string> Array of model classes indexed by type
     */
    public function getAllModels(): array
    {
        return [
            'wallet' => $this->walletModel,
            'transaction' => $this->transactionModel,
            'transfer' => $this->transferModel,
        ];
    }

    /**
     * Check if a model class exists in this configuration.
     *
     * @param string $modelClass The model class to check
     * @return bool True if the model class exists
     */
    public function hasModel(string $modelClass): bool
    {
        return in_array($modelClass, [
            $this->walletModel,
            $this->transactionModel,
            $this->transferModel,
        ], true);
    }

    /**
     * Validate that all model classes exist and are instantiable.
     *
     * @return bool True if all model classes are valid
     *
     * @throws \InvalidArgumentException If any model class is invalid
     */
    public function validateModelClassesExist(): bool
    {
        $models = [$this->walletModel, $this->transactionModel, $this->transferModel];

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                throw new \InvalidArgumentException("Model class does not exist: {$modelClass}");
            }

            if (!is_subclass_of($modelClass, \Illuminate\Database\Eloquent\Model::class)) {
                throw new \InvalidArgumentException("Model class must extend Eloquent Model: {$modelClass}");
            }
        }

        return true;
    }

    /**
     * Create ModelConfiguration from an array.
     *
     * @param array{
     *     wallet_model: string,
     *     transaction_model: string,
     *     transfer_model: string
     * } $config The configuration array
     * @return self The created ModelConfiguration instance
     *
     * @throws \InvalidArgumentException If required keys are missing
     */
    public static function fromArray(array $config): self
    {
        $requiredKeys = ['wallet_model', 'transaction_model', 'transfer_model'];

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \InvalidArgumentException("Missing required model configuration key: {$key}");
            }
        }

        return new self(
            $config['wallet_model'],
            $config['transaction_model'],
            $config['transfer_model']
        );
    }

    /**
     * Convert the ModelConfiguration to an array.
     *
     * @return array{
     *     wallet_model: string,
     *     transaction_model: string,
     *     transfer_model: string
     * } The configuration array
     */
    public function toArray(): array
    {
        return [
            'wallet_model' => $this->walletModel,
            'transaction_model' => $this->transactionModel,
            'transfer_model' => $this->transferModel,
        ];
    }

    /**
     * Validate model class names.
     *
     * @throws \InvalidArgumentException If model class names are invalid
     */
    private function validateModelClasses(): void
    {
        $models = [$this->walletModel, $this->transactionModel, $this->transferModel];

        foreach ($models as $modelClass) {
            if (empty($modelClass)) {
                throw new \InvalidArgumentException('Model class name cannot be empty');
            }

            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\\\]*$/', $modelClass)) {
                throw new \InvalidArgumentException("Invalid model class name format: {$modelClass}");
            }
        }

        // Check for duplicate model classes
        $uniqueModels = array_unique($models);
        if (count($uniqueModels) !== count($models)) {
            throw new \InvalidArgumentException('Model class names must be unique');
        }
    }
}