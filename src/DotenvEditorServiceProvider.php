<?php

namespace Jackiedo\DotenvEditor;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Jackiedo\DotenvEditor\Console\Commands\DotenvBackupCommand;
use Jackiedo\DotenvEditor\Console\Commands\DotenvDeleteKeyCommand;
use Jackiedo\DotenvEditor\Console\Commands\DotenvGetBackupsCommand;
use Jackiedo\DotenvEditor\Console\Commands\DotenvGetKeysCommand;
use Jackiedo\DotenvEditor\Console\Commands\DotenvRestoreCommand;
use Jackiedo\DotenvEditor\Console\Commands\DotenvSetKeyCommand;

/**
 * DotenvEditorServiceProvider.
 *
 * @package Jackiedo\DotenvEditor
 *
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvEditorServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Loading and publishing package's config.
         */
        $packageConfigPath = __DIR__ . '/Config/config.php';
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
        $this->app->bind('dotenv-editor', DotenvEditor::class);

        $this->registerCommands();
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
            'command.dotenv.setkey',
        ];
    }

    /**
     * Register commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->bind('command.dotenv.backup', DotenvBackupCommand::class);
        $this->app->bind('command.dotenv.deletekey', DotenvDeleteKeyCommand::class);
        $this->app->bind('command.dotenv.getbackups', DotenvGetBackupsCommand::class);
        $this->app->bind('command.dotenv.getkeys', DotenvGetKeysCommand::class);
        $this->app->bind('command.dotenv.restore', DotenvRestoreCommand::class);
        $this->app->bind('command.dotenv.setkey', DotenvSetKeyCommand::class);

        $this->commands('command.dotenv.backup');
        $this->commands('command.dotenv.deletekey');
        $this->commands('command.dotenv.getbackups');
        $this->commands('command.dotenv.getkeys');
        $this->commands('command.dotenv.restore');
        $this->commands('command.dotenv.setkey');
    }
}
