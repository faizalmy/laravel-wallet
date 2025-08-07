<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Events;

use Illuminate\Contracts\Container\Container;

/**
 * Interface for event registration factories.
 *
 * This interface provides a common contract for different types of event registration
 * factories, allowing for clean separation of concerns in the service provider.
 */
interface EventRegistrationFactoryInterface
{
    /**
     * Register events with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array for this factory
     * @return void
     */
    public function register(Container $container, array $config = []): void;

    /**
     * Get the list of events provided by this factory.
     *
     * @return array<class-string> Array of event class names
     */
    public function provides(): array;
}