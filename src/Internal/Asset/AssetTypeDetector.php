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
     * Detect asset type from wallet creation data.
     *
     * This method analyzes wallet creation data to determine the appropriate
     * asset type using multiple detection strategies.
     *
     * @param array<string, mixed> $data The wallet creation data
     * @return string|null The detected asset type or null if not found
     */
    public function detectFromWalletData(array $data): ?string
    {
        // Strategy 1: Explicit asset type in metadata
        if (isset($data['meta']['asset_type'])) {
            $assetType = $data['meta']['asset_type'];
            if ($this->registry->has($assetType)) {
                return $assetType;
            }
        }

        // Strategy 2: Detect from slug pattern
        if (isset($data['slug'])) {
            $assetType = $this->detectFromSlug($data['slug']);
            if ($assetType !== null) {
                return $assetType;
            }
        }

        // Strategy 3: Detect from name pattern
        if (isset($data['name'])) {
            $assetType = $this->detectFromName($data['name']);
            if ($assetType !== null) {
                return $assetType;
            }
        }

        // Strategy 4: Detect from required fields in metadata
        if (isset($data['meta'])) {
            $assetType = $this->detectFromRequiredFields($data['meta']);
            if ($assetType !== null) {
                return $assetType;
            }
        }

        return null;
    }

    /**
     * Detect asset type from slug patterns.
     *
     * @param string $slug The wallet slug
     * @return string|null The detected asset type or null if not found
     */
    private function detectFromSlug(string $slug): ?string
    {
        $patterns = [
            '/^share_/' => 'shares',
            '/^bond_/' => 'bonds',
            '/^inventory_/' => 'inventory',
            '/^crypto_/' => 'crypto',
            '/^commodity_/' => 'commodity',
            '/^currency_/' => 'currency',
        ];

        foreach ($patterns as $pattern => $assetType) {
            if (preg_match($pattern, $slug) && $this->registry->has($assetType)) {
                return $assetType;
            }
        }

        return null;
    }

    /**
     * Detect asset type from name patterns.
     *
     * @param string $name The wallet name
     * @return string|null The detected asset type or null if not found
     */
    private function detectFromName(string $name): ?string
    {
        $patterns = [
            '/\bshare\b/i' => 'shares',
            '/\bbond\b/i' => 'bonds',
            '/\binventory\b/i' => 'inventory',
            '/\bcrypto\b/i' => 'crypto',
            '/\bcommodity\b/i' => 'commodity',
            '/\bcurrency\b/i' => 'currency',
        ];

        foreach ($patterns as $pattern => $assetType) {
            if (preg_match($pattern, $name) && $this->registry->has($assetType)) {
                return $assetType;
            }
        }

        return null;
    }

    /**
     * Detect asset type from required fields in metadata.
     *
     * @param array<string, mixed> $meta The metadata array
     * @return string|null The detected asset type or null if not found
     */
    private function detectFromRequiredFields(array $meta): ?string
    {
        $fieldMappings = [
            'share_id' => 'shares',
            'bond_id' => 'bonds',
            'item_id' => 'inventory',
            'crypto_address' => 'crypto',
            'commodity_id' => 'commodity',
            'currency_code' => 'currency',
        ];

        foreach ($fieldMappings as $field => $assetType) {
            if (isset($meta[$field]) && $this->registry->has($assetType)) {
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