<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Interface for managing asset context in the multi-asset wallet system.
 *
 * This interface provides methods for setting and retrieving the current
 * asset context for operations.
 */
interface AssetContextInterface
{
    /**
     * Set the current asset context.
     *
     * @param string $assetType The asset type identifier
     * @return void
     */
    public function setContext(string $assetType): void;

    /**
     * Get the current asset context.
     *
     * @return string|null The current asset type identifier or null if not set
     */
    public function getContext(): ?string;

    /**
     * Clear the current asset context.
     *
     * @return void
     */
    public function clearContext(): void;

    /**
     * Check if a context is currently set.
     *
     * @return bool True if a context is set
     */
    public function hasContext(): bool;

    /**
     * Execute a callback with a specific asset context.
     *
     * @param string $assetType The asset type identifier
     * @param callable $callback The callback to execute
     * @return mixed The result of the callback
     */
    public function withContext(string $assetType, callable $callback): mixed;
}