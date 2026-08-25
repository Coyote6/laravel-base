<?php


namespace Coyote6\LaravelBase\Traits\Models\Boot;


trait Owner {

	// Assign Owner On Model Creation
	//
	// Sets the configured field (coyote6-base.owner.field) to the current
	// user's id, unless a value is already present -- this preserves an
	// explicitly bulk-filled owner_id (e.g. from an import) instead of
	// overwriting it with the current user's id.
	//
	// @return void
	//
	public function assignOwnerOnModelCreation () {
		$field = config('coyote6-base.owner.field', 'owner_id');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$userId = getCurrentUserId();
			if (!is_null ($userId)) {
				$this->{$field} = $userId;
			}
		}
	}

}
