<?php
  
  
namespace Coyote6\LaravelBase\Traits;


use App\Models\User;


trait BootTraits {

		
	protected static function boot() {
	
		parent::boot();
		
		static::creating(function ($model) {
				
			if (method_exists ($model, 'modelCreating')) {
				$model->modelCreating();
				
			}
					
			if (method_exists ($model, 'createAuthor')) {
				$model->createAuthor();
				
			}
			
			if (method_exists ($model, 'createClient')) {
				$model->createClient();
				
			}
			
			if (method_exists ($model, 'createMachineName')) {
				$model->createMachineName();
				
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


	public static function getUserId () {
		$user = auth()->user();
		if (is_object ($user)) {
			return $user->getKey();
		}
		return 0;
	}
	
	
	public static function getUser () {
		$user = auth()->user();
		if (!is_object ($user)) {
			return $user;
		}
		return User::make();		
	}
  

}