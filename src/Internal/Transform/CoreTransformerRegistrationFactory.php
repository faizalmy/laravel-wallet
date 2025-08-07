<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Transform;

use Illuminate\Contracts\Container\Container;

/**
 * Factory for registering core transformers.
 *
 * This factory handles the registration of all core transformers,
 * reducing code duplication in the service provider.
 */
final class CoreTransformerRegistrationFactory implements TransformerRegistrationFactoryInterface
{
    /**
     * Register core transformers with the container.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    public function register(Container $container, array $config = []): void
    {
        $this->registerDtoTransformers($container, $config);
    }

    /**
     * Register DTO transformers.
     *
     * @param Container $container The Laravel container instance
     * @param array<string, mixed> $config The configuration array
     * @return void
     */
    private function registerDtoTransformers(Container $container, array $config): void
    {
        $container->singleton(
            TransactionDtoTransformerInterface::class,
            $config['transaction'] ?? TransactionDtoTransformer::class
        );

        $container->singleton(
            TransferDtoTransformerInterface::class,
            $config['transfer'] ?? TransferDtoTransformer::class
        );
    }

    /**
     * Get the list of transformers provided by this factory.
     *
     * @return array<class-string> Array of transformer class names
     */
    public function provides(): array
    {
        return [
            TransactionDtoTransformerInterface::class,
            TransferDtoTransformerInterface::class,
        ];
    }
}