<?php


namespace Coyote6\LaravelBase\Traits\Models;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;


trait GetAsOptions {

	// Get As Options
	//
	// Returns records as a $key => $field option list for selects, radios, and
	// the like, ordered by $field ascending. Pass $limit (0 = all) and $page to
	// paginate.
	//
	// $field is usually a column name, but also accepts:
	//   - an Expression (DB::raw()) -- a DB-computed label, e.g. a
	//     concatenation. Alias it ("AS label") so the label can be pulled out
	//     of the result row. Dialect-specific (MySQL/MariaDB CONCAT() vs
	//     SQLite/Postgres ||) -- don't use this if the same call has to run
	//     against more than one driver.
	//   - a Closure(Model): string -- a PHP-computed label built from the
	//     hydrated model. Portable across every driver, at the cost of a full
	//     get() in place of a lean two-column pluck().
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
	//		// DB-computed label -- $field can't be reused inside ORDER BY once it
	//		// carries an alias, so $modifyQuery supplies the ordering instead
	//		State::getAsOptions (field: DB::raw ("CONCAT (country_id, ' - ', name) AS label"), modifyQuery: fn ($q) => $q->orderBy ('country_id')->orderBy ('name'));
	//
	//		// PHP-computed label -- same ordering rule, portable across every driver
	//		State::getAsOptions (field: fn (State $state) => "{$state->country_id} - {$state->name}", modifyQuery: fn ($q) => $q->orderBy ('country_id')->orderBy ('name'));
	//
	// An Expression or Closure $field never gets the default $field-ASC sort --
	// an Expression can't be reused inside ORDER BY once it carries an alias,
	// and a Closure's label doesn't exist at the SQL level to sort by at all.
	// Pass $modifyQuery for ordering whenever $field isn't a plain column.
	//
	// Not cached -- every call queries the database, so the column selection,
	// filter, order, page, and data are always current. Hoist the result into a
	// variable if a single request calls this repeatedly with the same
	// arguments.
	//
	// @param $key string - Attribute to key each option by [Ex: id, abbr, slug]
	// @param $field string|Expression|Closure - Column, DB::raw() expression, or Closure(Model): string used as each option's label [Ex: name, title]
	// @param $limit int - Maximum options to return, or 0 for all [Ex: 25]
	// @param $page int - 1-based page to return when $limit is set [Ex: 2]
	// @param $modifyQuery ?Closure - Adjusts the query builder before it runs (filter, re-order, scope); null keeps the default $field ASC, but only when $field is a plain column [Ex: fn ($q) => $q->where ('active', true)]
	//
	// @return array
	//
	static public function getAsOptions (string $key = 'id', string|Expression|Closure $field = 'name', int $limit = 0, int $page = 1, ?Closure $modifyQuery = null): array {

		$query = static::query ();

		if ($modifyQuery) {
			$modifyQuery ($query);
		} elseif (is_string ($field)) {
			$query->orderBy ($field, 'ASC');
		}

		if ($limit > 0) {
			$query->forPage (max ($page, 1), $limit);
		}

		if ($field instanceof Closure) {
			return $query->get ()->mapWithKeys (
				fn (Model $model) => [$model->getAttribute ($key) => $field ($model)]
			)->all ();
		}

		return $query->pluck ($field, $key)->all ();

	}

}
