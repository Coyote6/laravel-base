<?php


namespace Coyote6\LaravelBase\Traits\Models;

use Closure;


trait GetAsOptions {

	// Get As Options
	//
	// Returns records as a $key => $field option list for selects, radios, and
	// the like, ordered by $field ascending. Pass $limit (0 = all) and $page to
	// paginate.
	//
	// $modifyQuery is an optional closure handed the query builder before it
	// runs -- use it to filter (where(), whereIn(), whereHas(), a local scope),
	// re-order (orderByDesc(), orderByRaw()), or anything else. When given, it
	// owns the ordering: the default $field-ascending sort is not applied, so a
	// filter-only closure should add its own orderBy(). The return value is
	// ignored.
	//
	//		// states of one country, still alphabetical
	//		State::getAsOptions (modifyQuery: fn ($q) => $q->where ('country_abbr', 'US')->orderBy ('name'));
	//
	//		// workflow order off a position column
	//		Status::getAsOptions (modifyQuery: fn ($q) => $q->orderBy ('position'));
	//
	// Not cached -- every call queries the database, so the column selection,
	// filter, order, page, and data are always current. Hoist the result into a
	// variable if a single request calls this repeatedly with the same
	// arguments.
	//
	// @param $key string - Attribute to key each option by [Ex: id, abbr, slug]
	// @param $field string - Attribute to use as each option's label [Ex: name, title]
	// @param $limit int - Maximum options to return, or 0 for all [Ex: 25]
	// @param $page int - 1-based page to return when $limit is set [Ex: 2]
	// @param $modifyQuery ?Closure - Adjusts the query builder before it runs (filter, re-order, scope); null keeps the default $field ASC [Ex: fn ($q) => $q->where ('active', true)]
	//
	// @return array
	//
	static public function getAsOptions (string $key = 'id', string $field = 'name', int $limit = 0, int $page = 1, ?Closure $modifyQuery = null): array {

		$query = static::query ();

		if ($modifyQuery) {
			$modifyQuery ($query);
		} else {
			$query->orderBy ($field, 'ASC');
		}

		if ($limit > 0) {
			$query->forPage (max ($page, 1), $limit);
		}

		return $query->pluck ($field, $key)->all ();

	}

}
