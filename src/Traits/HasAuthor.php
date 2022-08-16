<?php
  
  
namespace Coyote6\LaravelBase\Traits;


trait HasAuthor {

	public function createAuthor () {
		if (is_null ($this->author_id) || $this->author_id == '') {
			$this->author_id = $this->getUserId();
		}
	}

}