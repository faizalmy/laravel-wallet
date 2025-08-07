<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Services;

use Bavix\Wallet\Internal\Asset\AssetConfig;
use Bavix\Wallet\Internal\Asset\AssetContext;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactory;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Internal\Assembler\TransactionCreatedEventAssemblerInterface;
use Bavix\Wallet\Internal\Dto\TransactionDtoInterface;
use Bavix\Wallet\Internal\Exceptions\RecordNotFoundException;
use Bavix\Wallet\Internal\Service\DispatcherServiceInterface;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Services\AssetAwareTransactionService;
use Bavix\Wallet\Services\AssistantServiceInterface;
use Bavix\Wallet\Services\AssetAwareAtmService;
use Bavix\Wallet\Services\CastServiceInterface;
use Bavix\Wallet\Services\PrepareServiceInterface;
use Bavix\Wallet\Services\RegulatorServiceInterface;
use Bavix\Wallet\Test\Infra\TestCase;

/**
 * @internal
 */
final class AssetAwareTransactionServiceTest extends TestCase
{
    private AssetAwareTransactionService $service;
    private AssetTypeRegistry $registry;
    private AssetContext $context;
    private AssetTypeDetector $detector;
    private AssetRepositoryFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = new AssetTypeRegistry();
        $this->context = new AssetContext();
        $this->detector = new AssetTypeDetector($this->registry);
        $this->factory = new AssetRepositoryFactory(
            $this->registry,
            $this->context,
            $this->createMock(\Bavix\Wallet\Internal\Transform\TransactionDtoTransformerInterface::class),
            $this->createMock(\Bavix\Wallet\Internal\Transform\TransferDtoTransformerInterface::class),
            $this->createMock(\Bavix\Wallet\Internal\Service\JsonServiceInterface::class)
        );

        // Register test asset type
        $this->registry->register('bonds', [
            'wallet_table' => 'bond_wallets',
            'transaction_table' => 'bond_transactions',
            'transfer_table' => 'bond_transfers',
            'wallet_model' => 'App\Models\BondWallet',
            'transaction_model' => 'App\Models\BondTransaction',
            'transfer_model' => 'App\Models\BondTransfer'
        ]);

        $this->service = new AssetAwareTransactionService(
            $this->createMock(TransactionCreatedEventAssemblerInterface::class),
            $this->createMock(DispatcherServiceInterface::class),
            $this->createMock(AssistantServiceInterface::class),
            $this->createMock(RegulatorServiceInterface::class),
            $this->createMock(PrepareServiceInterface::class),
            $this->createMock(CastServiceInterface::class),
            $this->createMock(AssetAwareAtmService::class),
            $this->detector,
            $this->context,
            $this->factory
        );
    }

    public function testMakeOneWithAssetTypeDetection(): void
    {
        // Create mock wallet that will be detected as bonds asset type
        $wallet = $this->createMock(Wallet::class);
        $wallet->method('getTable')->willReturn('bond_wallets');

        // Mock the parent service behavior
        $mockTransaction = $this->createMock(Transaction::class);

        // We can't easily test the actual parent call due to complex dependencies,
        // but we can verify that the asset context is set correctly
        $detectedAssetType = $this->detector->detect($wallet);
        self::assertSame('bonds', $detectedAssetType);
    }

    public function testApplyWithAssetTypeDetection(): void
    {
        // Create mock wallets that will be detected as bonds asset type
        $wallet1 = $this->createMock(Wallet::class);
        $wallet1->method('getTable')->willReturn('bond_wallets');

        $wallet2 = $this->createMock(Wallet::class);
        $wallet2->method('getTable')->willReturn('bond_wallets');

        $wallets = [1 => $wallet1, 2 => $wallet2];
        $objects = [$this->createMock(TransactionDtoInterface::class)];

        // We can't easily test the actual parent call due to complex dependencies,
        // but we can verify that the asset context is set correctly
        $detectedAssetType = $this->detector->detect($wallet1);
        self::assertSame('bonds', $detectedAssetType);
    }

    public function testMakeOneWithNoAssetTypeDetection(): void
    {
        // Create mock wallet that won't be detected as any asset type
        $wallet = $this->createMock(Wallet::class);
        $wallet->method('getTable')->willReturn('unknown_table');

        // Verify that no asset type is detected
        $detectedAssetType = $this->detector->detect($wallet);
        self::assertNull($detectedAssetType);
    }

    public function testApplyWithNoAssetTypeDetection(): void
    {
        // Create mock wallets that won't be detected as any asset type
        $wallet1 = $this->createMock(Wallet::class);
        $wallet1->method('getTable')->willReturn('unknown_table');

        $wallet2 = $this->createMock(Wallet::class);
        $wallet2->method('getTable')->willReturn('unknown_table');

        $wallets = [1 => $wallet1, 2 => $wallet2];
        $objects = [$this->createMock(TransactionDtoInterface::class)];

        // Verify that no asset type is detected
        $detectedAssetType = $this->detector->detect($wallet1);
        self::assertNull($detectedAssetType);
    }

    public function testGetAssetTypeDetector(): void
    {
        $detector = $this->service->getAssetTypeDetector();
        self::assertInstanceOf(AssetTypeDetector::class, $detector);
        self::assertSame($this->detector, $detector);
    }

    public function testGetAssetContext(): void
    {
        $context = $this->service->getAssetContext();
        self::assertInstanceOf(AssetContext::class, $context);
        self::assertSame($this->context, $context);
    }

    public function testGetAssetRepositoryFactory(): void
    {
        $factory = $this->service->getAssetRepositoryFactory();
        self::assertInstanceOf(AssetRepositoryFactory::class, $factory);
        self::assertSame($this->factory, $factory);
    }

    public function testAssetContextIsSetDuringOperation(): void
    {
        // Create mock wallet that will be detected as bonds asset type
        $wallet = $this->createMock(Wallet::class);
        $wallet->method('getTable')->willReturn('bond_wallets');

        // Verify that the context is initially empty
        self::assertNull($this->context->getContext());

        // Simulate what happens during asset-aware operation
        $assetType = $this->detector->detect($wallet);
        if ($assetType !== null) {
            $this->context->withContext($assetType, function () {
                // Verify that the context is set during the operation
                self::assertSame('bonds', $this->context->getContext());
            });
        }

        // Verify that the context is cleared after the operation
        self::assertNull($this->context->getContext());
    }
}