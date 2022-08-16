<?php
  
  
namespace Coyote6\LaravelBase\Traits;

use Illuminate\Database\Eloquent\Builder;


trait HasClient {

	
	public function createClient () {
		if (is_null ($this->client_id) || $this->client_id == '') {
			$this->client_id = $this->getUser()->client_id;
		}
	}
	
/*
	protected function setKeysForSaveQuery(Builder $query) {
        $query->where('id', '=', $this->getAttribute('id'))
              ->where('client_id', '=', $this->getAttribute('client_id'));

        return $query;
    }
*/
  
  
}