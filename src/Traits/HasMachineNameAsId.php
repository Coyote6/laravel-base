<?php
  
  
namespace Coyote6\LaravelBase\Traits;


use Webpatser\Uuid\Uuid as WebpatserUuid;


trait HasMachineNameAsId {

		
	public function createMachineName () {
		if (is_null ($this->{$this->getKeyName()}) || $this->{$this->getKeyName()} == '') {
			$this->{$this->getKeyName()} = trim (strtolower (preg_replace ('/[^\da-z]/i','_', $this->name)));
		}
	}
  
  
}