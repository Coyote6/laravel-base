<?php
  
  
namespace Coyote6\LaravelBase\Traits\Models;


trait BootTraits {

		
	// Boot
	//
	// Registers Eloquent model-event listeners that call each optional
	// convention method when it exists on the model: assignAuthorOnModelCreation,
	// assignOriginalAuthorOnModelCreation, assignOwnerOnModelCreation,
	// assignUserOnModelCreation, assignClientOnModelCreation,
	// assignMachineNameOnModelCreation, and assignSlugOnModelCreation on
	// creating; modelCreating/modelCreated on create;
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

			if (method_exists ($model, 'assignAuthorOnModelCreation')) {
				$model->assignAuthorOnModelCreation();

			}

			if (method_exists ($model, 'assignOriginalAuthorOnModelCreation')) {
				$model->assignOriginalAuthorOnModelCreation();

			}

			if (method_exists ($model, 'assignOwnerOnModelCreation')) {
				$model->assignOwnerOnModelCreation();

			}

			if (method_exists ($model, 'assignUserOnModelCreation')) {
				$model->assignUserOnModelCreation();

			}

			if (method_exists ($model, 'assignClientOnModelCreation')) {
				$model->assignClientOnModelCreation();

			}

			if (method_exists ($model, 'assignMachineNameOnModelCreation')) {
				$model->assignMachineNameOnModelCreation();

			}

			if (method_exists ($model, 'assignSlugOnModelCreation')) {
				$model->assignSlugOnModelCreation();

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