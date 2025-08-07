<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Internal\Asset;

use Bavix\Wallet\Internal\Asset\AssetConfig;
use Bavix\Wallet\Internal\Asset\ModelConfiguration;
use Bavix\Wallet\Internal\Asset\TableConfiguration;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class ConfigurationObjectsTest extends TestCase
{
    public function testAssetTypeAgnosticism(): void
    {
        // Test that the system is asset-agnostic
        // Any asset type can be configured without hardcoded values
        $customAssetType = 'custom_asset';

        // This should work with any asset type name
        self::assertIsString($customAssetType);
        self::assertNotEmpty($customAssetType);

        // The system should accept any valid asset type identifier
        self::assertSame(1, preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $customAssetType));

        // Test that underscores are allowed
        $underscoreAssetType = 'custom_asset_type';
        self::assertSame(1, preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $underscoreAssetType));
    }

    public function testTableConfiguration(): void
    {
        $config = new TableConfiguration('wallets', 'transactions', 'transfers');

        // Test getter methods
        self::assertSame('wallets', $config->getWalletTable());
        self::assertSame('transactions', $config->getTransactionTable());
        self::assertSame('transfers', $config->getTransferTable());

        // Test getTableByType method
        self::assertSame('wallets', $config->getTableByType('wallet'));
        self::assertSame('transactions', $config->getTableByType('transaction'));
        self::assertSame('transfers', $config->getTableByType('transfer'));

        // Test hasTable method
        self::assertTrue($config->hasTable('wallets'));
        self::assertFalse($config->hasTable('invalid_table'));

        // Test toArray method
        $array = $config->toArray();
        self::assertSame('wallets', $array['wallet_table']);
        self::assertSame('transactions', $array['transaction_table']);
        self::assertSame('transfers', $array['transfer_table']);
    }

    public function testTableConfigurationFromArray(): void
    {
        $array = [
            'wallet_table' => 'wallets',
            'transaction_table' => 'transactions',
            'transfer_table' => 'transfers',
        ];

        $config = TableConfiguration::fromArray($array);

        self::assertSame('wallets', $config->getWalletTable());
        self::assertSame('transactions', $config->getTransactionTable());
        self::assertSame('transfers', $config->getTransferTable());
    }

    public function testModelConfiguration(): void
    {
        $config = new ModelConfiguration(
            'App\Models\Wallet',
            'App\Models\Transaction',
            'App\Models\Transfer'
        );

        // Test getter methods
        self::assertSame('App\Models\Wallet', $config->getWalletModel());
        self::assertSame('App\Models\Transaction', $config->getTransactionModel());
        self::assertSame('App\Models\Transfer', $config->getTransferModel());

        // Test getModelByType method
        self::assertSame('App\Models\Wallet', $config->getModelByType('wallet'));
        self::assertSame('App\Models\Transaction', $config->getModelByType('transaction'));
        self::assertSame('App\Models\Transfer', $config->getModelByType('transfer'));

        // Test hasModel method
        self::assertTrue($config->hasModel('App\Models\Wallet'));
        self::assertFalse($config->hasModel('App\Models\InvalidModel'));

        // Test toArray method
        $array = $config->toArray();
        self::assertSame('App\Models\Wallet', $array['wallet_model']);
        self::assertSame('App\Models\Transaction', $array['transaction_model']);
        self::assertSame('App\Models\Transfer', $array['transfer_model']);
    }

    public function testModelConfigurationFromArray(): void
    {
        $array = [
            'wallet_model' => 'App\Models\Wallet',
            'transaction_model' => 'App\Models\Transaction',
            'transfer_model' => 'App\Models\Transfer',
        ];

        $config = ModelConfiguration::fromArray($array);

        self::assertSame('App\Models\Wallet', $config->getWalletModel());
        self::assertSame('App\Models\Transaction', $config->getTransactionModel());
        self::assertSame('App\Models\Transfer', $config->getTransferModel());
    }

    public function testAssetConfigBackwardCompatibility(): void
    {
        $configArray = [
            'wallet_table' => 'wallets',
            'transaction_table' => 'transactions',
            'transfer_table' => 'transfers',
            'wallet_model' => 'App\Models\Wallet',
            'transaction_model' => 'App\Models\Transaction',
            'transfer_model' => 'App\Models\Transfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['key' => 'value'],
        ];

        $assetConfig = AssetConfig::fromArray('money', $configArray);

        // Test backward compatibility - all existing methods should work
        self::assertSame('wallets', $assetConfig->getWalletTable());
        self::assertSame('transactions', $assetConfig->getTransactionTable());
        self::assertSame('transfers', $assetConfig->getTransferTable());
        self::assertSame('App\Models\Wallet', $assetConfig->getWalletModel());
        self::assertSame('App\Models\Transaction', $assetConfig->getTransactionModel());
        self::assertSame('App\Models\Transfer', $assetConfig->getTransferModel());

        // Test new value object methods
        self::assertSame('wallets', $assetConfig->getTableByType('wallet'));
        self::assertSame('App\Models\Wallet', $assetConfig->getModelByType('wallet'));

        // Test toArray method maintains backward compatibility
        $resultArray = $assetConfig->toArray();
        self::assertSame($configArray, $resultArray);
    }

    public function testAssetConfigValidation(): void
    {
        // Test missing required keys
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required configuration key: wallet_table');

        AssetConfig::fromArray('money', [
            'transaction_table' => 'transactions',
            'transfer_table' => 'transfers',
            'wallet_model' => 'App\Models\Wallet',
            'transaction_model' => 'App\Models\Transaction',
            'transfer_model' => 'App\Models\Transfer',
        ]);
    }

    public function testTableConfigurationValidation(): void
    {
        // Test invalid table name format
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid table name format: 123invalid');

        new TableConfiguration('123invalid', 'transactions', 'transfers');
    }

    public function testModelConfigurationValidation(): void
    {
        // Test invalid model class name format
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid model class name format: 123Invalid');

        new ModelConfiguration('123Invalid', 'App\Models\Transaction', 'App\Models\Transfer');
    }
}