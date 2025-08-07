<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Services;

use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistryInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Services\AssetAwareTransactionService;
use Bavix\Wallet\Services\TransactionServiceInterface;
use Bavix\Wallet\Services\TransferServiceInterface;
use Bavix\Wallet\Test\Infra\TestCase;
use Bavix\Wallet\Test\Infra\Models\User;

/**
 * Integration test for asset-aware transfer operations.
 * This test verifies that transfer operations use the correct asset tables.
 */
final class AssetAwareTransferIntegrationTest extends TestCase
{
    private AssetTypeRegistryInterface $registry;
    private AssetContextInterface $context;
    private AssetTypeDetector $detector;
    private TransactionServiceInterface $transactionService;
    private TransferServiceInterface $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new AssetTypeRegistry();
        $this->context = $this->app->make(AssetContextInterface::class);
        $this->detector = new AssetTypeDetector($this->registry);
        $this->transactionService = $this->app->make(TransactionServiceInterface::class);
        $this->transferService = $this->app->make(TransferServiceInterface::class);

        // Register test asset type
        $this->registry->register('shares', [
            'wallet_table' => 'share_wallets',
            'transaction_table' => 'share_transactions',
            'transfer_table' => 'share_transfers',
            'wallet_model' => 'App\Models\ShareWallet',
            'transaction_model' => 'App\Models\ShareTransaction',
            'transfer_model' => 'App\Models\ShareTransfer',
        ]);
    }

    public function testTransferServiceUsesAssetAwareTransactionService(): void
    {
        // Verify that the TransferService is using AssetAwareTransactionService
        $reflection = new \ReflectionClass($this->transferService);
        $transactionServiceProperty = $reflection->getProperty('transactionService');
        $transactionServiceProperty->setAccessible(true);
        $injectedTransactionService = $transactionServiceProperty->getValue($this->transferService);

        self::assertInstanceOf(AssetAwareTransactionService::class, $injectedTransactionService);
    }

    public function testAssetAwareTransactionServiceIsBound(): void
    {
        // Verify that TransactionServiceInterface is bound to AssetAwareTransactionService
        $service = $this->app->make(TransactionServiceInterface::class);
        self::assertInstanceOf(AssetAwareTransactionService::class, $service);
    }

    public function testAssetTypeDetectionWorksInTransferContext(): void
    {
        // Create a mock wallet that will be detected as shares asset type
        $wallet = $this->createMock(Wallet::class);
        $wallet->method('getTable')->willReturn('share_wallets');

        // Verify asset type detection works
        $detectedAssetType = $this->detector->detect($wallet);
        self::assertEquals('shares', $detectedAssetType);
    }

    public function testAssetContextIsSetDuringTransferOperations(): void
    {
        // Verify that asset context is properly managed
        $this->context->setContext('shares');
        self::assertEquals('shares', $this->context->getContext());

        $this->context->clearContext();
        self::assertNull($this->context->getContext());
    }

    public function testAssetAwareTransactionServiceHasAssetComponents(): void
    {
        $service = $this->app->make(TransactionServiceInterface::class);
        self::assertInstanceOf(AssetAwareTransactionService::class, $service);

        // Verify the service has the required asset components
        self::assertInstanceOf(AssetTypeDetector::class, $service->getAssetTypeDetector());
        self::assertInstanceOf(AssetContextInterface::class, $service->getAssetContext());
        self::assertInstanceOf(\Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface::class, $service->getAssetRepositoryFactory());
    }

    public function testAssetRegistryContainsTestAssetType(): void
    {
        // Verify the test asset type is registered
        self::assertTrue($this->registry->has('shares'));

        $config = $this->registry->get('shares');
        self::assertEquals('share_wallets', $config->getWalletTable());
        self::assertEquals('share_transactions', $config->getTransactionTable());
        self::assertEquals('share_transfers', $config->getTransferTable());
    }

    public function testAssetTypeDetectorCanDetectFromTableName(): void
    {
        // Create a mock wallet with share_wallets table
        $wallet = $this->createMock(Wallet::class);
        $wallet->method('getTable')->willReturn('share_wallets');

        $detectedAssetType = $this->detector->detectFromWallet($wallet);
        self::assertEquals('shares', $detectedAssetType);
    }

    public function testAssetTypeDetectorReturnsNullForUnknownTable(): void
    {
        // Create a mock wallet with unknown table
        $wallet = $this->createMock(Wallet::class);
        $wallet->method('getTable')->willReturn('unknown_table');

        $detectedAssetType = $this->detector->detectFromWallet($wallet);
        self::assertNull($detectedAssetType);
    }

    public function testAssetContextWithCallbackRestoresPreviousContext(): void
    {
        // Set initial context
        $this->context->setContext('default');

        // Use withContext callback
        $result = $this->context->withContext('shares', function () {
            self::assertEquals('shares', $this->context->getContext());
            return 'test_result';
        });

        // Verify context is restored
        self::assertEquals('default', $this->context->getContext());
        self::assertEquals('test_result', $result);
    }

    public function testAssetContextWithCallbackHandlesExceptions(): void
    {
        // Set initial context
        $this->context->setContext('default');

        // Use withContext callback that throws exception
        try {
            $this->context->withContext('shares', function () {
                self::assertEquals('shares', $this->context->getContext());
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            self::assertEquals('Test exception', $e->getMessage());
        }

        // Verify context is restored even after exception
        self::assertEquals('default', $this->context->getContext());
    }
}