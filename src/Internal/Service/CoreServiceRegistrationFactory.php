<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Service;

use Bavix\Wallet\Services\AssistantService;
use Bavix\Wallet\Services\AssistantServiceInterface;
use Bavix\Wallet\Services\AtmService;
use Bavix\Wallet\Services\AtmServiceInterface;
use Bavix\Wallet\Services\AtomicService;
use Bavix\Wallet\Services\AtomicServiceInterface;
use Bavix\Wallet\Services\BasketService;
use Bavix\Wallet\Services\BasketServiceInterface;
use Bavix\Wallet\Services\BookkeeperService;
use Bavix\Wallet\Services\BookkeeperServiceInterface;
use Bavix\Wallet\Services\CastService;
use Bavix\Wallet\Services\CastServiceInterface;
use Bavix\Wallet\Services\ConsistencyService;
use Bavix\Wallet\Services\ConsistencyServiceInterface;
use Bavix\Wallet\Services\DiscountService;
use Bavix\Wallet\Services\DiscountServiceInterface;
use Bavix\Wallet\Services\EagerLoaderService;
use Bavix\Wallet\Services\EagerLoaderServiceInterface;
use Bavix\Wallet\Services\ExchangeService;
use Bavix\Wallet\Services\ExchangeServiceInterface;
use Bavix\Wallet\Services\FormatterService;
use Bavix\Wallet\Services\FormatterServiceInterface;
use Bavix\Wallet\Services\PrepareService;
use Bavix\Wallet\Services\PrepareServiceInterface;
use Bavix\Wallet\Services\PurchaseService;
use Bavix\Wallet\Services\PurchaseServiceInterface;
use Bavix\Wallet\Services\RegulatorService;
use Bavix\Wallet\Services\RegulatorServiceInterface;
use Bavix\Wallet\Services\TaxService;
use Bavix\Wallet\Services\TaxServiceInterface;
use Bavix\Wallet\Services\TransactionService;
use Bavix\Wallet\Services\TransactionServiceInterface;
use Bavix\Wallet\Services\TransferService;
use Bavix\Wallet\Services\TransferServiceInterface;
use Bavix\Wallet\Services\WalletService;
use Bavix\Wallet\Services\WalletServiceInterface;
use Bavix\Wallet\Internal\Decorator\StorageServiceLockDecorator;
use Bavix\Wallet\Internal\Service\StorageServiceInterface;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering core wallet services.
 *
 * This factory handles the registration of all core wallet services,
 * reducing code duplication in the service provider.
 */
final class CoreServiceRegistrationFactory implements ServiceRegistrationFactoryInterface
{
    /**
     * Register core services with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerBasicServices($container, $config);
        $this->registerBookkeeperService($container, $config);
        $this->registerRegulatorService($container, $config);
    }

    /**
     * Register basic services.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerBasicServices(Container $container, array $config): void
    {
        $container->singleton(AssistantServiceInterface::class, $config['assistant'] ?? AssistantService::class);
        $container->singleton(AtmServiceInterface::class, $config['atm'] ?? AtmService::class);
        $container->singleton(AtomicServiceInterface::class, $config['atomic'] ?? AtomicService::class);
        $container->singleton(BasketServiceInterface::class, $config['basket'] ?? BasketService::class);
        $container->singleton(CastServiceInterface::class, $config['cast'] ?? CastService::class);
        $container->singleton(ConsistencyServiceInterface::class, $config['consistency'] ?? ConsistencyService::class);
        $container->singleton(DiscountServiceInterface::class, $config['discount'] ?? DiscountService::class);
        $container->singleton(EagerLoaderServiceInterface::class, $config['eager_loader'] ?? EagerLoaderService::class);
        $container->singleton(ExchangeServiceInterface::class, $config['exchange'] ?? ExchangeService::class);
        $container->singleton(FormatterServiceInterface::class, $config['formatter'] ?? FormatterService::class);
        $container->singleton(PrepareServiceInterface::class, $config['prepare'] ?? PrepareService::class);
        $container->singleton(PurchaseServiceInterface::class, $config['purchase'] ?? PurchaseService::class);
        $container->singleton(TaxServiceInterface::class, $config['tax'] ?? TaxService::class);
        $container->singleton(TransactionServiceInterface::class, $config['transaction'] ?? TransactionService::class);
        $container->singleton(TransferServiceInterface::class, $config['transfer'] ?? TransferService::class);
        $container->singleton(WalletServiceInterface::class, $config['wallet'] ?? WalletService::class);
    }

    /**
     * Register bookkeeper service with storage decorator.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerBookkeeperService(Container $container, array $config): void
    {
        $cache = $config['cache'] ?? [];

        $container->when(StorageServiceLockDecorator::class)
            ->needs(StorageServiceInterface::class)
            ->give(function () use ($container, $cache) {
                return $container->make(
                    'wallet.internal.storage',
                    [
                        'cacheRepository' => $container->get(CacheFactory::class)
                            ->store($cache['driver'] ?? 'array'),
                    ],
                );
            });

        $container->when($config['bookkeeper'] ?? BookkeeperService::class)
            ->needs(StorageServiceInterface::class)
            ->give(StorageServiceLockDecorator::class);

        $container->singleton(BookkeeperServiceInterface::class, $config['bookkeeper'] ?? BookkeeperService::class);
    }

    /**
     * Register regulator service with storage.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerRegulatorService(Container $container, array $config): void
    {
        $container->when($config['regulator'] ?? RegulatorService::class)
            ->needs(StorageServiceInterface::class)
            ->give(function () use ($container) {
                return $container->make(
                    'wallet.internal.storage',
                    [
                        'cacheRepository' => clone $container->make(CacheFactory::class)
                            ->store('array'),
                    ],
                );
            });

        $container->singleton(RegulatorServiceInterface::class, $config['regulator'] ?? RegulatorService::class);
    }

    /**
     * Get the list of services provided by this factory.
     *
     * @return array<class-string> Array of service class names
     */
    public function provides(): array
    {
        return [
            AssistantServiceInterface::class,
            AtmServiceInterface::class,
            AtomicServiceInterface::class,
            BasketServiceInterface::class,
            CastServiceInterface::class,
            ConsistencyServiceInterface::class,
            DiscountServiceInterface::class,
            EagerLoaderServiceInterface::class,
            ExchangeServiceInterface::class,
            FormatterServiceInterface::class,
            PrepareServiceInterface::class,
            PurchaseServiceInterface::class,
            TaxServiceInterface::class,
            TransactionServiceInterface::class,
            TransferServiceInterface::class,
            WalletServiceInterface::class,
            BookkeeperServiceInterface::class,
            RegulatorServiceInterface::class,
        ];
    }
}