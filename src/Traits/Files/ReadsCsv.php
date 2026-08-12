<?php
	
	
namespace Coyote6\LaravelBase\Traits\Files;


trait ReadsCsv {
	
	
	// Get Items From CSV
	//
	// Reads the CSV file at $pathToFile and returns each row as an
	// associative array keyed by the header row's column names.
	//
	// @param $pathToFile string - Path to the CSV file to read
	//
	// @return array
	//
	protected function getItemsFromCSV (string $pathToFile): array {
		
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
