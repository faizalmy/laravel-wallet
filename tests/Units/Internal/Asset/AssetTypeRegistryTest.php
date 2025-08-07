<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Internal\Asset;

use Bavix\Wallet\Internal\Asset\AssetConfig;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class AssetTypeRegistryTest extends TestCase
{
    private AssetTypeRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new AssetTypeRegistry();
    }

    public function testRegisterAssetTypeFromArray(): void
    {
        $config = [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Bond assets']
        ];

        $this->registry->register('bonds', $config);

        self::assertTrue($this->registry->has('bonds'));
        self::assertSame('bonds', $this->registry->resolveFromModel('App\Models\BondWallet'));
        self::assertSame('bonds', $this->registry->resolveFromTable('bond_wallets'));
    }

    public function testRegisterAssetTypeFromConfig(): void
    {
        $config = AssetConfig::fromArray('shares', [
            'wallet_table' => 'share_wallets',
            'transaction_table' => 'share_transactions',
            'transfer_table' => 'share_transfers',
            'wallet_model' => 'App\Models\ShareWallet',
            'transaction_model' => 'App\Models\ShareTransaction',
            'transfer_model' => 'App\Models\ShareTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Share assets']
        ]);

        $this->registry->registerConfig($config);

        self::assertTrue($this->registry->has('shares'));
        self::assertSame('shares', $this->registry->resolveFromModel('App\Models\ShareWallet'));
        self::assertSame('shares', $this->registry->resolveFromTable('share_wallets'));
    }

    public function testGetAssetConfig(): void
    {
        $config = [
            'wallet_table' => 'inventory_wallets',
            'transaction_table' => 'inventory_transactions',
            'transfer_table' => 'inventory_transfers',
            'wallet_model' => 'App\Models\InventoryWallet',
            'transaction_model' => 'App\Models\InventoryTransaction',
            'transfer_model' => 'App\Models\InventoryTransfer'
        ];

        $this->registry->register('inventory', $config);

        $retrievedConfig = $this->registry->get('inventory');
        self::assertNotNull($retrievedConfig);
        self::assertSame('inventory', $retrievedConfig->getAssetType());
        self::assertSame('inventory_wallets', $retrievedConfig->getWalletTable());
    }

    public function testGetNonExistentAssetConfig(): void
    {
        $config = $this->registry->get('nonexistent');
        self::assertNull($config);
    }

    public function testGetAllAssetTypes(): void
    {
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

        $allTypes = $this->registry->getAll();
        self::assertCount(2, $allTypes);
        self::assertArrayHasKey('bonds', $allTypes);
        self::assertArrayHasKey('shares', $allTypes);
    }

    public function testRemoveAssetType(): void
    {
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        self::assertTrue($this->registry->has('bonds'));

        $removed = $this->registry->remove('bonds');
        self::assertTrue($removed);
        self::assertFalse($this->registry->has('bonds'));
        self::assertNull($this->registry->resolveFromModel('App\Models\BondWallet'));
    }

    public function testRemoveNonExistentAssetType(): void
    {
        $removed = $this->registry->remove('nonexistent');
        self::assertFalse($removed);
    }

    public function testClearAllAssetTypes(): void
    {
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

        self::assertCount(2, $this->registry->getAll());

        $this->registry->clear();

        self::assertCount(0, $this->registry->getAll());
        self::assertFalse($this->registry->has('bonds'));
        self::assertFalse($this->registry->has('shares'));
    }

    public function testSetAndGetDefaultAssetType(): void
    {
        $defaultConfig = AssetConfig::fromArray('default', [
            'wallet_table' => 'wallets',
            'transaction_table' => 'transactions',
            'transfer_table' => 'transfers',
            'wallet_model' => 'Bavix\Wallet\Models\Wallet',
            'transaction_model' => 'Bavix\Wallet\Models\Transaction',
            'transfer_model' => 'Bavix\Wallet\Models\Transfer'
        ]);

        $this->registry->setDefault($defaultConfig);

        $retrievedDefault = $this->registry->getDefault();
        self::assertNotNull($retrievedDefault);
        self::assertSame('default', $retrievedDefault->getAssetType());
    }

    public function testResolveAssetTypeWithFallback(): void
    {
        $defaultConfig = AssetConfig::fromArray('default', [
            'wallet_table' => 'wallets',
            'transaction_table' => 'transactions',
            'transfer_table' => 'transfers',
            'wallet_model' => 'Bavix\Wallet\Models\Wallet',
            'transaction_model' => 'Bavix\Wallet\Models\Transaction',
            'transfer_model' => 'Bavix\Wallet\Models\Transfer'
        ]);

        $this->registry->setDefault($defaultConfig);

        // Test fallback to default when model not found
        $assetType = $this->registry->getAssetTypeByModel('Unknown\Model');
        self::assertSame('default', $assetType);

        // Test fallback to default when table not found
        $assetType = $this->registry->getAssetTypeByTable('unknown_table');
        self::assertSame('default', $assetType);
    }

    public function testLoadFromConfig(): void
    {
        $assetsConfig = [
            'bonds' => [
                'wallet_table' => 'bond_wallets',
                'transaction_table' => 'bond_transactions',
                'transfer_table' => 'bond_transfers',
                'wallet_model' => 'App\Models\BondWallet',
                'transaction_model' => 'App\Models\BondTransaction',
                'transfer_model' => 'App\Models\BondTransfer'
            ],
            'shares' => [
                'wallet_table' => 'share_wallets',
                'transaction_table' => 'share_transactions',
                'transfer_table' => 'share_transfers',
                'wallet_model' => 'App\Models\ShareWallet',
                'transaction_model' => 'App\Models\ShareTransaction',
                'transfer_model' => 'App\Models\ShareTransfer'
            ]
        ];

        $this->registry->loadFromConfig($assetsConfig);

        self::assertTrue($this->registry->has('bonds'));
        self::assertTrue($this->registry->has('shares'));
        self::assertCount(2, $this->registry->getAll());
    }

    public function testGetAssetTypeNames(): void
    {
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

        $names = $this->registry->getAssetTypeNames();
        self::assertContains('bonds', $names);
        self::assertContains('shares', $names);
        self::assertCount(2, $names);
    }

    public function testCountAndIsEmpty(): void
    {
        self::assertSame(0, $this->registry->count());
        self::assertTrue($this->registry->isEmpty());

        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        self::assertSame(1, $this->registry->count());
        self::assertFalse($this->registry->isEmpty());
    }
}