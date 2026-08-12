<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models;


trait BootTraits {

		
	// Boot
	//
	// Registers Eloquent model-event listeners that call each optional
	// convention method when it exists on the model: createAuthor,
	// createOriginalAuthor, createClient, createMachineName, createSlug, and
	// createUuid on creating; modelCreating/modelCreated on create;
	// modelUpdating/modelUpdated on update; modelDeleting/modelDeleted on
	// delete. A model opts into any of this behavior just by defining the
	// matching method itself, or by composing the trait that defines it --
	// this trait never requires any of them to exist.
	//
	// @return void
	//
	protected static function boot() {
	
		parent::boot();
		
		static::creating(function ($model) {
			
			if (method_exists ($model, 'modelCreating')) {
        		$model->modelCreating();
    		}
						
			if (method_exists ($model, 'createAuthor')) {
				$model->createAuthor();

			}

			if (method_exists ($model, 'createOriginalAuthor')) {
				$model->createOriginalAuthor();

			}

			if (method_exists ($model, 'createClient')) {
				$model->createClient();
				
			}
			
			if (method_exists ($model, 'createMachineName')) {
				$model->createMachineName();

			}

			if (method_exists ($model, 'createSlug')) {
				$model->createSlug();

			}

			if (method_exists ($model, 'createUuid')) {
				$model->createUuid();
				
			}
			
        });
        
	    static::created (function ($model) {
        	if (method_exists ($model, 'modelCreated')) {
        		$model->modelCreated();
    		}
    	});
       
        static::updating (function ($model) {
			if (method_exists($model, 'modelUpdating')) {
				$model->modelUpdating();
			}
        });

		static::updated(function ($model) {
	 		if (method_exists($model, 'modelUpdated')) {
	            $model->modelUpdated();
        	}
	    });

	    static::deleting(function($model) {
			if (method_exists ($model, 'modelDeleting')) {
				$model->modelDeleting();
			}
	    });
		
	    static::deleted(function($model) {
			if (method_exists ($model, 'modelDeleted')) {
				$model->modelDeleted();
			}
	    });
	
	}
  

}