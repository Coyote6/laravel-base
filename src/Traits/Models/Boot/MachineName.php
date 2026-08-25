<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models\Boot;

trait MachineName {

	use ResolvesMachineName;


	// Assign Machine Name On Model Creation
	//
	// Sets the configured field (coyote6-base.machine_name.field) from the
	// configured reference attribute, via resolveMachineName(), unless a
	// value is already present.
	//
	// @return void
	//
	public function assignMachineNameOnModelCreation () {
		$field = config('coyote6-base.machine_name.field', 'machine_name');

		if (is_null ($this->{$field}) || $this->{$field} == '') {
			$this->{$field} = $this->resolveMachineName ($this->{$this->resolveMachineNameReference()});
		}
	}
  
  
}