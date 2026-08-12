<?php


// Get Current User Id
//
// Helper function to return the current authenticated user's id
// or null if not found.
//
// @return int|string|null - Return type depends on the key's type
//
if (!function_exists('getCurrentUserId')) {
function getCurrentUserId (): int|string|null {
		$user = auth()->user();
		if ($user) {
			return $user->getKey();
		}
		return null;
	}
}


// Get Current User Client Id
//
// Helper function to return the current authenticated user's client id
// (per the coyote6-base.client.reference config value) or null if not
// found.
//
// @return int|string|null
//
if (!function_exists('getCurrentUserClientId')) {
	function getCurrentUserClientId (): int|string|null {
		$user = auth()->user();
		if ($user) {
            return $user->{config('coyote6-base.client.reference', 'client_id')};
		}
		return null;
	}
}

