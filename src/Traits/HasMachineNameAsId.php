<?php
  
  
namespace Coyote6\LaravelBase\Traits;


use Webpatser\Uuid\Uuid as WebpatserUuid;


trait HasMachineNameAsId {

		
	public function createMachineName () {
		if (is_null ($this->id) || $this->id == '') {
			$this->id = trim (strtolower (preg_replace ('/[^\da-z]/i','_', $this->name)));
		}
	}
  
  
}