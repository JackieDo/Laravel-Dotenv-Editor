<?php namespace Jackiedo\DotenvEditor;

use Illuminate\Support\ServiceProvider;

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
        /**
         * Loading and publishing package's config
         */
        $packageConfigPath = __DIR__ . '/../../config/config.php';
        $appConfigPath     = config_path('dotenv-editor.php');

        $this->mergeConfigFrom($packageConfigPath, 'dotenv-editor');

        $this->publishes([
            $packageConfigPath => $appConfigPath,
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('dotenv-editor', 'Jackiedo\DotenvEditor\DotenvEditor');

        $this->registerCommands();
    }

    /**
     * Register commands
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->bind('command.dotenv.backup', 'Jackiedo\DotenvEditor\Console\Commands\DotenvBackupCommand');
        $this->app->bind('command.dotenv.deletekey', 'Jackiedo\DotenvEditor\Console\Commands\DotenvDeleteKeyCommand');
        $this->app->bind('command.dotenv.getbackups', 'Jackiedo\DotenvEditor\Console\Commands\DotenvGetBackupsCommand');
        $this->app->bind('command.dotenv.getkeys', 'Jackiedo\DotenvEditor\Console\Commands\DotenvGetKeysCommand');
        $this->app->bind('command.dotenv.restore', 'Jackiedo\DotenvEditor\Console\Commands\DotenvRestoreCommand');
        $this->app->bind('command.dotenv.setkey', 'Jackiedo\DotenvEditor\Console\Commands\DotenvSetKeyCommand');

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
