# Multi-Asset Wallet System

The Bavix Laravel Wallet library now supports a **truly agnostic multi-asset system** that allows you to define custom asset types with separate tables for wallets, transactions, and transfers. This system is designed to be completely flexible and extensible, enabling you to create any number of asset types without hardcoding them into the library.

## Overview

The multi-asset system consists of several key components:

- **AssetConfig**: Configuration for a single asset type
- **AssetTypeRegistry**: Manages multiple asset type configurations
- **AssetContext**: Provides context-aware operations
- **AssetRepositoryFactory**: Creates context-aware repositories

## Quick Start

### 1. Define Your Asset Types

Create a configuration file (e.g., `config/wallet-assets.php`) to define your asset types:

```php
<?php

return [
    'bonds' => [
        'wallet_table' => 'bond_wallets',
        'transaction_table' => 'bond_transactions',
        'transfer_table' => 'bond_transfers',
        'wallet_model' => 'App\Models\BondWallet',
        'transaction_model' => 'App\Models\BondTransaction',
        'transfer_model' => 'App\Models\BondTransfer',
        'migration_path' => '/path/to/migrations',
        'meta' => [
            'description' => 'Bond assets for trading',
            'currency' => 'USD'
        ]
    ],

    'shares' => [
        'wallet_table' => 'share_wallets',
        'transaction_table' => 'share_transactions',
        'transfer_table' => 'share_transfers',
        'wallet_model' => 'App\Models\ShareWallet',
        'transaction_model' => 'App\Models\ShareTransaction',
        'transfer_model' => 'App\Models\ShareTransfer',
        'meta' => [
            'description' => 'Company shares',
            'tradable' => true
        ]
    ],

    'inventory' => [
        'wallet_table' => 'inventory_wallets',
        'transaction_table' => 'inventory_transactions',
        'transfer_table' => 'inventory_transfers',
        'wallet_model' => 'App\Models\InventoryWallet',
        'transaction_model' => 'App\Models\InventoryTransaction',
        'transfer_model' => 'App\Models\InventoryTransfer',
        'meta' => [
            'description' => 'Physical inventory items',
            'unit_type' => 'pieces'
        ]
    ]
];
```

### 2. Register Asset Types in Service Provider

Update your `config/wallet.php` to include the asset configurations:

```php
<?php

return [
    // ... existing wallet configuration ...

    'assets' => require __DIR__ . '/wallet-assets.php',
];
```

### 3. Create Your Models

Create the model classes for each asset type. For example, for bonds:

```php
<?php

namespace App\Models;

use Bavix\Wallet\Models\Wallet as BaseWallet;

class BondWallet extends BaseWallet
{
    protected $table = 'bond_wallets';

    // Add any bond-specific functionality
    public function getBondType()
    {
        return $this->meta['bond_type'] ?? null;
    }
}
```

```php
<?php

namespace App\Models;

use Bavix\Wallet\Models\Transaction as BaseTransaction;

class BondTransaction extends BaseTransaction
{
    protected $table = 'bond_transactions';

    // Add any bond-specific functionality
}
```

```php
<?php

namespace App\Models;

use Bavix\Wallet\Models\Transfer as BaseTransfer;

class BondTransfer extends BaseTransfer
{
    protected $table = 'bond_transfers';

    // Add any transfer-specific functionality
}
```

### 4. Create Database Migrations

Create the necessary database tables for each asset type:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bond_wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('holder');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('description')->nullable();
            $table->decimal('balance', 64, 0)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['holder_type', 'holder_id', 'slug']);
        });

        Schema::create('bond_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('payable');
            $table->morphs('wallet');
            $table->string('type');
            $table->decimal('amount', 64, 0);
            $table->boolean('confirmed');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['wallet_type', 'wallet_id']);
        });

        Schema::create('bond_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->morphs('from');
            $table->morphs('to');
            $table->string('status');
            $table->string('status_last')->nullable();
            $table->json('deposit')->nullable();
            $table->json('withdraw')->nullable();
            $table->json('discount')->nullable();
            $table->json('fee')->nullable();
            $table->timestamps();

            $table->index(['from_type', 'from_id']);
            $table->index(['to_type', 'to_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bond_transfers');
        Schema::dropIfExists('bond_transactions');
        Schema::dropIfExists('bond_wallets');
    }
};
```

## Usage

### Context-Aware Operations

The multi-asset system uses context to determine which asset type to operate on:

```php
use Bavix\Wallet\Internal\Asset\AssetContextInterface;
use Bavix\Wallet\Internal\Asset\AssetRepositoryFactoryInterface;

