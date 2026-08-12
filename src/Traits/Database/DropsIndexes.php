<?php
  
  
namespace Coyote6\LaravelBase\Traits\Database;

use Illuminate\Database\Schema\Blueprint;

trait DropsIndexes {

	
	// Drop Foreign If Exists
	//
	// Drops the foreign key on $column if one exists on $table, then drops
	// the plain index behind it too.
	//
	// @param $table Blueprint - The table to drop the foreign key from
	// @param $column string - The column the foreign key is on [Ex: user_id]
	// @param $foreignKey string|null - The foreign key's constraint name, if it differs from the default "{table}_{column}_foreign"
	//
	// @return void
	//
	public function dropForeignIfExists (Blueprint $table, string $column, ?string $foreignKey = null): void {
	
		$q = "SELECT `column_name`, `constraint_name` FROM `information_schema`.`KEY_COLUMN_USAGE` WHERE `constraint_schema` = SCHEMA() AND `table_name` = '" . $table->getTable() . "' AND `referenced_column_name` IS NOT NULL ORDER BY `column_name`;";
		$keys = collect(\DB::select($q))->pluck('column_name');
		$indexNames = collect(\DB::select($q))->pluck('constraint_name');
	
	
		if ($keys->contains ($column)) {
			$key = [$column];
			$keyName = $table->getTable() . '_' . $column . '_foreign';
	
			if ($foreignKey) {
				$key = $foreignKey;
				$keyName = $foreignKey;
			}
		
			if ($indexNames->contains ($keyName)) {
				$table->dropForeign($key);
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
		
		$keys = collect(\DB::select("SHOW INDEXES FROM " . $table->getTable()))->pluck('Key_name');
	
		if ($foreignKey) {
			$key = $foreignKey;
		}
		else {
			$key = $table->getTable() . '_' . $column . '_index';
			if ($isForeign) {
				$key = $table->getTable() . '_' . $column . '_foreign';
			}
		}
	
		if ($keys->contains ($key)) {		
			$table->dropIndex ($key);
		}
	
	}  
  
}