<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Service;

use Bavix\Wallet\Internal\Asset\AssetContext;
use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactory;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistry;
use Bavix\Wallet\Internal\Asset\AssetTypeRegistryInterface;
use Bavix\Wallet\Internal\Assembler\AssemblerRegistrationFactoryInterface;
use Bavix\Wallet\Internal\Assembler\CoreAssemblerRegistrationFactory;
use Bavix\Wallet\Internal\Events\CoreEventRegistrationFactory;
use Bavix\Wallet\Internal\Events\EventRegistrationFactoryInterface;
use Bavix\Wallet\Internal\Repository\CoreRepositoryRegistrationFactory;
use Bavix\Wallet\Internal\Repository\RepositoryRegistrationFactoryInterface;
use Bavix\Wallet\Internal\Transform\CoreTransformerRegistrationFactory;
use Bavix\Wallet\Internal\Transform\TransformerRegistrationFactoryInterface;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Transfer;
use Bavix\Wallet\Models\Wallet;
use Bavix\Wallet\Services\AssetAwareWalletServiceInterface;
use Illuminate\Contracts\Container\Container;

/**
 * Master orchestrator for service provider registration.
 *
 * This class manages all factory registrations and provides a clean interface
 * for the service provider, reducing complexity and improving maintainability.
 */
final class ServiceProviderOrchestrator
{
    /**
     * Register all core services with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function registerAll(Container $container, array $config = []): void
    {
        // Temporarily disable asset management to test if it's causing the issue
        // if (!empty($config['asset_management'])) {
        //     $this->registerAssetManagement($container, $config['asset_management']);
        //     $this->registerAssetAwareServices($container);
        // }

        $this->registerInternalServices($container, $config['internal'] ?? []);
        $this->registerRepositories($container, $config['repositories'] ?? []);
        $this->registerServices($container, $config['services'] ?? [], $config['cache'] ?? []);
        $this->registerAssemblers($container, $config['assemblers'] ?? []);
        $this->registerTransformers($container, $config['transformers'] ?? []);
        $this->registerEvents($container, $config['events'] ?? []);
        $this->registerModelBindings($container, $config['models'] ?? []);
        $this->registerApiHandlers($container);
    }

    /**
     * Register asset management components.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerAssetManagement(Container $container, array $config): void
    {
        $container->singleton(AssetTypeRegistryInterface::class, AssetTypeRegistry::class);
        $container->singleton(AssetContextInterface::class, AssetContext::class);
        $container->singleton(AssetRepositoryFactoryInterface::class, AssetRepositoryFactory::class);

        // Load asset configurations if provided, otherwise use default
        $registry = $container->make(AssetTypeRegistryInterface::class);
        if (!empty($config)) {
            $registry->loadFromConfig($config);
        } else {
            // Register default asset configuration for backward compatibility
            $defaultConfig = [
                'default' => [
                    'wallet_table' => 'wallets',
                    'transaction_table' => 'transactions',
                    'transfer_table' => 'transfers',
                    'wallet_model' => \Bavix\Wallet\Models\Wallet::class,
                    'transaction_model' => \Bavix\Wallet\Models\Transaction::class,
                    'transfer_model' => \Bavix\Wallet\Models\Transfer::class,
                ]
            ];
            $registry->loadFromConfig($defaultConfig);
        }
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
        $factory = new InternalServiceRegistrationFactory();
        $factory->register($container, $config);
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
        $factory = new CoreRepositoryRegistrationFactory();
        $factory->register($container, $config);
    }

    /**
     * Register core services.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @param array<string, mixed> $cache The cache configuration
     * @return void
     */
    private function registerServices(Container $container, array $config, array $cache): void
    {
        $factory = new CoreServiceRegistrationFactory();
        $factory->register($container, array_merge($config, ['cache' => $cache]));
    }

    /**
     * Register assemblers.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerAssemblers(Container $container, array $config): void
    {
        $factory = new CoreAssemblerRegistrationFactory();
        $factory->register($container, $config);
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
        $factory = new CoreTransformerRegistrationFactory();
        $factory->register($container, $config);
    }

    /**
     * Register events.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerEvents(Container $container, array $config): void
    {
        $factory = new CoreEventRegistrationFactory();
        $factory->register($container, $config);
    }

    /**
     * Register asset-aware services.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerAssetAwareServices(Container $container): void
    {
        $factory = new AssetAwareServiceRegistrationFactory();
        $factory->register($container);
    }

    /**
     * Register model bindings.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerModelBindings(Container $container, array $config): void
    {
        $container->bind(Transaction::class, $config['transaction']['model'] ?? \Bavix\Wallet\Models\Transaction::class);
        $container->bind(Transfer::class, $config['transfer']['model'] ?? \Bavix\Wallet\Models\Transfer::class);
        $container->bind(Wallet::class, $config['wallet']['model'] ?? \Bavix\Wallet\Models\Wallet::class);
    }

    /**
     * Register API handlers.
     *
     * @param Container $container The Laravel container instance
     * @return void
     */
    private function registerApiHandlers(Container $container): void
    {
        $container->bind(\Bavix\Wallet\External\Api\TransactionQueryHandlerInterface::class, \Bavix\Wallet\External\Api\TransactionQueryHandler::class);
        $container->bind(\Bavix\Wallet\External\Api\TransferQueryHandlerInterface::class, \Bavix\Wallet\External\Api\TransferQueryHandler::class);
    }

    /**
     * Get all provided services.
     *
     * @return array<class-string> Array of all provided service class names
     */
    public function provides(): array
    {
        $factories = [
            new InternalServiceRegistrationFactory(),
            new CoreRepositoryRegistrationFactory(),
            new CoreServiceRegistrationFactory(),
            new CoreAssemblerRegistrationFactory(),
            new CoreTransformerRegistrationFactory(),
            new CoreEventRegistrationFactory(),
            new AssetAwareServiceRegistrationFactory(),
        ];

        $services = [];
        foreach ($factories as $factory) {
            $services = array_merge($services, $factory->provides());
        }

        // Add asset management services
        $services[] = AssetTypeRegistryInterface::class;
        $services[] = AssetContextInterface::class;
        $services[] = AssetRepositoryFactoryInterface::class;

        // Add model bindings
        $services[] = Transaction::class;
        $services[] = Transfer::class;
        $services[] = Wallet::class;

        // Add API handlers
        $services[] = \Bavix\Wallet\External\Api\TransactionQueryHandlerInterface::class;
        $services[] = \Bavix\Wallet\External\Api\TransferQueryHandlerInterface::class;

        return array_unique($services);
    }
}