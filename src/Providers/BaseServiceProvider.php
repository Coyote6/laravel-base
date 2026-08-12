<?php


namespace Coyote6\LaravelBase\Providers;


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
	// consuming application.
	//
	// @return void
	//
	public function boot() {
		$this->publishes([
			__DIR__ . '/../../config/coyote6-base.php' => config_path('coyote6-base.php'),
		], 'coyote6-base-config');
	}
  

}
