<?php


namespace Coyote6\LaravelBase\Traits;


trait GetAsOptions {
	
	static public function getAsOptions () {
		
		static $options; 
		if (is_null ($options)) {
		    $options = [];
		    foreach (static::orderBy('name','ASC')->get() as $m) {
			    $options[$m->id] = $m->name;
		    }
	    }
	    return $options;
	    
	}

}