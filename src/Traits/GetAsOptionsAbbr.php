<?php


namespace Coyote6\LaravelBase\Traits;


trait GetAsOptionsAbbr {
	
	static public function getAsOptions () {
		
		static $options; 
		if (is_null ($options)) {
		    $options = [];
		    foreach (static::orderBy('name','ASC')->get() as $m) {
			    $options[$m->abbr] = $m->name;
		    }
	    }
	    return $options;
	    
	}

}