<?php


namespace Coyote6\LaravelBase\Providers;


use Coyote6\LaravelBase\Console\Commands\UpgradeCommand;
use Illuminate\Support\ServiceProvider;


class BaseServiceProvider extends ServiceProvider {
	

	// Register
	//
	// Merges this package's config into the application's config repository.
	//
	// @return void
	//
	public function register() {
		$this->mergeConfigFrom (__DIR__ . '/../../config/coyote6-base.php', 'coyote6-base');
	}


	// Boot
	//
	// Publishes this package's config so it can be customized in the
	// consuming application, and registers this package's Artisan commands.
	//
	// @return void
	//
	public function boot() {
		$this->publishes([
			__DIR__ . '/../../config/coyote6-base.php' => config_path('coyote6-base.php'),
		], 'coyote6-base-config');

		if ($this->app->runningInConsole()) {
			$this->commands([
				UpgradeCommand::class,
			]);
		}
	}
  

}
