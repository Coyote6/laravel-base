<?php
	
	
namespace Coyote6\LaravelBase\Traits;


trait ReadsCsv {
	
	
	//
	// @return array
	//
	protected function getItemsFromCSV (string $pathToFile = null) {
		
		$fh = fopen ($pathToFile, 'r');
		$items = [];
		$headers = [];
		$count = 0;
		if ($fh !== false) {
			while (($row = fgetcsv($fh, 1000, ',')) !== false) {
				if ($count == 0) {
					foreach ($row as $key => $column) {
						$headers[$key] = $column;
					}
				}
				else {
					foreach ($row as $key => $column) {
						if (!isset ($items[$count])) {
							$items[$count] = [];
						}
						$items[$count][$headers[$key]] = $column;
					}
				}
				$count++;
			}
			fclose ($fh);
		}
		return $items;
		
	}
	

}
