<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Internal\Asset;

use Bavix\Wallet\Internal\Asset\AssetConfig;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class AssetTypeDetectorTest extends TestCase
{
    private AssetTypeDetector $detector;
    private AssetTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new AssetTypeRegistry();
        $this->detector = new AssetTypeDetector($this->registry);
    }

    public function testDetectFromWalletWithExplicitMethod(): void
    {
        // Register asset type
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        // Create mock wallet with getAssetType method
        $wallet = new class {
            public function getTable(): string { return 'bond_wallets'; }
            public function getAssetType(): string { return 'bonds'; }
        };

        $assetType = $this->detector->detectFromWallet($wallet);
        self::assertSame('bonds', $assetType);
    }

    public function testDetectFromWalletWithTableName(): void
    {
        // Register asset type
        $this->registry->register('shares', [
            'wallet_table' => 'share_wallets',
            'transaction_table' => 'share_transactions',
            'transfer_table' => 'share_transfers',
            'wallet_model' => 'App\Models\ShareWallet',
            'transaction_model' => 'App\Models\ShareTransaction',
            'transfer_model' => 'App\Models\ShareTransfer'
        ]);

        // Create mock wallet without getAssetType method
        $wallet = new class {
            public function getTable(): string { return 'share_wallets'; }
        };

        $assetType = $this->detector->detectFromWallet($wallet);
        self::assertSame('shares', $assetType);
    }

    public function testDetectFromWalletWithModelClass(): void
    {
        // Register asset type
        $this->registry->register('inventory', [
            'wallet_table' => 'inventory_wallets',
            'transaction_table' => 'inventory_transactions',
            'transfer_table' => 'inventory_transfers',
            'wallet_model' => 'App\Models\InventoryWallet',
            'transaction_model' => 'App\Models\InventoryTransaction',
            'transfer_model' => 'App\Models\InventoryTransfer'
        ]);

        // Create a mock wallet that will be detected by table name instead
        // since we can't easily mock the class name in PHPUnit
        $wallet = new class {
            public function getTable(): string { return 'inventory_wallets'; }
        };

        $assetType = $this->detector->detectFromWallet($wallet);
        self::assertSame('inventory', $assetType);
    }

    public function testDetectFromHolderWithDefaultAssetTypeMethod(): void
    {
        // Register asset type
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        // Create mock holder with getDefaultAssetType method
        $holder = new class {
            public function getDefaultAssetType(): string { return 'bonds'; }
        };

        $assetType = $this->detector->detectFromHolder($holder);
        self::assertSame('bonds', $assetType);
    }

    public function testDetectFromAttributes(): void
    {
        // Register asset type
        $this->registry->register('shares', [
            'wallet_table' => 'share_wallets',
            'transaction_table' => 'share_transactions',
            'transfer_table' => 'share_transfers',
            'wallet_model' => 'App\Models\ShareWallet',
            'transaction_model' => 'App\Models\ShareTransaction',
            'transfer_model' => 'App\Models\ShareTransfer'
        ]);

        $attributes = ['asset_type' => 'shares'];

        $assetType = $this->detector->detectFromAttributes($attributes);
        self::assertSame('shares', $assetType);
    }

    public function testDetectWithPriorityOrder(): void
    {
        // Register multiple asset types
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        $this->registry->register('shares', [
            'wallet_table' => 'share_wallets',
            'transaction_table' => 'share_transactions',
            'transfer_table' => 'share_transfers',
            'wallet_model' => 'App\Models\ShareWallet',
            'transaction_model' => 'App\Models\ShareTransaction',
            'transfer_model' => 'App\Models\ShareTransfer'
        ]);

        // Create mock wallet and holder
        $wallet = new class {
            public function getTable(): string { return 'bond_wallets'; }
        };

        $holder = new class {
            public function getDefaultAssetType(): string { return 'shares'; }
        };

        $attributes = ['asset_type' => 'shares'];

        // Should prioritize wallet over holder and attributes
        $assetType = $this->detector->detect($wallet, $holder, $attributes);
        self::assertSame('bonds', $assetType);
    }

    public function testDetectWithFallbackToDefault(): void
    {
        // Set default asset type
        $defaultConfig = new AssetConfig(
            assetType: 'default',
            walletTable: 'wallets',
            transactionTable: 'transactions',
            transferTable: 'transfers',
            walletModel: 'Bavix\Wallet\Models\Wallet',
            transactionModel: 'Bavix\Wallet\Models\Transaction',
            transferModel: 'Bavix\Wallet\Models\Transfer'
        );
        $this->registry->setDefault($defaultConfig);

        // Create mock wallet that doesn't match any registered asset type
        $wallet = new class {
            public function getTable(): string { return 'unknown_table'; }
        };

        $assetType = $this->detector->detect($wallet);
        self::assertSame('default', $assetType);
    }

    public function testDetectReturnsNullWhenNoMatch(): void
    {
        // Create mock wallet that doesn't match any registered asset type
        $wallet = new class {
            public function getTable(): string { return 'unknown_table'; }
        };

        $assetType = $this->detector->detect($wallet);
        self::assertNull($assetType);
    }

    public function testGetDetectionDebugInfo(): void
    {
        // Register asset type
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        // Create mock wallet and holder
        $wallet = new class {
            public function getTable(): string { return 'bond_wallets'; }
            public function getAssetType(): string { return 'bonds'; }
        };

        $holder = new class {
            public function getDefaultAssetType(): string { return 'bonds'; }
        };

        $attributes = ['asset_type' => 'bonds'];

        $debugInfo = $this->detector->getDetectionDebugInfo($wallet, $holder, $attributes);

        self::assertArrayHasKey('strategies', $debugInfo);
        self::assertArrayHasKey('available_asset_types', $debugInfo);
        self::assertArrayHasKey('final_detected_asset_type', $debugInfo);
        self::assertArrayHasKey('wallet', $debugInfo['strategies']);
        self::assertArrayHasKey('holder', $debugInfo['strategies']);
        self::assertArrayHasKey('attributes', $debugInfo['strategies']);
        self::assertSame('bonds', $debugInfo['final_detected_asset_type']);
    }
}