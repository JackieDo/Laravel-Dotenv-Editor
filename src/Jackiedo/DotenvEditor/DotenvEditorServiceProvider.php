<?php namespace Jackiedo\DotenvEditor;

use Illuminate\Support\ServiceProvider;
use Jackiedo\DotenvEditor\Console\DotenvBackupCommand;
use Jackiedo\DotenvEditor\Console\DotenvDeleteKeyCommand;
use Jackiedo\DotenvEditor\Console\DotenvGetBackupsCommand;
use Jackiedo\DotenvEditor\Console\DotenvGetKeysCommand;
use Jackiedo\DotenvEditor\Console\DotenvRestoreCommand;
use Jackiedo\DotenvEditor\Console\DotenvSetKeyCommand;

/**
 * DotenvEditorServiceProvider
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvEditorServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $packageConfigPath = __DIR__ . '/../../config/config.php';

        /**
         * Loading and publishing package's config
         */
        $config = config_path('dotenv-editor.php');

        if (file_exists($config)) {
            $this->mergeConfigFrom($packageConfigPath, 'dotenv-editor');
        }

        $this->publishes([
            $packageConfigPath => $config,
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('dotenv-editor', function ($app) {
            $formatter = new DotenvFormatter;
            return new DotenvEditor($app, $formatter);
        });

        $this->registerCommands();
    }

    /**
     * Register commands
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->singleton('command.dotenv.backup', function ($app) {
            return new DotenvBackupCommand($app['dotenv-editor']);
        });

        $this->app->singleton('command.dotenv.deletekey', function ($app) {
            return new DotenvDeleteKeyCommand($app['dotenv-editor']);
        });

        $this->app->singleton('command.dotenv.getbackups', function ($app) {
            return new DotenvGetBackupsCommand($app['dotenv-editor']);
        });

        $this->app->singleton('command.dotenv.getkeys', function ($app) {
            return new DotenvGetKeysCommand($app['dotenv-editor']);
        });

        $this->app->singleton('command.dotenv.restore', function ($app) {
            return new DotenvRestoreCommand($app['dotenv-editor']);
        });

        $this->app->singleton('command.dotenv.setkey', function ($app) {
            return new DotenvSetKeyCommand($app['dotenv-editor']);
        });

        $this->commands('command.dotenv.backup');
        $this->commands('command.dotenv.deletekey');
        $this->commands('command.dotenv.getbackups');
        $this->commands('command.dotenv.getkeys');
        $this->commands('command.dotenv.restore');
        $this->commands('command.dotenv.setkey');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'dotenv-editor',
            'command.dotenv.backup',
            'command.dotenv.deletekey',
            'command.dotenv.getbackups',
            'command.dotenv.getkeys',
            'command.dotenv.restore',
            'command.dotenv.setkey'
        ];
    }
}
