<?php
  
  
namespace Coyote6\LaravelBase\Traits;


use Illuminate\Support\Str;


trait HasUuid {
	
	public function createUuid () {
		if (is_null ($this->{$this->getKeyName()}) || $this->{$this->getKeyName()} == '') {
			$this->{$this->getKeyName()} = Str::uuid()->toString();
		}
	}  
  
}