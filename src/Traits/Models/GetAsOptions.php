<?php


namespace Coyote6\LaravelBase\Traits\Models;


trait GetAsOptions {
	
	// Get As Options
	//
	// Returns every record as an id => name option list, ordered by name.
	// Cached in a static variable after the first call.
	//
	// @return array
	//
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