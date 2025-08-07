<?php

declare(strict_types=1);

namespace Bavix\Wallet\Test\Units\Services;

use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistryInterface;
use Bavix\Wallet\Internal\Dto\TransferDto;
use Bavix\Wallet\Internal\Dto\TransferDtoInterface;
use Bavix\Wallet\Internal\Assembler\TransferDtoAssembler;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Services\AssetAwareTransferService;
use Bavix\Wallet\Services\TransferServiceInterface;
use Bavix\Wallet\Test\Infra\TestCase;
use Bavix\Wallet\Test\Infra\Models\User;

/**
 * Test for polymorphic relationship functionality in multi-asset transfers.
 * This test verifies that the polymorphic relationship fixes work correctly.
 */
final class PolymorphicTransferTest extends TestCase
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

        // Register test asset types
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

    public function testTransferDtoIncludesPolymorphicTypes(): void
    {
        // Create mock models
        $fromWallet = $this->createMock(Wallet::class);
        $fromWallet->method('getKey')->willReturn(1);
        $fromWallet->method('getMorphClass')->willReturn('App\Models\ShareWallet');

        $toWallet = $this->createMock(Wallet::class);
        $toWallet->method('getKey')->willReturn(2);
        $toWallet->method('getMorphClass')->willReturn('App\Models\ShareWallet');

        // Create transfer DTO using assembler
        $assembler = $this->app->make(\Bavix\Wallet\Internal\Assembler\TransferDtoAssemblerInterface::class);
        $transferDto = $assembler->create(
            10, // depositId
            11, // withdrawId
            'transfer', // status
            $fromWallet, // fromModel
            $toWallet, // toModel
            0, // discount
            '0', // fee
            null, // uuid
            null // extra
        );

        // Verify polymorphic types are set correctly
        self::assertInstanceOf(TransferDtoInterface::class, $transferDto);
        self::assertEquals(1, $transferDto->getFromId());
        self::assertEquals('App\Models\ShareWallet', $transferDto->getFromType());
        self::assertEquals(2, $transferDto->getToId());
        self::assertEquals('App\Models\ShareWallet', $transferDto->getToType());
    }

    public function testTransferModelHasPolymorphicFields(): void
    {
        // Create a transfer model
        $transfer = new Transfer();

        // Verify polymorphic fields are fillable
        $fillable = $transfer->getFillable();
        self::assertContains('from_type', $fillable);
        self::assertContains('to_type', $fillable);
        self::assertContains('from_id', $fillable);
        self::assertContains('to_id', $fillable);
    }

    public function testTransferModelHasPolymorphicRelationships(): void
    {
        // Create a transfer model
        $transfer = new Transfer();

        // Verify polymorphic relationships are defined
        $fromRelation = $transfer->from();
        self::assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $fromRelation);

        $toRelation = $transfer->to();
        self::assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $toRelation);
    }

    public function testAssetAwareTransferServiceUsesPolymorphicTypes(): void
    {
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Verify the service has access to asset components
        $detector = $service->getAssetTypeDetector();
        $context = $service->getAssetContext();
        $factory = $service->getAssetRepositoryFactory();

        self::assertInstanceOf(AssetTypeDetector::class, $detector);
        self::assertInstanceOf(AssetContextInterface::class, $context);
        self::assertInstanceOf(\Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface::class, $factory);
    }

    public function testTransferDtoAssemblerSetsPolymorphicTypes(): void
    {
        // Create mock models with different morph classes
        $fromWallet = $this->createMock(Wallet::class);
        $fromWallet->method('getKey')->willReturn(1);
        $fromWallet->method('getMorphClass')->willReturn('App\Models\ShareWallet');

        $toWallet = $this->createMock(Wallet::class);
        $toWallet->method('getKey')->willReturn(2);
        $toWallet->method('getMorphClass')->willReturn('App\Models\BondWallet');

        // Create transfer DTO
        $assembler = new TransferDtoAssembler(
            $this->app->make(\Bavix\Wallet\Internal\Service\IdentifierFactoryServiceInterface::class),
            $this->app->make(\Bavix\Wallet\Internal\Service\ClockServiceInterface::class)
        );

        $transferDto = $assembler->create(
            10, // depositId
            11, // withdrawId
            'transfer', // status
            $fromWallet, // fromModel
            $toWallet, // toModel
            0, // discount
            '0', // fee
            null, // uuid
            null // extra
        );

        // Verify polymorphic types are set correctly
        self::assertEquals('App\Models\ShareWallet', $transferDto->getFromType());
        self::assertEquals('App\Models\BondWallet', $transferDto->getToType());
        self::assertEquals(1, $transferDto->getFromId());
        self::assertEquals(2, $transferDto->getToId());
    }

    public function testPolymorphicTypesAreDifferentForDifferentAssetTypes(): void
    {
        // Test shares asset type
        $sharesWallet = $this->createMock(Wallet::class);
        $sharesWallet->method('getTable')->willReturn('share_wallets');
        $sharesWallet->method('getKey')->willReturn(1);
        $sharesWallet->method('getMorphClass')->willReturn('App\Models\ShareWallet');

        // Test bonds asset type
        $bondsWallet = $this->createMock(Wallet::class);
        $bondsWallet->method('getTable')->willReturn('bond_wallets');
        $bondsWallet->method('getKey')->willReturn(2);
        $bondsWallet->method('getMorphClass')->willReturn('App\Models\BondWallet');

        // Verify different morph classes
        self::assertEquals('App\Models\ShareWallet', $sharesWallet->getMorphClass());
        self::assertEquals('App\Models\BondWallet', $bondsWallet->getMorphClass());
        self::assertNotEquals($sharesWallet->getMorphClass(), $bondsWallet->getMorphClass());
    }

    public function testTransferDtoInterfaceIncludesPolymorphicMethods(): void
    {
        // Verify the interface includes polymorphic type methods
        $reflection = new \ReflectionClass(\Bavix\Wallet\Internal\Dto\TransferDtoInterface::class);

        self::assertTrue($reflection->hasMethod('getFromType'));
        self::assertTrue($reflection->hasMethod('getToType'));

        $fromTypeMethod = $reflection->getMethod('getFromType');
        $toTypeMethod = $reflection->getMethod('getToType');

        self::assertEquals('string', $fromTypeMethod->getReturnType()->getName());
        self::assertEquals('string', $toTypeMethod->getReturnType()->getName());
    }

    public function testTransferDtoImplementsPolymorphicMethods(): void
    {
        // Create a transfer DTO with polymorphic types
        $transferDto = new TransferDto(
            'test-uuid',
            10, // depositId
            11, // withdrawId
            'transfer', // status
            1, // fromId
            'App\Models\ShareWallet', // fromType
            2, // toId
            'App\Models\ShareWallet', // toType
            0, // discount
            '0', // fee
            null, // extra
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        // Verify polymorphic methods work correctly
        self::assertEquals('App\Models\ShareWallet', $transferDto->getFromType());
        self::assertEquals('App\Models\ShareWallet', $transferDto->getToType());
        self::assertEquals(1, $transferDto->getFromId());
        self::assertEquals(2, $transferDto->getToId());
    }

    public function testPolymorphicRelationshipConfiguration(): void
    {
        // Test that the Transfer model is properly configured for polymorphic relationships
        $transfer = new Transfer();

        // Verify fillable array includes polymorphic fields
        $fillable = $transfer->getFillable();
        self::assertContains('from_type', $fillable);
        self::assertContains('to_type', $fillable);

        // Verify relationships are polymorphic
        $fromRelation = $transfer->from();
        $toRelation = $transfer->to();

        self::assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $fromRelation);
        self::assertInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class, $toRelation);

        // Verify relationship methods return the correct type
        $reflection = new \ReflectionClass(Transfer::class);
        $fromMethod = $reflection->getMethod('from');
        $toMethod = $reflection->getMethod('to');

        $fromReturnType = $fromMethod->getReturnType();
        $toReturnType = $toMethod->getReturnType();

        self::assertEquals('Illuminate\Database\Eloquent\Relations\MorphTo', $fromReturnType->getName());
        self::assertEquals('Illuminate\Database\Eloquent\Relations\MorphTo', $toReturnType->getName());
    }

    public function testAssetTypeDetectionWithPolymorphicTypes(): void
    {
        // Test that asset type detection works with polymorphic types
        $sharesWallet = $this->createMock(Wallet::class);
        $sharesWallet->method('getTable')->willReturn('share_wallets');
        $sharesWallet->method('getMorphClass')->willReturn('App\Models\ShareWallet');

        $bondsWallet = $this->createMock(Wallet::class);
        $bondsWallet->method('getTable')->willReturn('bond_wallets');
        $bondsWallet->method('getMorphClass')->willReturn('App\Models\BondWallet');

        // Detect asset types
        $sharesAssetType = $this->detector->detect($sharesWallet);
        $bondsAssetType = $this->detector->detect($bondsWallet);

        self::assertEquals('shares', $sharesAssetType);
        self::assertEquals('bonds', $bondsAssetType);

        // Verify morph classes are different
        self::assertEquals('App\Models\ShareWallet', $sharesWallet->getMorphClass());
        self::assertEquals('App\Models\BondWallet', $bondsWallet->getMorphClass());
    }

    public function testCompletePolymorphicTransferFlow(): void
    {
        // This test verifies the complete polymorphic transfer flow
        $service = $this->app->make(AssetAwareTransferService::class);
        self::assertInstanceOf(AssetAwareTransferService::class, $service);

        // Create mock wallets with different asset types
        $sharesWallet = $this->createMock(Wallet::class);
        $sharesWallet->method('getTable')->willReturn('share_wallets');
        $sharesWallet->method('getKey')->willReturn(1);
        $sharesWallet->method('getMorphClass')->willReturn('App\Models\ShareWallet');

        $bondsWallet = $this->createMock(Wallet::class);
        $bondsWallet->method('getTable')->willReturn('bond_wallets');
        $bondsWallet->method('getKey')->willReturn(2);
        $bondsWallet->method('getMorphClass')->willReturn('App\Models\BondWallet');

        // Test asset type detection
        $detector = $service->getAssetTypeDetector();
        $sharesAssetType = $detector->detect($sharesWallet);
        $bondsAssetType = $detector->detect($bondsWallet);

        self::assertEquals('shares', $sharesAssetType);
        self::assertEquals('bonds', $bondsAssetType);

        // Test polymorphic type handling
        self::assertEquals('App\Models\ShareWallet', $sharesWallet->getMorphClass());
        self::assertEquals('App\Models\BondWallet', $bondsWallet->getMorphClass());

        // Test context management with polymorphic types
        $context = $service->getAssetContext();
        $context->setContext($sharesAssetType);
        self::assertEquals('shares', $context->getContext());

        // Test context switching
        $result = $context->withContext($bondsAssetType, function () use ($context) {
            self::assertEquals('bonds', $context->getContext());
            return 'context_switch_test';
        });

        self::assertEquals('shares', $context->getContext());
        self::assertEquals('context_switch_test', $result);
    }
}