<?php

declare(strict_types=1);

namespace Bavix\Wallet;

use function config;
use function dirname;
use function function_exists;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class WalletServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(dirname(__DIR__).'/resources/lang', 'wallet');

        Event::listen(TransactionBeginning::class, Internal\Listeners\TransactionBeginningListener::class);
        Event::listen(TransactionCommitting::class, Internal\Listeners\TransactionCommittingListener::class);
        Event::listen(TransactionCommitted::class, Internal\Listeners\TransactionCommittedListener::class);
        Event::listen(TransactionRolledBack::class, Internal\Listeners\TransactionRolledBackListener::class);

        // @codeCoverageIgnoreStart
        if (! $this->app->runningInConsole()) {
            return;
        }
        // @codeCoverageIgnoreEnd

        if (WalletConfigure::isRunsMigrations()) {
            $this->loadMigrationsFrom([dirname(__DIR__).'/database']);
        }

        if (function_exists('config_path')) {
            $this->publishes([
                dirname(__DIR__).'/config/config.php' => config_path('wallet.php'),
            ], 'laravel-wallet-config');
        }

        $this->publishes([
            dirname(__DIR__).'/database/' => database_path('migrations'),
        ], 'laravel-wallet-migrations');
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/config.php', 'wallet');

        $configure = config('wallet', []);

        $orchestrator = new \Bavix\Wallet\Internal\Service\ServiceProviderOrchestrator();
        $orchestrator->registerAll($this->app, $configure);
    }

    /**
     * @return class-string[]
     */
    public function provides(): array
    {
        $orchestrator = new \Bavix\Wallet\Internal\Service\ServiceProviderOrchestrator();
        return $orchestrator->provides();
    }
}
