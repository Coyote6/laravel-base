<?php
  
  
namespace Coyote6\LaravelBase\Traits;


trait HasAuthor {

	public function createAuthor () {
		if (is_null ($this->author_id) || $this->author_id == '') {
			$this->author_id = $this->getUserId();
		}
		
		// The original author id is a fallback field for when we there is a foreign key 
		// constraint on the author_id field and we want to keep a record when if the user 
		// is deleted, we still have the user's id for future reference.
		//
		// Better use is to just remove the foreign key and use a regular index on the author 
		// field.
		//
		if (
			property_exists ($this, 'fillable') && 
			is_array ($this->fillable) && 
			isset ($this->fillable['original_author_id'])
		) {
			$this->original_author_id = $this->author_id;
		}
		
	}

}