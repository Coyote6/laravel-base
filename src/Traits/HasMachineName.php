<?php
  
  
namespace Coyote6\LaravelBase\Traits;


use Webpatser\Uuid\Uuid as WebpatserUuid;


trait HasMachineName {

		
	public function createMachineName () {
		if (is_null ($this->machine_name) || $this->machine_name == '') {
			$this->machine_name = trim (strtolower (preg_replace ('/[^\da-z]/i','_', $this->name)));
		}
	}
  
  
}