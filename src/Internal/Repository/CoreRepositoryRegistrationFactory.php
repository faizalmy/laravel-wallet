<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Repository;

use Bavix\Wallet\Internal\Repository\TransactionRepository;
use Bavix\Wallet\Internal\Repository\TransactionRepositoryInterface;
use Bavix\Wallet\Internal\Repository\TransferRepository;
use Bavix\Wallet\Internal\Repository\TransferRepositoryInterface;
use Bavix\Wallet\Internal\Repository\WalletRepository;
use Bavix\Wallet\Internal\Repository\WalletRepositoryInterface;
use Bavix\Wallet\Internal\Service\ClockService;
use Bavix\Wallet\Internal\Service\ClockServiceInterface;
use Bavix\Wallet\Internal\Service\ConnectionService;
use Bavix\Wallet\Internal\Service\ConnectionServiceInterface;
use Bavix\Wallet\Internal\Service\DatabaseService;
use Bavix\Wallet\Internal\Service\DatabaseServiceInterface;
use Bavix\Wallet\Internal\Service\DispatcherService;
use Bavix\Wallet\Internal\Service\DispatcherServiceInterface;
use Bavix\Wallet\Internal\Service\IdentifierFactoryService;
use Bavix\Wallet\Internal\Service\IdentifierFactoryServiceInterface;
use Bavix\Wallet\Internal\Service\StorageService;
use Bavix\Wallet\Internal\Service\StorageServiceInterface;
use Bavix\Wallet\Internal\Transform\TransactionDtoTransformer;
use Bavix\Wallet\Internal\Transform\TransactionDtoTransformerInterface;
use Bavix\Wallet\Internal\Transform\TransferDtoTransformer;
use Bavix\Wallet\Internal\Transform\TransferDtoTransformerInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering core repositories.
 *
 * This factory handles the registration of all core repositories,
 * reducing code duplication in the service provider.
 */
final class CoreRepositoryRegistrationFactory implements RepositoryRegistrationFactoryInterface
{
    /**
     * Register core repositories with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerInternalServices($container, $config);
        $this->registerRepositories($container, $config);
        $this->registerTransformers($container, $config);
    }

    /**
     * Register internal services.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerInternalServices(Container $container, array $config): void
    {
        $container->singleton(ClockServiceInterface::class, $config['clock'] ?? ClockService::class);
        $container->singleton(ConnectionServiceInterface::class, $config['connection'] ?? ConnectionService::class);
        $container->singleton(DatabaseServiceInterface::class, $config['database'] ?? DatabaseService::class);
        $container->singleton(DispatcherServiceInterface::class, $config['dispatcher'] ?? DispatcherService::class);
        $container->singleton(IdentifierFactoryServiceInterface::class, $config['identifier_factory'] ?? IdentifierFactoryService::class);

        // Register storage service with cache configuration
        $cache = $config['cache'] ?? [];
        $container->singleton(StorageServiceInterface::class, function () use ($container, $cache) {
            return new StorageService(
                $container->get(\Bavix\Wallet\Internal\Service\MathServiceInterface::class),
                $container->get(CacheFactory::class)->store($cache['driver'] ?? 'array'),
                $cache['ttl'] ?? null
            );
        });

        // Bind to internal alias for backward compatibility
        $container->bind('wallet.internal.storage', StorageServiceInterface::class);
    }

    /**
     * Register repositories.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerRepositories(Container $container, array $config): void
    {
        $container->singleton(TransactionRepositoryInterface::class, $config['transaction'] ?? TransactionRepository::class);
        $container->singleton(TransferRepositoryInterface::class, $config['transfer'] ?? TransferRepository::class);
        $container->singleton(WalletRepositoryInterface::class, $config['wallet'] ?? WalletRepository::class);
    }

    /**
     * Register transformers.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerTransformers(Container $container, array $config): void
    {
        $container->singleton(TransactionDtoTransformerInterface::class, $config['transaction_transformer'] ?? TransactionDtoTransformer::class);
        $container->singleton(TransferDtoTransformerInterface::class, $config['transfer_transformer'] ?? TransferDtoTransformer::class);
    }

    /**
     * Get the list of repositories provided by this factory.
     *
     * @return array<class-string> Array of repository class names
     */
    public function provides(): array
    {
        return [
            ClockServiceInterface::class,
            ConnectionServiceInterface::class,
            DatabaseServiceInterface::class,
            DispatcherServiceInterface::class,
            IdentifierFactoryServiceInterface::class,
            StorageServiceInterface::class,
            TransactionRepositoryInterface::class,
            TransferRepositoryInterface::class,
            WalletRepositoryInterface::class,
            TransactionDtoTransformerInterface::class,
            TransferDtoTransformerInterface::class,
        ];
    }
}