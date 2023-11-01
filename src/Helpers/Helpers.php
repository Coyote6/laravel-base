<?php
	

//
// @return string || null
//
if (!function_exists('getCurrentUserClientId')) {
	function getCurrentUserClientId () {
		$user = auth()->user();
		if ($user) {
			return $user->client_id;
		}
		return null;
	}
}

//
// Change string to snake case.
//
// @return void
//
if (!function_exists('toSnakeCase')) {
	function toSnakeCase (string $str) {
		
		// Replace spaces and special characters with underscores
		$snakeCase = preg_replace('/[^A-Za-z0-9]+/', '_', $str);
		
		// Convert to lowercase
		$snakeCase = strtolower($snakeCase);
		
		// Remove leading and trailing underscores
		$snakeCase = trim($snakeCase, '_');
		return $snakeCase;
		
	}
}


//
// @alias to toSnakeCase()
//
if (!function_exists('createMachineName')) {
	function createMachineName (string $str) {
		return toSnakeCase ($str);
	}
}


//
// Change camelcase keys to snake case.
//
// @return void
//
if (!function_exists('snakeCaseKeys')) {
	function snakeCaseKeys (array &$array) {
		foreach ($array as $key => $val) {
			$newKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
			if ($newKey != $key && !isset ($array[$newKey])) {
				$array[$newKey] = $val;
				unset ($array[$key]);
			}
		}
	}
}
