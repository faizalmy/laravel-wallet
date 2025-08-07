<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Service;

use Bavix\Wallet\Internal\Service\ClockService;
use Bavix\Wallet\Internal\Service\ClockServiceInterface;
use Bavix\Wallet\Internal\Service\ConnectionService;
use Bavix\Wallet\Internal\Service\ConnectionServiceInterface;
use Bavix\Wallet\Internal\Service\DatabaseService;
use Bavix\Wallet\Internal\Service\DatabaseServiceInterface;
use Bavix\Wallet\Internal\Service\DispatcherService;
use Bavix\Wallet\Internal\Service\DispatcherServiceInterface;
use Bavix\Wallet\Internal\Service\JsonService;
use Bavix\Wallet\Internal\Service\JsonServiceInterface;
use Bavix\Wallet\Internal\Service\LockService;
use Bavix\Wallet\Internal\Service\LockServiceInterface;
use Bavix\Wallet\Internal\Service\MathService;
use Bavix\Wallet\Internal\Service\MathServiceInterface;
use Bavix\Wallet\Internal\Service\StateService;
use Bavix\Wallet\Internal\Service\StateServiceInterface;
use Bavix\Wallet\Internal\Service\StorageService;
use Bavix\Wallet\Internal\Service\StorageServiceInterface;
use Bavix\Wallet\Internal\Service\TranslatorService;
use Bavix\Wallet\Internal\Service\TranslatorServiceInterface;
use Bavix\Wallet\Internal\Service\UuidFactoryService;
use Bavix\Wallet\Internal\Service\UuidFactoryServiceInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering internal services.
 *
 * This factory handles the registration of internal services that are not
 * covered by other factories, reducing code duplication in the service provider.
 */
final class InternalServiceRegistrationFactory implements ServiceRegistrationFactoryInterface
{
    /**
     * Register internal services with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerStorageService($container, $config);
        $this->registerUtilityServices($container, $config);
        $this->registerMathService($container, $config);
        $this->registerLockService($container, $config);
    }

    /**
     * Register storage service.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerStorageService(Container $container, array $config): void
    {
        $container->alias($config['storage'] ?? StorageService::class, 'wallet.internal.storage');
        $container->when($config['storage'] ?? StorageService::class)
            ->needs('$ttl')
            ->giveConfig('wallet.cache.ttl');

        // Register StorageService as a singleton to ensure proper dependency injection
        $container->singleton(StorageServiceInterface::class, $config['storage'] ?? StorageService::class);
    }

    /**
     * Register utility services.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerUtilityServices(Container $container, array $config): void
    {
        $container->singleton(ClockServiceInterface::class, $config['clock'] ?? ClockService::class);
        $container->singleton(ConnectionServiceInterface::class, $config['connection'] ?? ConnectionService::class);
        $container->singleton(DatabaseServiceInterface::class, $config['database'] ?? DatabaseService::class);
        $container->singleton(DispatcherServiceInterface::class, $config['dispatcher'] ?? DispatcherService::class);
        $container->singleton(JsonServiceInterface::class, $config['json'] ?? JsonService::class);
        $container->singleton(StateServiceInterface::class, $config['state'] ?? StateService::class);
        $container->singleton(TranslatorServiceInterface::class, $config['translator'] ?? TranslatorService::class);
        $container->singleton(UuidFactoryServiceInterface::class, $config['uuid'] ?? UuidFactoryService::class);
    }

    /**
     * Register math service.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerMathService(Container $container, array $config): void
    {
        $container->when($config['math'] ?? MathService::class)
            ->needs('$scale')
            ->giveConfig('wallet.math.scale', 64);

        $container->singleton(MathServiceInterface::class, $config['math'] ?? MathService::class);
    }

    /**
     * Register lock service.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerLockService(Container $container, array $config): void
    {
        $container->when($config['lock'] ?? LockService::class)
            ->needs('$seconds')
            ->giveConfig('wallet.lock.seconds', 1);

        $container->singleton(LockServiceInterface::class, $config['lock'] ?? LockService::class);
    }

    /**
     * Get the list of services provided by this factory.
     *
     * @return array<class-string> Array of service class names
     */
    public function provides(): array
    {
        return [
            ClockServiceInterface::class,
            ConnectionServiceInterface::class,
            DatabaseServiceInterface::class,
            DispatcherServiceInterface::class,
            JsonServiceInterface::class,
            StateServiceInterface::class,
            TranslatorServiceInterface::class,
            UuidFactoryServiceInterface::class,
            MathServiceInterface::class,
            LockServiceInterface::class,
        ];
    }
}