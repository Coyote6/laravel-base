<?php


namespace Coyote6\LaravelBase\Traits\Models;


trait GetBySlug {
	
	// Get By Slug
	//
	// Finds the first record whose configured slug field
	// (coyote6-base.slug.field) matches $slug.
	//
	// @param $slug string - The slug value to search for [Ex: my-record-name]
	//
	// @return static|null
	//
	public static function getBySlug (string $slug) {
		return static::where(config('coyote6-base.slug.field', 'slug'), $slug)->first();
	}

}

