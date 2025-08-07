# Multi-Asset Wallet System

The Bavix Laravel Wallet library now supports a **truly agnostic multi-asset system** that allows you to define custom asset types with separate tables for wallets, transactions, and transfers. This system is designed to be completely flexible and extensible, enabling you to create any number of asset types without hardcoding them into the library.

## 🎯 Key Features

- **🔄 Transparent Integration**: Use standard Bavix methods (`deposit`, `withdraw`, `transfer`) without any code changes
- **🎯 Automatic Asset Detection**: Asset types are detected automatically from wallet models, holder models, or attributes
- **🔧 Agnostic Design**: Works with any asset type configuration you provide
- **⚡ Backward Compatible**: All existing functionality is preserved
- **🛡️ Context Management**: Asset context is properly managed and restored

## Overview

The multi-asset system consists of several key components:

- **AssetConfig**: Configuration for a single asset type
- **AssetTypeRegistry**: Manages multiple asset type configurations
- **AssetContext**: Provides context-aware operations
- **AssetRepositoryFactory**: Creates context-aware repositories
- **AssetTypeDetector**: Automatically detects asset types from various sources
- **AssetAwareTransactionService**: Transparent asset-aware transaction operations

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

### 🚀 Transparent Usage (Recommended)

The multi-asset system is **completely transparent** - you can use standard Bavix methods without any changes:

```php
// These work exactly as before, but now automatically use the correct asset tables
$wallet->deposit(100);                    // Automatically detected asset type
$wallet->withdraw(50);                    // Automatically detected asset type
$wallet->transfer($otherWallet, 25);      // Automatically detected asset type
$wallet->forceWithdraw(75);               // Automatically detected asset type
$wallet->safeTransfer($otherWallet, 10);  // Automatically detected asset type
```

### Asset Type Detection

The system automatically detects asset types from multiple sources in this priority order:

1. **Wallet Model**: Table name, class name, or explicit `getAssetType()` method
2. **Holder Model**: Default asset type methods or class-based detection
3. **Attributes**: Asset type specified in attributes array
4. **Default Fallback**: Falls back to default asset type if no detection

#### Detection Examples

```php
// Detection from table name
class BondWallet extends \Bavix\Wallet\Models\Wallet
{
    protected $table = 'bond_wallets'; // Automatically detected as 'bonds'
}

// Detection from explicit method
class ShareWallet extends \Bavix\Wallet\Models\Wallet
{
    public function getAssetType(): string
    {
        return 'shares';
    }
}

// Detection from holder model
class User extends \Illuminate\Foundation\Auth\User
{
    public function getDefaultAssetType(): string
    {
        return 'bonds';
    }
}

// Detection from attributes
$wallet = new BondWallet([
    'asset_type' => 'bonds',
    'name' => 'My Bond Wallet'
]);
```

### Context-Aware Operations (Advanced)

For advanced use cases, you can use context-aware operations:

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

### Debugging Asset Type Detection

You can debug asset type detection to understand how the system works:

```php
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;

class AssetDebugger
{
    public function __construct(
        private AssetTypeDetector $detector
    ) {}

    public function debugDetection($wallet, $holder = null, $attributes = [])
    {
        $debugInfo = $this->detector->getDetectionDebugInfo($wallet, $holder, $attributes);

        return [
            'detected_asset_type' => $debugInfo['final_detected_asset_type'],
            'detection_strategies' => $debugInfo['strategies'],
            'available_asset_types' => $debugInfo['available_asset_types']
        ];
    }
}

// Usage
$debugger = app(AssetDebugger::class);
$result = $debugger->debugDetection($bondWallet, $user);
dd($result);
```

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

### 1. Transparent Usage (Recommended)

Use standard Bavix methods for the best experience:

```php
// ✅ Recommended - Transparent and simple
$bondWallet->deposit(100);
$shareWallet->withdraw(50);
$bondWallet->transfer($shareWallet, 25);

// ❌ Not needed - The system handles this automatically
$bondWallet->depositWithAssetType(100, 'bonds');
```

### 2. Consistent Naming

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

### 3. Model Inheritance

Extend the base Bavix models to maintain compatibility and enable automatic detection:

