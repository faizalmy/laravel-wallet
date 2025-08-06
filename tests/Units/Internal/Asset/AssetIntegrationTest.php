<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Internal\Asset;

use Bavix\Wallet\Internal\Asset\AssetConfig;
use Bavix\Wallet\Internal\Asset\AssetContext;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactory;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Internal\Repository\TransactionRepositoryInterface;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Internal\Repository\WalletRepositoryInterface;
use Bavix\Wallet\Internal\Service\JsonService;
use Bavix\Wallet\Internal\Transform\TransactionDtoTransformer;
use Bavix\Wallet\Internal\Transform\TransferDtoTransformer;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class AssetIntegrationTest extends TestCase
{
    private AssetTypeRegistry $registry;
    private AssetContext $context;
    private AssetRepositoryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new AssetTypeRegistry();
        $this->context = new AssetContext();
        $this->factory = new AssetRepositoryFactory(
            $this->registry,
            $this->context,
            new TransactionDtoTransformer(),
            new TransferDtoTransformer(),
            new JsonService()
        );
    }

    public function testCompleteAssetManagementFlow(): void
    {
        // 1. Register asset types
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

        // 2. Set default asset type
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

        // 3. Verify asset types are registered
        self::assertTrue($this->registry->has('bonds'));
        self::assertTrue($this->registry->has('shares'));
        self::assertTrue($this->registry->has('default'));
        self::assertCount(3, $this->registry->getAll());

        // 4. Test context-aware repository creation (skip actual creation since models don't exist in test)
        $this->context->setContext('bonds');

        // Test that we can get asset config for bonds
        $bondConfig = $this->factory->getAssetConfig();
        self::assertSame('bonds', $bondConfig->getAssetType());

        // 5. Test explicit asset type specification
        $shareConfig = $this->factory->getAssetConfig('shares');
        self::assertSame('shares', $shareConfig->getAssetType());

        // 6. Test fallback to default when no context
        $this->context->clearContext();

        $defaultConfig = $this->factory->getAssetConfig();
        self::assertSame('default', $defaultConfig->getAssetType());
    }

    public function testAssetConfigRetrieval(): void
    {
        $this->registry->register('inventory', [
            'wallet_table' => 'inventory_wallets',
            'transaction_table' => 'inventory_transactions',
            'transfer_table' => 'inventory_transfers',
            'wallet_model' => 'App\Models\InventoryWallet',
            'transaction_model' => 'App\Models\InventoryTransaction',
            'transfer_model' => 'App\Models\InventoryTransfer',
            'migration_path' => '/path/to/migrations',
            'meta' => ['description' => 'Inventory assets']
        ]);

        $config = $this->factory->getAssetConfig('inventory');

        self::assertSame('inventory', $config->getAssetType());
        self::assertSame('inventory_wallets', $config->getWalletTable());
        self::assertSame('inventory_transactions', $config->getTransactionTable());
        self::assertSame('inventory_transfers', $config->getTransferTable());
        self::assertSame('App\Models\InventoryWallet', $config->getWalletModel());
        self::assertSame('App\Models\InventoryTransaction', $config->getTransactionModel());
        self::assertSame('App\Models\InventoryTransfer', $config->getTransferModel());
        self::assertSame('/path/to/migrations', $config->getMigrationPath());
        self::assertSame('Inventory assets', $config->getMetaValue('description'));
    }

    public function testAssetTypeSupport(): void
    {
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        self::assertTrue($this->factory->isAssetTypeSupported('bonds'));
        self::assertFalse($this->factory->isAssetTypeSupported('nonexistent'));
    }

    public function testContextWithCallback(): void
    {
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        $result = $this->context->withContext('bonds', function () {
            $config = $this->factory->getAssetConfig();
            return $config->getAssetType();
        });

        self::assertSame('bonds', $result);
        self::assertNull($this->context->getContext());
    }
}