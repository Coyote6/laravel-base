<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models\Boot;


trait MachineNameAsId {

	use ResolvesMachineName;


	// Create Machine Name
	//
	// Sets the model's primary key from the configured reference attribute,
	// via resolveMachineName(), unless a value is already present. Use
	// instead of MachineName when the machine name itself should be the
	// primary key, rather than a separate field.
	//
	// @return void
	//
	public function createMachineName () {
		if (is_null ($this->{$this->getKeyName()}) || $this->{$this->getKeyName()} == '') {
			$this->{$this->getKeyName()} = $this->resolveMachineName ($this->{$this->resolveMachineNameReference()});
		}
	}
  
  
}