```php
class BondWallet extends \Bavix\Wallet\Models\Wallet
{
    protected $table = 'bond_wallets'; // Automatically detected as 'bonds'

    // Add bond-specific methods
    public function getBondType()
    {
        return $this->meta['bond_type'] ?? null;
    }
}
```

### 4. Context Management (Advanced)

Use the context system for advanced scenarios where you need explicit control:

```php
$this->assetContext->withContext('bonds', function () {
    // All operations in this callback will use bond assets
    $wallet = $this->createWallet();
    $this->deposit($wallet, 100);
    $this->transfer($wallet, $otherWallet, 50);
});
```

**Note**: For most use cases, the transparent system handles this automatically.

### 5. Error Handling

Always check if asset types exist before using them:

```php
if (!$this->assetRegistry->has($assetType)) {
    throw new \InvalidArgumentException("Asset type '{$assetType}' not registered");
}
```

## Migration from Single-Table System

If you're migrating from the single-table system:

1. **✅ No Code Changes Required**: The transparent system works with existing code
2. **✅ Keep existing functionality**: The default asset type maintains backward compatibility
3. **✅ Gradual migration**: Add new asset types alongside existing ones
4. **✅ Test thoroughly**: Ensure all existing functionality continues to work

### Migration Example

```php
// Before (single-table system)
$user->deposit(100); // Uses default 'wallets' table

// After (multi-asset system) - NO CHANGES NEEDED!
$user->deposit(100); // Still works, uses default asset type

// Add new asset types
$bondWallet = new BondWallet(['name' => 'Bond Portfolio']);
$bondWallet->deposit(1000); // Automatically uses 'bond_wallets' table
```

## Service Binding Fix (v2.0+)

### Issue Resolved
The multi-asset system had a service binding conflict that prevented transfer operations from using asset-specific tables. This has been fixed in version 2.0+.

### What Was Fixed
- **Problem**: `TransferService` was not using `AssetAwareTransactionService` due to Laravel singleton binding precedence
- **Solution**: Changed `bind()` to `singleton()` in the service provider to properly override the service registration
- **Impact**: Transfer operations now automatically use the correct asset tables

### Verification
You can verify the fix is working by checking:

```php
// This should return AssetAwareTransactionService
$service = app(\Bavix\Wallet\Services\TransactionServiceInterface::class);
dd(get_class($service));

// Transfer operations should now work with asset-specific tables
$shareWallet->transfer($otherWallet, 100); // Uses share_transactions table
```

## Troubleshooting

### Common Issues

1. **Model class not found**: Ensure model classes exist and are autoloaded
2. **Table doesn't exist**: Run migrations for your asset type tables
3. **Asset type not detected**: Check table names, model classes, or add explicit `getAssetType()` method
4. **Asset type not registered**: Check configuration and registration
5. **Foreign key constraint violations**: Ensure wallet IDs exist in the correct asset tables
6. **Transfer operations still use default tables**: This was a service binding issue that has been fixed in the latest version

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

// Debug asset type detection
use Bavix\Wallet\Internal\Asset\AssetTypeDetector;

$detector = app(AssetTypeDetector::class);
$debugInfo = $detector->getDetectionDebugInfo($wallet, $holder, $attributes);
dd($debugInfo);

// Verify service binding is correct
$transactionService = app(\Bavix\Wallet\Services\TransactionServiceInterface::class);
dd(get_class($transactionService)); // Should be AssetAwareTransactionService

// Check if TransferService is using AssetAwareTransactionService
$transferService = app(\Bavix\Wallet\Services\TransferServiceInterface::class);
$reflection = new \ReflectionClass($transferService);
$property = $reflection->getProperty('transactionService');
$property->setAccessible(true);
$injectedService = $property->getValue($transferService);
dd(get_class($injectedService)); // Should be AssetAwareTransactionService

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

### AssetTypeDetector

- `detect($wallet, $holder, $attributes)`: Detect asset type from multiple sources
- `detectFromWallet($wallet)`: Detect asset type from wallet model
- `detectFromHolder($holder)`: Detect asset type from holder model
- `detectFromAttributes($attributes)`: Detect asset type from attributes array
- `getDetectionDebugInfo($wallet, $holder, $attributes)`: Get detailed detection information

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

### AssetAwareTransactionService

