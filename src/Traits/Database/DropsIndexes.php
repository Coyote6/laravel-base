<?php


namespace Coyote6\LaravelBase\Traits\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;

trait DropsIndexes {


	// Drop Foreign If Exists
	//
	// Drops the foreign key on $column if one exists on $table, then drops
	// the plain index behind it too. Driven by Laravel's own driver-agnostic
	// schema introspection (schemaBuilder()->getForeignKeys()), not raw SQL --
	// this works identically on SQLite, MySQL, MariaDB, Postgres, and SQL
	// Server, not just MySQL/MariaDB.
	//
	// @ai
	//		SQLite has no first-class named-constraint concept for foreign
	//		keys at all -- getForeignKeys() always reports 'name' => null
	//		there, even for an explicitly-named one (the name is simply
	//		never stored), and its own grammar's dropForeign() throws
	//		"doesn't support dropping foreign keys by name" unless given
	//		the column-array form specifically. That shows up twice below:
	//		the match always requires $column (the one signal every driver
	//		can report), only ALSO checking $foreignKey when the driver
	//		actually reported a name to check it against, so a $foreignKey
	//		hint that's structurally unanswerable on SQLite doesn't
	//		wrongly exclude the one real match; and $table->dropForeign()
	//		is called with $foreign['name'] ?? $foreign['columns'] --
	//		every other driver reports a real name (default or custom) and
	//		drops correctly by it (the array form there would silently
	//		target the wrong default-computed name on a custom-named one).
	//
	// @param $table Blueprint - The table to drop the foreign key from
	// @param $column string - The foreign key's column [Ex: user_id]
	// @param $foreignKey string|null - The foreign key's constraint name, if it differs from the default "{table}_{column}_foreign"
	//
	// @return void
	//
	public function dropForeignIfExists (Blueprint $table, string $column, ?string $foreignKey = null): void {

		$tableName = $table->getTable();

		foreach ($this->schemaBuilder()->getForeignKeys($tableName) as $foreign) {
			$matches = $foreign['columns'] === [$column]
				&& ($foreignKey === null || $foreign['name'] === null || $foreign['name'] === $foreignKey);

			if ($matches) {
				$table->dropForeign($foreign['name'] ?? $foreign['columns']);
			}
		}

		$this->dropIndexIfExists ($table, $column, $foreignKey, true);

	}


	// Drop Index If Exists
	//
	// Drops the index on $column if one exists on $table.
	//
	// @param $table Blueprint - The table to drop the index from
	// @param $column string - The column the index is on [Ex: user_id]
	// @param $foreignKey string|null - The index name, if it differs from the default "{table}_{column}_index" (or "..._foreign" when $isForeign)
	// @param $isForeign bool - Whether to use the "_foreign" naming convention instead of "_index" when deriving the default name
	//
	// @return void
	//
	public function dropIndexIfExists (Blueprint $table, string $column, ?string $foreignKey = null, bool $isForeign = false): void {

		$tableName = $table->getTable();

		$indexName = $foreignKey ?? $tableName . '_' . $column . '_' . ($isForeign ? 'foreign' : 'index');

		foreach ($this->schemaBuilder()->getIndexes($tableName) as $index) {
			if ($index['name'] === $indexName) {
				$table->dropIndex($index['name']);
			}
		}

	}


	// Foreign Keys Referencing
	//
	// Every foreign key across the whole connection whose target is $table --
	// i.e. everything that would break if $table were dropped. Built from
	// schemaBuilder()->getTables() + ->getForeignKeys() rather than a single
	// reverse-lookup query: Laravel's native schema-state layer only exposes
	// a table's own outgoing foreign keys, never "who references me," on any
	// driver, so answering "who references $table" means walking every other
	// table's own outgoing keys and keeping the ones that point back here.
	//
	// @param $table string - The table other tables might reference [Ex: countries]
	//
	// @return list<array{referencing_table: string, name: string|null, columns: list<string>, foreign_columns: list<string>}>
	//
	public function foreignKeysReferencing (string $table): array {

		$builder = $this->schemaBuilder();
		$referencing = [];

		foreach ($builder->getTables() as $candidate) {
			if ($candidate['name'] === $table) {
				continue;
			}

			foreach ($builder->getForeignKeys($candidate['name']) as $foreign) {
				if ($foreign['foreign_table'] === $table) {
					$referencing[] = [
						'referencing_table' => $candidate['name'],
						'name' => $foreign['name'],
						'columns' => $foreign['columns'],
						'foreign_columns' => $foreign['foreign_columns'],
					];
				}
			}
		}

		return $referencing;

	}


	// Schema Builder
	//
	// Resolves Schema::connection($this->getConnection()) when the composing
	// class exposes one -- every Migration does -- so introspection runs
	// against whatever connection that migration itself targets, rather than
	// always assuming the app's default connection. Falls back to the
	// default connection's builder for a composing class with no
	// getConnection() of its own.
	//
	// @return \Illuminate\Database\Schema\Builder
	//
	protected function schemaBuilder (): Builder {

		$connection = method_exists($this, 'getConnection') ? $this->getConnection() : null;

		return Schema::connection($connection);

	}

}
