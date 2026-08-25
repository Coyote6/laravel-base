<?php


namespace Coyote6\LaravelBase\Traits\Models\Boot;


trait User {

	// Assign User On Model Creation
	//
	// Sets the configured field (coyote6-base.user.field) to the current
	// user's id, unless a value is already present -- this preserves an
	// explicitly bulk-filled user_id (e.g. from an import) instead of
	// overwriting it with the current user's id.
	//
	// @return void
	//
	public function assignUserOnModelCreation () {
		$field = config('coyote6-base.user.field', 'user_id');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$userId = getCurrentUserId();
			if (!is_null ($userId)) {
				$this->{$field} = $userId;
			}
		}
	}

}