- `makeOne($wallet, $type, $amount, $meta, $confirmed)`: Create transaction with automatic asset detection
- `apply($wallets, $objects)`: Apply transactions with automatic asset detection
- `getAssetTypeDetector()`: Get the asset type detector for debugging
- `getAssetContext()`: Get the asset context for debugging
- `getAssetRepositoryFactory()`: Get the asset repository factory for debugging

## Complete Example

Here's a complete example showing how to set up and use the multi-asset system:

### 1. Configuration

```php
// config/wallet-assets.php
<?php

return [
    'bonds' => [
        'wallet_table' => 'bond_wallets',
        'transaction_table' => 'bond_transactions',
        'transfer_table' => 'bond_transfers',
        'wallet_model' => 'App\Models\BondWallet',
        'transaction_model' => 'App\Models\BondTransaction',
        'transfer_model' => 'App\Models\BondTransfer',
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
    ]
];

// config/wallet.php
<?php

return [
    // ... existing configuration ...
    'assets' => require __DIR__ . '/wallet-assets.php',
];
```

### 2. Models

```php
// app/Models/BondWallet.php
<?php

namespace App\Models;

use Bavix\Wallet\Models\Wallet as BaseWallet;

class BondWallet extends BaseWallet
{
    protected $table = 'bond_wallets';

    public function getBondType()
    {
        return $this->meta['bond_type'] ?? null;
    }
}

// app/Models/ShareWallet.php
<?php

namespace App\Models;

use Bavix\Wallet\Models\Wallet as BaseWallet;

class ShareWallet extends BaseWallet
{
    protected $table = 'share_wallets';

    public function getShareSymbol()
    {
        return $this->meta['symbol'] ?? null;
    }
}
```

### 3. Usage

```php
// app/Services/TradingService.php
<?php

namespace App\Services;

use App\Models\BondWallet;
use App\Models\ShareWallet;
use App\Models\User;

class TradingService
{
    public function createPortfolios(User $user)
    {
        // Create bond portfolio
        $bondWallet = new BondWallet([
            'holder_type' => User::class,
            'holder_id' => $user->id,
            'name' => 'Bond Portfolio',
            'meta' => ['bond_type' => 'corporate']
        ]);
        $bondWallet->save();

        // Create share portfolio
        $shareWallet = new ShareWallet([
            'holder_type' => User::class,
            'holder_id' => $user->id,
            'name' => 'Share Portfolio',
            'meta' => ['symbol' => 'AAPL']
        ]);
        $shareWallet->save();

        return [$bondWallet, $shareWallet];
    }

    public function tradeAssets($bondWallet, $shareWallet, $amount)
    {
        // These automatically use the correct asset tables!
        $bondWallet->withdraw($amount);
        $shareWallet->deposit($amount);

        // Transfer between different asset types
        $bondWallet->transfer($shareWallet, $amount);
    }

    public function getPortfolioValue($user)
    {
        $bondWallet = $user->bondWallet;
        $shareWallet = $user->shareWallet;

        return [
            'bonds' => $bondWallet->balance,
            'shares' => $shareWallet->balance,
            'total' => $bondWallet->balance + $shareWallet->balance
        ];
    }
}
```

### 4. Controller Usage

```php
// app/Http/Controllers/TradingController.php
<?php

namespace App\Http\Controllers;

use App\Services\TradingService;
use Illuminate\Http\Request;

class TradingController extends Controller
{
    public function __construct(
        private TradingService $tradingService
    ) {}

    public function deposit(Request $request)
    {
        $user = auth()->user();
        $wallet = $user->bondWallet; // or $user->shareWallet
        $amount = $request->input('amount');

        // This automatically uses the correct asset table!
        $transaction = $wallet->deposit($amount);

        return response()->json([
            'success' => true,
            'balance' => $wallet->balance,
            'transaction_id' => $transaction->id
        ]);
    }

    public function transfer(Request $request)
    {
        $user = auth()->user();
        $fromWallet = $user->bondWallet;
        $toWallet = $user->shareWallet;
        $amount = $request->input('amount');

        // This automatically handles the asset type detection!
        $transfer = $fromWallet->transfer($toWallet, $amount);

        return response()->json([
            'success' => true,
            'transfer_id' => $transfer->id
        ]);
    }
}
```

This example demonstrates how the multi-asset system works transparently - you use the same Bavix methods you're familiar with, but the system automatically routes operations to the correct asset tables based on the wallet models.