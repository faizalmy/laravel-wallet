<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Internal\Asset;

use Bavix\Wallet\Internal\Asset\AssetConfig;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class AssetConfigTest extends TestCase
{
    public function testCreateAssetConfig(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Bond assets']
        ]);

        self::assertSame('bonds', $config->getAssetType());
        self::assertSame('bond_wallets', $config->getWalletTable());
        self::assertSame('bond_transactions', $config->getTransactionTable());
        self::assertSame('bond_transfers', $config->getTransferTable());
        self::assertSame('App\Models\BondWallet', $config->getWalletModel());
        self::assertSame('App\Models\BondTransaction', $config->getTransactionModel());
        self::assertSame('App\Models\BondTransfer', $config->getTransferModel());
        self::assertSame('/path/to/migrations', $config->getMigrationPath());
        self::assertSame(['description' => 'Bond assets'], $config->getMeta());
    }

    public function testCreateFromArray(): void
    {
        $configArray = [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Bond assets'],
        ];

        $config = AssetConfig::fromArray('bonds', $configArray);

        self::assertSame('bonds', $config->getAssetType());
        self::assertSame('bond_wallets', $config->getWalletTable());
        self::assertSame('bond_transactions', $config->getTransactionTable());
        self::assertSame('bond_transfers', $config->getTransferTable());
        self::assertSame('App\Models\BondWallet', $config->getWalletModel());
        self::assertSame('App\Models\BondTransaction', $config->getTransactionModel());
        self::assertSame('App\Models\BondTransfer', $config->getTransferModel());
        self::assertSame('/path/to/migrations', $config->getMigrationPath());
        self::assertSame(['description' => 'Bond assets'], $config->getMeta());
    }

    public function testCreateFromArrayWithMissingRequiredKeys(): void
    {
        $configArray = [
            'wallet_table' => 'bond_wallets',
            // Missing required keys
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration key: transaction_table');

        AssetConfig::fromArray('bonds', $configArray);
    }

    public function testToArray(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Bond assets']
        ]);

        $expected = [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Bond assets'],
        ];

        self::assertSame($expected, $config->toArray());
    }

    public function testIsDefault(): void
    {
        $defaultConfig = AssetConfig::fromArray('default', [
            'wallet_table' => 'wallets',
            'transaction_table' => 'transactions',
            'transfer_table' => 'transfers',
            'wallet_model' => 'Bavix\Wallet\Models\Wallet',
            'transaction_model' => 'Bavix\Wallet\Models\Transaction',
            'transfer_model' => 'Bavix\Wallet\Models\Transfer'
        ]);

        $customConfig = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        self::assertTrue($defaultConfig->isDefault());
        self::assertFalse($customConfig->isDefault());
    }

    public function testGetTableByType(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        self::assertSame('bond_wallets', $config->getTableByType('wallet'));
        self::assertSame('bond_transactions', $config->getTableByType('transaction'));
        self::assertSame('bond_transfers', $config->getTableByType('transfer'));
    }

    public function testGetTableByTypeWithInvalidType(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table type: invalid');

        $config->getTableByType('invalid');
    }

    public function testGetModelByType(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        self::assertSame('App\Models\BondWallet', $config->getModelByType('wallet'));
        self::assertSame('App\Models\BondTransaction', $config->getModelByType('transaction'));
        self::assertSame('App\Models\BondTransfer', $config->getModelByType('transfer'));
    }

    public function testGetModelByTypeWithInvalidType(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid model type: invalid');

        $config->getModelByType('invalid');
    }

    public function testGetMetaValue(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'meta' => ['description' => 'Bond assets', 'category' => 'financial']
        ]);

        self::assertSame('Bond assets', $config->getMetaValue('description'));
        self::assertSame('financial', $config->getMetaValue('category'));
        self::assertNull($config->getMetaValue('nonexistent'));
        self::assertSame('default', $config->getMetaValue('nonexistent', 'default'));
    }

    public function testHasMetaValue(): void
    {
        $config = AssetConfig::fromArray('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'meta' => ['description' => 'Bond assets']
        ]);

        self::assertTrue($config->hasMetaValue('description'));
        self::assertFalse($config->hasMetaValue('nonexistent'));
    }
}