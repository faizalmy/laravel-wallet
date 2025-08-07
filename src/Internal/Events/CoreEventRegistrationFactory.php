<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Events;

use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering core events.
 *
 * This factory handles the registration of all core events,
 * reducing code duplication in the service provider.
 */
final class CoreEventRegistrationFactory implements EventRegistrationFactoryInterface
{
    /**
     * Register core events with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerWalletEvents($container, $config);
        $this->registerTransactionEvents($container, $config);
    }

    /**
     * Register wallet events.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerWalletEvents(Container $container, array $config): void
    {
        $container->bind(
            BalanceUpdatedEventInterface::class,
            $config['balance_updated'] ?? BalanceUpdatedEvent::class
        );

        $container->bind(
            WalletCreatedEventInterface::class,
            $config['wallet_created'] ?? WalletCreatedEvent::class
        );
    }

    /**
     * Register transaction events.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerTransactionEvents(Container $container, array $config): void
    {
        $container->bind(
            TransactionCreatedEventInterface::class,
            $config['transaction_created'] ?? TransactionCreatedEvent::class
        );
    }

    /**
     * Get the list of events provided by this factory.
     *
     * @return array<class-string> Array of event class names
     */
    public function provides(): array
    {
        return [
            BalanceUpdatedEventInterface::class,
            WalletCreatedEventInterface::class,
            TransactionCreatedEventInterface::class,
        ];
    }
}