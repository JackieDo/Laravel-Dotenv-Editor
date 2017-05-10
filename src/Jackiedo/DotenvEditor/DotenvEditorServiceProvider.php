<?php namespace Jackiedo\DotenvEditor;

use Illuminate\Support\ServiceProvider;

/**
 * DotenvEditorServiceProvider
 *
 * @package Jackiedo\DotenvEditor
 * @author Jackie Do <anhvudo@gmail.com>
 */
class DotenvEditorServiceProvider extends ServiceProvider {

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
		$this->app->singleton('dotenv-editor', function($app)
		{
			$formatter = new DotenvFormatter;
			return new DotenvEditor($app, $formatter);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['dotenv-editor'];
	}

}