class BondService
{
    public function __construct(
        private AssetContextInterface $assetContext,
        private AssetRepositoryFactoryInterface $repositoryFactory
    ) {}

    public function createBondWallet($holder, $name)
    {
        return $this->assetContext->withContext('bonds', function () use ($holder, $name) {
            $walletRepo = $this->repositoryFactory->createWalletRepository();
            return $walletRepo->create([
                'holder_type' => get_class($holder),
                'holder_id' => $holder->id,
                'name' => $name,
                'slug' => 'bond-wallet'
            ]);
        });
    }

    public function depositBonds($wallet, $amount)
    {
        return $this->assetContext->withContext('bonds', function () use ($wallet, $amount) {
            $transactionRepo = $this->repositoryFactory->createTransactionRepository();
            // Create deposit transaction
        });
    }
}
```

### Direct Asset Type Specification

You can also specify the asset type directly without setting context:

```php
public function transferShares($fromWallet, $toWallet, $amount)
{
    $transferRepo = $this->repositoryFactory->createTransferRepository('shares');
    // Create share transfer
}
```

### Asset Type Registry

You can interact with the asset type registry directly:

```php
use Bavix\Wallet\Internal\Asset\AssetTypeRegistryInterface;

class AssetManager
{
    public function __construct(
        private AssetTypeRegistryInterface $assetRegistry
    ) {}

    public function getAssetInfo($assetType)
    {
        $config = $this->assetRegistry->get($assetType);
        if (!$config) {
            throw new \InvalidArgumentException("Asset type '{$assetType}' not found");
        }

        return [
            'type' => $config->getAssetType(),
            'tables' => [
                'wallet' => $config->getWalletTable(),
                'transaction' => $config->getTransactionTable(),
                'transfer' => $config->getTransferTable(),
            ],
            'models' => [
                'wallet' => $config->getWalletModel(),
                'transaction' => $config->getTransactionModel(),
                'transfer' => $config->getTransferModel(),
            ],
            'meta' => $config->getMeta()
        ];
    }

    public function listAssetTypes()
    {
        return $this->assetRegistry->getAssetTypeNames();
    }
}
```

## Advanced Features

### Dynamic Asset Type Registration

You can register asset types dynamically at runtime:

```php
$assetRegistry = app(AssetTypeRegistryInterface::class);

$assetRegistry->register('crypto', [
    'wallet_table' => 'crypto_wallets',
    'transaction_table' => 'crypto_transactions',
    'transfer_table' => 'crypto_transfers',
    'wallet_model' => 'App\Models\CryptoWallet',
    'transaction_model' => 'App\Models\CryptoTransaction',
    'transfer_model' => 'App\Models\CryptoTransfer',
    'meta' => [
        'description' => 'Cryptocurrency assets',
        'blockchain' => 'ethereum'
    ]
]);
```

### Asset Type Validation

```php
if ($assetRegistry->has('bonds')) {
    $config = $assetRegistry->get('bonds');
    // Use the configuration
}
```

### Fallback to Default

If no asset context is set, the system falls back to the default asset type:

```php
// This will use the default asset type (usually 'default')
$walletRepo = $repositoryFactory->createWalletRepository();
```

## Configuration Options

### Required Configuration Keys

Each asset type configuration must include:

- `wallet_table`: Table name for wallets
- `transaction_table`: Table name for transactions
- `transfer_table`: Table name for transfers
- `wallet_model`: Fully qualified model class name
- `transaction_model`: Fully qualified model class name
- `transfer_model`: Fully qualified model class name

### Optional Configuration Keys

- `migration_path`: Path to migration files (for future use)
- `meta`: Array of custom metadata

### Default Asset Type

The system automatically sets up a default asset type using the standard Bavix wallet tables:

- `wallets` table
- `transactions` table
- `transfers` table
- `Bavix\Wallet\Models\Wallet` model
- `Bavix\Wallet\Models\Transaction` model
- `Bavix\Wallet\Models\Transfer` model

## Best Practices

### 1. Consistent Naming

Use consistent naming patterns for your asset types:

```php
// Good
'bonds' => [...],
'shares' => [...],
'inventory' => [...],

