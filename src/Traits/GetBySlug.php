<?php


namespace Coyote6\LaravelBase\Traits;


trait GetBySlug {
	
	public static function getBySlug (string $slug) {
		return static::where('slug', $slug)->first();
	}

}

