<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Implementation of AssetContextInterface for managing asset context.
 *
 * This class provides thread-safe asset context management for the
 * multi-asset wallet system.
 */
final class AssetContext implements AssetContextInterface
{
    /**
     * @var string|null Current asset context
     */
    private ?string $context = null;

    /**
     * Set the current asset context.
     *
     * @param string $assetType The asset type identifier
     * @return void
     */
    public function setContext(string $assetType): void
    {
        $this->context = $assetType;
    }

    /**
     * Get the current asset context.
     *
     * @return string|null The current asset type identifier or null if not set
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * Clear the current asset context.
     *
     * @return void
     */
    public function clearContext(): void
    {
        $this->context = null;
    }

    /**
     * Check if a context is currently set.
     *
     * @return bool True if a context is set
     */
    public function hasContext(): bool
    {
        return $this->context !== null;
    }

    /**
     * Execute a callback with a specific asset context.
     *
     * @param string $assetType The asset type identifier
     * @param callable $callback The callback to execute
     * @return mixed The result of the callback
     */
    public function withContext(string $assetType, callable $callback): mixed
    {
        $previousContext = $this->context;

        try {
            $this->setContext($assetType);
            return $callback();
        } finally {
            if ($previousContext !== null) {
                $this->setContext($previousContext);
            } else {
                $this->clearContext();
            }
        }
    }
}