<?php

declare(strict_types=1);

namespace Bavix\Wallet\Internal\Asset;

/**
 * Service for automatically detecting asset types from various sources.
 * Provides agnostic detection strategies that work with any asset type.
 */
final class AssetTypeDetector
{
    public function __construct(
        private readonly AssetTypeRegistryInterface $registry
    ) {
    }

    /**
     * Detect asset type from wallet model.
     *
     * @param object $wallet The wallet model instance
     * @return string|null The detected asset type or null if not found
     */
    public function detectFromWallet(object $wallet): ?string
    {
        // Strategy 1: Check if wallet has explicit asset type method
        if (method_exists($wallet, 'getAssetType')) {
            $assetType = $wallet->getAssetType();
            if ($assetType !== null && $this->registry->has($assetType)) {
                return $assetType;
            }
        }

        // Strategy 2: Detect from table name
        $tableName = $wallet->getTable();
        $assetType = $this->registry->resolveFromTable($tableName);
        if ($assetType !== null) {
            return $assetType;
        }

        // Strategy 3: Detect from model class
        $modelClass = get_class($wallet);
        $assetType = $this->registry->resolveFromModel($modelClass);
        if ($assetType !== null) {
            return $assetType;
        }

        // Strategy 4: Check metadata if available
        if (property_exists($wallet, 'meta') && is_array($wallet->meta)) {
            if (isset($wallet->meta['asset_type'])) {
                $assetType = $wallet->meta['asset_type'];
                if ($this->registry->has($assetType)) {
                    return $assetType;
                }
            }
        }

        return null;
    }

    /**
     * Detect asset type from holder model.
     *
     * @param object $holder The holder model instance
     * @return string|null The detected asset type or null if not found
     */
    public function detectFromHolder(object $holder): ?string
    {
        // Strategy 1: Check if holder has default asset type method
        if (method_exists($holder, 'getDefaultAssetType')) {
            $assetType = $holder->getDefaultAssetType();
            if ($assetType !== null && $this->registry->has($assetType)) {
                return $assetType;
            }
        }

        // Strategy 2: Detect from holder class
        $holderClass = get_class($holder);
        $assetType = $this->registry->resolveFromModel($holderClass);
        if ($assetType !== null) {
            return $assetType;
        }

        // Strategy 3: Check holder metadata if available
        if (property_exists($holder, 'meta') && is_array($holder->meta)) {
            if (isset($holder->meta['default_asset_type'])) {
                $assetType = $holder->meta['default_asset_type'];
                if ($this->registry->has($assetType)) {
                    return $assetType;
                }
            }
        }

        return null;
    }

    /**
     * Detect asset type from attributes array.
     *
     * @param array<string, mixed> $attributes The attributes array
     * @return string|null The detected asset type or null if not found
     */
    public function detectFromAttributes(array $attributes): ?string
    {
        if (isset($attributes['asset_type']) && is_string($attributes['asset_type'])) {
            $assetType = $attributes['asset_type'];
            if ($this->registry->has($assetType)) {
                return $assetType;
            }
        }

        return null;
    }

    /**
     * Detect asset type from multiple sources with priority order.
     *
     * @param object|null $wallet The wallet model instance
     * @param object|null $holder The holder model instance
     * @param array<string, mixed> $attributes The attributes array
     * @return string|null The detected asset type or null if not found
     */
    public function detect(?object $wallet = null, ?object $holder = null, array $attributes = []): ?string
    {
        // Priority order: wallet > holder > attributes > default

        if ($wallet !== null) {
            $assetType = $this->detectFromWallet($wallet);
            if ($assetType !== null) {
                return $assetType;
            }
        }

        if ($holder !== null) {
            $assetType = $this->detectFromHolder($holder);
            if ($assetType !== null) {
                return $assetType;
            }
        }

        if (!empty($attributes)) {
            $assetType = $this->detectFromAttributes($attributes);
            if ($assetType !== null) {
                return $assetType;
            }
        }

        // Fall back to default asset type
        $defaultConfig = $this->registry->getDefault();
        return $defaultConfig?->getAssetType();
    }

    /**
     * Get all available detection strategies for debugging.
     *
     * @param object|null $wallet The wallet model instance
     * @param object|null $holder The holder model instance
     * @param array<string, mixed> $attributes The attributes array
     * @return array<string, mixed> Debug information about detection strategies
     */
    public function getDetectionDebugInfo(?object $wallet = null, ?object $holder = null, array $attributes = []): array
    {
        $debug = [
            'strategies' => [],
            'available_asset_types' => array_keys($this->registry->getAll()),
        ];

        if ($wallet !== null) {
            $debug['strategies']['wallet'] = [
                'table_name' => $wallet->getTable(),
                'model_class' => get_class($wallet),
                'has_get_asset_type_method' => method_exists($wallet, 'getAssetType'),
                'detected_asset_type' => $this->detectFromWallet($wallet),
            ];
        }

        if ($holder !== null) {
            $debug['strategies']['holder'] = [
                'model_class' => get_class($holder),
                'has_get_default_asset_type_method' => method_exists($holder, 'getDefaultAssetType'),
                'detected_asset_type' => $this->detectFromHolder($holder),
            ];
        }

        if (!empty($attributes)) {
            $debug['strategies']['attributes'] = [
                'has_asset_type_key' => isset($attributes['asset_type']),
                'asset_type_value' => $attributes['asset_type'] ?? null,
                'detected_asset_type' => $this->detectFromAttributes($attributes),
            ];
        }

        $debug['final_detected_asset_type'] = $this->detect($wallet, $holder, $attributes);

        return $debug;
    }
}