<?php


namespace Coyote6\LaravelBase\Traits\Models\Boot;

use Illuminate\Support\Str;


trait Slug {


	// Create Slug
	//
	// Generates a slug via Str::slug(), using the coyote6-base.slug config
	// for the source/destination fields and Str::slug()'s
	// $separator/$language/$dictionary parameters.
	//
	// @return void
	//
	public function createSlug () {
		$field = config('coyote6-base.slug.field', 'slug');
		$reference = config('coyote6-base.slug.reference', 'name');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$this->{$field} = Str::slug(
				$this->{$reference},
				config('coyote6-base.slug.separator', '-'),
				config('coyote6-base.slug.language', 'en'),
				config('coyote6-base.slug.dictionary', ['@' => 'at'])
			);
		}
	}


}
