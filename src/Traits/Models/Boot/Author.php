<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models\Boot;


trait Author {

	// Create Author
	//
	// Sets the configured field (coyote6-base.author.field) to the current
	// user's id, unless a value is already present -- this preserves an
	// explicitly bulk-filled author_id (e.g. from an import) instead of
	// overwriting it with the importing admin's id.
	//
	// @return void
	//
	public function createAuthor () {
		$field = config('coyote6-base.author.field', 'author_id');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$userId = getCurrentUserId();
			if (!is_null ($userId)) {
				$this->{$field} = $userId;
			}
		}
	}

}