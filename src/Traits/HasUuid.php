<?php
  
  
namespace Coyote6\LaravelBase\Traits;


use Webpatser\Uuid\Uuid as WebpatserUuid;


trait HasUuid {
	
	public function createUuid () {
		if (is_null ($this->{$this->getKeyName()}) || $this->{$this->getKeyName()} == '') {
			$this->{$this->getKeyName()} = WebpatserUuid::generate()->string;
		}
	}  
  
}