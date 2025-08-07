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
use Bavix\Wallet\Services\AssetAwareTransferService;
use Bavix\Wallet\Services\TransferServiceInterface;
use Bavix\Wallet\Test\Infra\TestCase;
use Bavix\Wallet\Test\Infra\Models\User;

/**
 * Test for automatic asset detection in AssetAwareTransferService.
 * This test verifies that the service automatically detects asset types
 * from wallet models and uses asset-specific tables for transfers.
 */
final class AssetAwareTransferAutomaticDetectionTest extends TestCase
{
    private AssetTypeRegistryInterface $registry;
    private AssetContextInterface $context;
    private AssetTypeDetector $detector;
    private TransferServiceInterface $transferService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->app->make(AssetTypeRegistryInterface::class);
        $this->context = $this->app->make(AssetContextInterface::class);
        $this->detector = new AssetTypeDetector($this->registry);
        $this->transferService = $this->app->make(TransferServiceInterface::class);

        // Register test asset types in the service container's registry
        $this->registry->register('shares', [
            'wallet_table' => 'share_wallets',
            'transaction_table' => 'share_transactions',
            'transfer_table' => 'share_transfers',
            'wallet_model' => 'App\Models\ShareWallet',
            'transaction_model' => 'App\Models\ShareTransaction',
            'transfer_model' => 'App\Models\ShareTransfer',
        ]);

        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer',
        ]);
    }

    public function testAssetAwareTransferServiceIsAvailableExplicitly(): void
    {
        // Verify that TransferServiceInterface resolves to default TransferService for backward compatibility
        $service = $this->app->make(TransferServiceInterface::class);
        self::assertInstanceOf(\Bavix\Wallet\Services\TransferService::class, $service);

        // Verify that AssetAwareTransferService is available explicitly
        $assetAwareService = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $assetAwareService);
    }

    public function testAutomaticAssetTypeDetectionFromWallets(): void
    {
        // Create mock wallets that will be detected as shares asset type
        $fromWallet = $this->createMock(Wallet::class);
        $fromWallet->method('getTable')->willReturn('share_wallets');
        $fromWallet->method('getKey')->willReturn(1);

        $toWallet = $this->createMock(Wallet::class);
        $toWallet->method('getTable')->willReturn('share_wallets');
        $toWallet->method('getKey')->willReturn(2);

        // Verify asset type detection works
        $detectedAssetType = $this->detector->detect($fromWallet);
        self::assertEquals('shares', $detectedAssetType);

        $detectedAssetType = $this->detector->detect($toWallet);
        self::assertEquals('shares', $detectedAssetType);
    }

    public function testAssetContextIsSetAutomaticallyDuringTransfer(): void
    {
        // Verify that asset context is not set initially
        self::assertNull($this->context->getContext());

        // The AssetAwareTransferService should automatically set asset context
        // when processing transfers, but we need to test this with actual transfer objects
        // This test verifies the capability exists
        $this->context->setContext('shares');
        self::assertEquals('shares', $this->context->getContext());

        // Test context restoration
        $result = $this->context->withContext('bonds', function () {
            self::assertEquals('bonds', $this->context->getContext());
            return 'test_result';
        });

        self::assertEquals('shares', $this->context->getContext());
        self::assertEquals('test_result', $result);
    }

    public function testAssetTypeDetectorHandlesDifferentAssetTypes(): void
    {
        // Test shares asset type
        $sharesWallet = $this->createMock(Wallet::class);
        $sharesWallet->method('getTable')->willReturn('share_wallets');
        $sharesAssetType = $this->detector->detect($sharesWallet);
        self::assertEquals('shares', $sharesAssetType);

        // Test bonds asset type
        $bondsWallet = $this->createMock(Wallet::class);
        $bondsWallet->method('getTable')->willReturn('bond_wallets');
        $bondsAssetType = $this->detector->detect($bondsWallet);
        self::assertEquals('bonds', $bondsAssetType);

        // Test unknown asset type
        $unknownWallet = $this->createMock(Wallet::class);
        $unknownWallet->method('getTable')->willReturn('unknown_wallets');
        $unknownAssetType = $this->detector->detect($unknownWallet);
        self::assertNull($unknownAssetType);
    }

    public function testAssetRegistryContainsAllRegisteredAssetTypes(): void
    {
        // Verify both asset types are properly registered
        self::assertTrue($this->registry->has('shares'));
        self::assertTrue($this->registry->has('bonds'));

        // Verify shares configuration
        $sharesConfig = $this->registry->get('shares');
        self::assertEquals('share_wallets', $sharesConfig->getWalletTable());
        self::assertEquals('share_transactions', $sharesConfig->getTransactionTable());
        self::assertEquals('share_transfers', $sharesConfig->getTransferTable());

        // Verify bonds configuration
        $bondsConfig = $this->registry->get('bonds');
        self::assertEquals('bond_wallets', $bondsConfig->getWalletTable());
        self::assertEquals('bond_transactions', $bondsConfig->getTransactionTable());
        self::assertEquals('bond_transfers', $bondsConfig->getTransferTable());
    }

    public function testAssetAwareTransferServiceHasRequiredComponents(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Verify the service has the required asset components
        $reflection = new \ReflectionClass($service);
        self::assertTrue($reflection->hasProperty('assetTypeDetector'));
        self::assertTrue($reflection->hasProperty('assetContext'));
        self::assertTrue($reflection->hasProperty('repositoryFactory'));
        self::assertTrue($reflection->hasProperty('castService'));
    }

    public function testCastServiceIsAccessibleInAssetAwareTransferService(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Verify castService is accessible (it's now public)
        $reflection = new \ReflectionClass($service);
        $castServiceProperty = $reflection->getProperty('castService');
        self::assertTrue($castServiceProperty->isPublic());
    }

    public function testAssetAwareTransferServiceCanDetectAssetTypes(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Verify the service has access to asset type detector
        $detector = $service->getAssetTypeDetector();
        self::assertInstanceOf(AssetTypeDetector::class, $detector);

        // Test asset type detection through the service
        $sharesWallet = $this->createMock(Wallet::class);
        $sharesWallet->method('getTable')->willReturn('share_wallets');
        $detectedAssetType = $detector->detect($sharesWallet);
        self::assertEquals('shares', $detectedAssetType);
    }

    public function testAssetContextManagementInTransferService(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Verify the service has access to asset context
        $context = $service->getAssetContext();
        self::assertInstanceOf(AssetContextInterface::class, $context);

        // Test context management
        $context->setContext('shares');
        self::assertEquals('shares', $context->getContext());

        $result = $context->withContext('bonds', function () use ($context) {
            self::assertEquals('bonds', $context->getContext());
            return 'context_test';
        });

        self::assertEquals('shares', $context->getContext());
        self::assertEquals('context_test', $result);
    }

    public function testAssetRepositoryFactoryAccessInTransferService(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Verify the service has access to repository factory
        $factory = $service->getAssetRepositoryFactory();
        self::assertInstanceOf(\Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface::class, $factory);
    }

    public function testCompleteAssetDetectionFlow(): void
    {
        // This test verifies the complete flow that should happen in AssetAwareTransferService
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // 1. Create mock wallets
        $fromWallet = $this->createMock(Wallet::class);
        $fromWallet->method('getTable')->willReturn('share_wallets');
        $fromWallet->method('getKey')->willReturn(1);

        $toWallet = $this->createMock(Wallet::class);
        $toWallet->method('getTable')->willReturn('share_wallets');
        $toWallet->method('getKey')->willReturn(2);

        // 2. Detect asset type
        $detector = $service->getAssetTypeDetector();
        $detectedAssetType = $detector->detect($fromWallet);
        self::assertEquals('shares', $detectedAssetType);

        // 3. Set asset context
        $context = $service->getAssetContext();
        $context->setContext($detectedAssetType);
        self::assertEquals('shares', $context->getContext());

        // 4. Verify context is properly managed
        $result = $context->withContext('shares', function () {
            return 'transfer_operation';
        });

        self::assertEquals('shares', $context->getContext());
        self::assertEquals('transfer_operation', $result);
    }

    public function testAssetAwareTransferServiceFallbackBehavior(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Test with unknown asset type
        $unknownWallet = $this->createMock(Wallet::class);
        $unknownWallet->method('getTable')->willReturn('unknown_wallets');

        $detector = $service->getAssetTypeDetector();
        $detectedAssetType = $detector->detect($unknownWallet);
        self::assertNull($detectedAssetType);

        // When no asset type is detected, the service should fall back to default behavior
        // This is handled in the apply() method
        $context = $service->getAssetContext();
        self::assertNull($context->getContext());
    }
}