// Avoid
'BondAssets' => [...],
'SHARES' => [...],
'InventoryItems' => [...],
```

### 2. Model Inheritance

Extend the base Bavix models to maintain compatibility:

```php
class BondWallet extends \Bavix\Wallet\Models\Wallet
{
    protected $table = 'bond_wallets';

    // Add bond-specific methods
}
```

### 3. Context Management

Use the context system for related operations:

```php
$this->assetContext->withContext('bonds', function () {
    // All operations in this callback will use bond assets
    $wallet = $this->createWallet();
    $this->deposit($wallet, 100);
    $this->transfer($wallet, $otherWallet, 50);
});
```

### 4. Error Handling

Always check if asset types exist before using them:

```php
if (!$this->assetRegistry->has($assetType)) {
    throw new \InvalidArgumentException("Asset type '{$assetType}' not registered");
}
```

## Migration from Single-Table System

If you're migrating from the single-table system:

1. **Keep existing functionality**: The default asset type maintains backward compatibility
2. **Gradual migration**: Add new asset types alongside existing ones
3. **Update services**: Modify services to use context-aware operations
4. **Test thoroughly**: Ensure all existing functionality continues to work

## Troubleshooting

### Common Issues

1. **Model class not found**: Ensure model classes exist and are autoloaded
2. **Table doesn't exist**: Run migrations for your asset type tables
3. **Context not set**: Use `withContext()` or specify asset type explicitly
4. **Asset type not registered**: Check configuration and registration

### Debugging

```php
// Check registered asset types
$assetTypes = $assetRegistry->getAssetTypeNames();
dd($assetTypes);

// Check current context
$context = $assetContext->getContext();
dd($context);

// Check asset configuration
$config = $assetRegistry->get('bonds');
dd($config->toArray());
```

## API Reference

### AssetConfig

- `getAssetType()`: Get asset type identifier
- `getWalletTable()`: Get wallet table name
- `getTransactionTable()`: Get transaction table name
- `getTransferTable()`: Get transfer table name
- `getWalletModel()`: Get wallet model class
- `getTransactionModel()`: Get transaction model class
- `getTransferModel()`: Get transfer model class
- `getMeta()`: Get all metadata
- `getMetaValue($key, $default = null)`: Get specific metadata value
- `hasMetaValue($key)`: Check if metadata key exists

### AssetTypeRegistry

- `register($assetType, $config)`: Register asset type from array
- `registerConfig($config)`: Register asset type from AssetConfig object
- `get($assetType)`: Get asset configuration
- `has($assetType)`: Check if asset type is registered
- `getAll()`: Get all registered asset types
- `remove($assetType)`: Remove asset type registration
- `clear()`: Clear all registrations
- `setDefault($config)`: Set default asset type
- `getDefault()`: Get default asset type configuration
- `loadFromConfig($configs)`: Load multiple asset types from configuration

### AssetContext

- `setContext($assetType)`: Set current asset context
- `getContext()`: Get current asset context
- `clearContext()`: Clear current context
- `hasContext()`: Check if context is set
- `withContext($assetType, $callback)`: Execute callback with specific context

### AssetRepositoryFactory

- `createWalletRepository($assetType = null)`: Create wallet repository
- `createTransactionRepository($assetType = null)`: Create transaction repository
- `createTransferRepository($assetType = null)`: Create transfer repository
- `getAssetConfig($assetType = null)`: Get asset configuration
- `isAssetTypeSupported($assetType)`: Check if asset type is supported