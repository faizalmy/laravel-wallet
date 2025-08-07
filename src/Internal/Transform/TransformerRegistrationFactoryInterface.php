<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Transform;

use Illuminate\Contracts\Container\Container;

/**
 * Interface for transformer registration factories.
 *
 * This interface provides a common contract for different types of transformer registration
 * factories, allowing for clean separation of concerns in the service provider.
 */
interface TransformerRegistrationFactoryInterface
{
    /**
     * Register transformers with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array for this factory
     * @return void
     */
    public function register(Container $container, array $config = []): void;

    /**
     * Get the list of transformers provided by this factory.
     *
     * @return array<class-string> Array of transformer class names
     */
    public function provides(): array;
}