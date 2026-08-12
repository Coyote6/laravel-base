<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models\Boot;

trait MachineName {

	use ResolvesMachineName;


	// Create Machine Name
	//
	// Sets the configured field (coyote6-base.machine_name.field) from the
	// configured reference attribute, via resolveMachineName(), unless a
	// value is already present.
	//
	// @return void
	//
	public function createMachineName () {
		$field = config('coyote6-base.machine_name.field', 'machine_name');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$this->{$field} = $this->resolveMachineName ($this->{$this->resolveMachineNameReference()});
		}
	}
  
  
}