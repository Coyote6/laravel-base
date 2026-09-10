<?php


namespace Coyote6\LaravelBase\Traits\Models;


trait GetAsOptionsAbbr {

	use GetAsOptions {
		getAsOptions as protected getAsOptionsByKey;
	}

	// Get As Options
	//
	// @deprecated Compose the GetAsOptions trait instead and call
	//             getAsOptions('abbr'). GetAsOptions::getAsOptions() takes a
	//             $key parameter, so one trait covers id, abbr, or any other
	//             field -- a dedicated abbr trait no longer earns its keep. This
	//             method stays as a thin shim: it flips the $key default to
	//             'abbr', forwards every other argument to getAsOptions()
	//             untouched, and emits an E_USER_DEPRECATED notice on each call.
	//
	// @param $key string - Attribute to key each option by [Ex: abbr, id]
	// @param $rest mixed - Forwarded verbatim to GetAsOptions::getAsOptions() ($field, $limit, $page, $modifyQuery)
	//
	// @return array
	//
	static public function getAsOptions (string $key = 'abbr', ...$rest): array {

		trigger_error (
			'The GetAsOptionsAbbr trait is deprecated. Compose the GetAsOptions '
			. "trait instead and call getAsOptions('abbr').",
			E_USER_DEPRECATED
		);

		return static::getAsOptionsByKey ($key, ...$rest);

	}

}
