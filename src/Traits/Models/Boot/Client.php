<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models\Boot;


trait Client {

	
	// Create Client
	//
	// Sets the configured field (coyote6-base.client.field) to the current
	// user's client id, via getCurrentUserClientId(), unless a value is
	// already present.
	//
	// @return void
	//
	public function createClient () {
		$field = config('coyote6-base.client.field', 'client_id');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$this->{$field} = getCurrentUserClientId();
		}
	}
  
  
}