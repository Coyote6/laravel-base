<?php
  
  
namespace Coyote6\LaravelBase\Traits;

use Illuminate\Database\Schema\Blueprint;

trait DropsIndexes {

	
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