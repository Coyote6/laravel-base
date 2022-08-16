<?php


namespace Coyote6\LaravelBase\Providers;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
	
		
class BaseServiceProvider extends ServiceProvider {
	

	/**
	 * Register services.
	 *
	 * @return void
	 */
	public function register() {}


	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot() {}
  

}
