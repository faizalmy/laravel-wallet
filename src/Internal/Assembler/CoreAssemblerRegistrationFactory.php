<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Assembler;

use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering core assemblers.
 *
 * This factory handles the registration of all core assemblers,
 * reducing code duplication in the service provider.
 */
final class CoreAssemblerRegistrationFactory implements AssemblerRegistrationFactoryInterface
{
    /**
     * Register core assemblers with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerDtoAssemblers($container, $config);
        $this->registerEventAssemblers($container, $config);
        $this->registerQueryAssemblers($container, $config);
    }

    /**
     * Register DTO assemblers.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerDtoAssemblers(Container $container, array $config): void
    {
        $container->singleton(
            AvailabilityDtoAssemblerInterface::class,
            $config['availability'] ?? AvailabilityDtoAssembler::class
        );

        $container->singleton(ExtraDtoAssemblerInterface::class, $config['extra'] ?? ExtraDtoAssembler::class);

        $container->singleton(
            OptionDtoAssemblerInterface::class,
            $config['option'] ?? OptionDtoAssembler::class
        );

        $container->singleton(
            TransactionDtoAssemblerInterface::class,
            $config['transaction'] ?? TransactionDtoAssembler::class
        );

        $container->singleton(
            TransferLazyDtoAssemblerInterface::class,
            $config['transfer_lazy'] ?? TransferLazyDtoAssembler::class
        );

        $container->singleton(
            TransferDtoAssemblerInterface::class,
            $config['transfer'] ?? TransferDtoAssembler::class
        );
    }

    /**
     * Register event assemblers.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerEventAssemblers(Container $container, array $config): void
    {
        $container->singleton(
            BalanceUpdatedEventAssemblerInterface::class,
            $config['balance_updated_event'] ?? BalanceUpdatedEventAssembler::class
        );

        $container->singleton(
            WalletCreatedEventAssemblerInterface::class,
            $config['wallet_created_event'] ?? WalletCreatedEventAssembler::class
        );

        $container->singleton(
            TransactionCreatedEventAssemblerInterface::class,
            $config['transaction_created_event'] ?? TransactionCreatedEventAssembler::class
        );
    }

    /**
     * Register query assemblers.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerQueryAssemblers(Container $container, array $config): void
    {
        $container->singleton(
            TransactionQueryAssemblerInterface::class,
            $config['transaction_query'] ?? TransactionQueryAssembler::class
        );

        $container->singleton(
            TransferQueryAssemblerInterface::class,
            $config['transfer_query'] ?? TransferQueryAssembler::class
        );
    }

    /**
     * Get the list of assemblers provided by this factory.
     *
     * @return array<class-string> Array of assembler class names
     */
    public function provides(): array
    {
        return [
            AvailabilityDtoAssemblerInterface::class,
            ExtraDtoAssemblerInterface::class,
            OptionDtoAssemblerInterface::class,
            TransactionDtoAssemblerInterface::class,
            TransferLazyDtoAssemblerInterface::class,
            TransferDtoAssemblerInterface::class,
            BalanceUpdatedEventAssemblerInterface::class,
            WalletCreatedEventAssemblerInterface::class,
            TransactionCreatedEventAssemblerInterface::class,
            TransactionQueryAssemblerInterface::class,
            TransferQueryAssemblerInterface::class,
        ];
    }
}