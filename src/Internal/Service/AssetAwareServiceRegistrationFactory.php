<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Service;

use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistryInterface;
use Bavix\Wallet\Internal\Assembler\TransactionCreatedEventAssemblerInterface;
use Bavix\Wallet\Internal\Assembler\TransactionQueryAssemblerInterface;
use Bavix\Wallet\Internal\Assembler\TransferDtoAssemblerInterface;
use Bavix\Wallet\Internal\Assembler\TransferQueryAssemblerInterface;
use Bavix\Wallet\Internal\Assembler\WalletCreatedEventAssemblerInterface;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Internal\Service\DatabaseServiceInterface;
use Bavix\Wallet\Internal\Service\DispatcherServiceInterface;
use Bavix\Wallet\Internal\Service\IdentifierFactoryServiceInterface;
use Bavix\Wallet\Services\AssetAwareAtmService;
use Bavix\Wallet\Services\AssetAwareTransactionService;
use Bavix\Wallet\Services\AssetAwareTransferService;
use Bavix\Wallet\Services\AssetAwareWalletService;
use Bavix\Wallet\Services\AssetAwareWalletServiceInterface;
use Bavix\Wallet\Services\AssistantServiceInterface;
use Bavix\Wallet\Services\AtmServiceInterface;
use Bavix\Wallet\Services\CastServiceInterface;
use Bavix\Wallet\Services\RegulatorServiceInterface;
use Bavix\Wallet\Services\TransactionServiceInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering asset-aware services.
 *
 * This factory handles the registration of asset-aware services with their
 * complex dependency injection requirements.
 */
final class AssetAwareServiceRegistrationFactory implements ServiceRegistrationFactoryInterface
{
    /**
     * Register asset-aware services with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerAssetTypeDetector($container);
        $this->registerAssetAwareAtmService($container);
        $this->registerAssetAwareTransactionService($container);
        $this->registerAssetAwareTransferService($container);
        $this->registerAssetAwareWalletService($container);
    }

    /**
     * Register AssetTypeDetector.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerAssetTypeDetector(Container $container): void
    {
        $container->singleton(AssetTypeDetector::class, function ($container) {
            return new AssetTypeDetector($container->make(AssetTypeRegistryInterface::class));
        });
    }

    /**
     * Register AssetAwareAtmService.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerAssetAwareAtmService(Container $container): void
    {
        $container->singleton(AssetAwareAtmService::class, function ($container) {
            return new AssetAwareAtmService(
                $container->make(TransactionQueryAssemblerInterface::class),
                $container->make(TransferQueryAssemblerInterface::class),
                $container->make(AssetRepositoryFactoryInterface::class),
                $container->make(AssistantServiceInterface::class)
            );
        });
    }

    /**
     * Register AssetAwareTransactionService.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerAssetAwareTransactionService(Container $container): void
    {
        $container->singleton(AssetAwareTransactionService::class, function ($container) {
            return new AssetAwareTransactionService(
                $container->make(TransactionCreatedEventAssemblerInterface::class),
                $container->make(DispatcherServiceInterface::class),
                $container->make(AssistantServiceInterface::class),
                $container->make(RegulatorServiceInterface::class),
                $container->make(\Bavix\Wallet\Services\PrepareServiceInterface::class),
                $container->make(CastServiceInterface::class),
                $container->make(AssetAwareAtmService::class),
                $container->make(AssetTypeDetector::class),
                $container->make(AssetContextInterface::class),
                $container->make(AssetRepositoryFactoryInterface::class)
            );
        });
    }

    /**
     * Register AssetAwareTransferService.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerAssetAwareTransferService(Container $container): void
    {
        $container->singleton(AssetAwareTransferService::class, function ($container) {
            return new AssetAwareTransferService(
                $container->make(TransferDtoAssemblerInterface::class),
                $container->make(TransferRepositoryInterface::class),
                $container->make(TransactionServiceInterface::class),
                $container->make(DatabaseServiceInterface::class),
                $container->make(CastServiceInterface::class),
                $container->make(AtmServiceInterface::class),
                $container->make(AssetTypeDetector::class),
                $container->make(AssetContextInterface::class),
                $container->make(AssetRepositoryFactoryInterface::class)
            );
        });
    }

    /**
     * Register AssetAwareWalletService.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerAssetAwareWalletService(Container $container): void
    {
        $container->singleton(AssetAwareWalletService::class, function ($container) {
            return new AssetAwareWalletService(
                $container->make(AssetContextInterface::class),
                $container->make(AssetRepositoryFactoryInterface::class),
                $container->make(AssetTypeDetector::class),
                $container->make(IdentifierFactoryServiceInterface::class),
                $container->make(DispatcherServiceInterface::class),
                $container->make(WalletCreatedEventAssemblerInterface::class),
                $container->make(\Bavix\Wallet\Internal\Repository\WalletRepositoryInterface::class)
            );
        });

        // Bind interface to implementation for easy access
        $container->bind(AssetAwareWalletServiceInterface::class, AssetAwareWalletService::class);
    }

    /**
     * Get the list of services provided by this factory.
     *
     * @return array<class-string> Array of service class names
     */
    public function provides(): array
    {
        return [
            AssetTypeDetector::class,
            AssetAwareAtmService::class,
            AssetAwareTransactionService::class,
            AssetAwareTransferService::class,
            AssetAwareWalletService::class,
            AssetAwareWalletServiceInterface::class,
        ];
    }
}