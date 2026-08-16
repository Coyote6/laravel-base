<?php


namespace Coyote6\LaravelBase\Console\Commands;

use Coyote6\LaravelBase\Upgrades\Upgrade_0_3_0;
use Coyote6\LaravelBase\Upgrades\UpgradeStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


class UpgradeCommand extends Command {


	protected $signature = 'coyote6-base:upgrade
		{--path=app,database : Comma-separated directories to scan, relative to the project base path}
		{--apply : Write the changes without asking for confirmation -- without this flag, you are asked to confirm before each version\'s changes are written}';

	protected $description = 'Runs every registered coyote6/laravel-base upgrade step against your application, one version at a time';


	// Handle
	//
	// Runs every registered step (see steps()) in order. Each step scans
	// the --path directories fresh, so a step always sees whatever an
	// earlier step already wrote to disk this run, and re-running the
	// whole command later is safe -- a step that finds nothing left to do
	// just reports no changes and moves on.
	//
	// @return int
	//
	public function handle (): int
	{
		$apply = (bool) $this->option('apply');
		$requested = array_filter(array_map('trim', explode(',', $this->option('path'))));

		$directories = [];

		foreach ($requested as $directory) {
			$fullPath = base_path($directory);

			if (!is_dir($fullPath)) {
				$this->warn("Skipping missing directory: {$directory}");
				continue;
			}

			$directories[] = $fullPath;
		}

		foreach ($this->steps() as $step) {
			$this->runStep($step, $directories, $apply);
		}

		$this->newLine();
		$this->info('Upgrade check complete.');

		return self::SUCCESS;
	}


	// Steps
	//
	// Every upgrade step this command knows how to run, in the order they
	// should be applied. Add a new version's step here when it's built.
	//
	// @return array<UpgradeStep>
	//
	protected function steps (): array
	{
		return [
			new Upgrade_0_3_0(),
		];
	}


	// Run Step
	//
	// Scans every .php file under $directories for one upgrade step,
	// reports what it found, and applies the changes -- immediately if
	// --apply was passed, otherwise only after the developer confirms.
	// Declining, or finding nothing to do, moves on without writing
	// anything; it never blocks a later step from running.
	//
	// @param $step UpgradeStep
	// @param $directories array - Absolute paths already confirmed to exist
	// @param $apply bool - Skip the confirmation prompt and write immediately
	//
	// @return void
	//
	protected function runStep (UpgradeStep $step, array $directories, bool $apply): void
	{
		$this->newLine();
		$this->info("Running v{$step->version()} upgrades");

		$changedFiles = [];
		$conflicts = [];
		$flagged = [];

		foreach ($directories as $fullPath) {
			foreach (File::allFiles($fullPath) as $file) {
				if ($file->getExtension() !== 'php') {
					continue;
				}

				$original = File::get($file->getPathname());

				$fileConflicts = $step->conflicts($original);
				if ($fileConflicts !== []) {
					$conflicts[$file->getPathname()] = $fileConflicts;
				}

				$fileFlagged = $step->flagged($original);
				if ($fileFlagged !== []) {
					$flagged[$file->getPathname()] = $fileFlagged;
				}

				$updated = $step->rewrite($original);

				if ($updated !== $original) {
					$changedFiles[$file->getPathname()] = $updated;
				}
			}
		}

		$this->reportFlagged($flagged);
		$this->reportConflicts($conflicts);

		if ($changedFiles === []) {
			$this->line('No file changes found.');
			return;
		}

		$count = count($changedFiles);
		$label = Str::plural('file', $count);

		$shouldApply = $apply || $this->confirm("Found {$count} {$label} that would change. Apply the changes?");

		if (!$shouldApply) {
			foreach (array_keys($changedFiles) as $path) {
				$this->line("<comment>Would update</comment> {$path}");
			}
			return;
		}

		foreach ($changedFiles as $path => $updated) {
			File::put($path, $updated);
			$this->line("<info>Updated</info> {$path}");
		}

		$this->info("Updated {$count} {$label}.");
	}


	// Report Flagged
	//
	// Prints one warning block per distinct old=>new pair found across all
	// scanned files for this step, listing every file that needs it --
	// these have no safe automatic replacement and always require a
	// manual choice.
	//
	// @param $flagged array - File path => [old FQCN => new FQCN, ...]
	//
	// @return void
	//
	protected function reportFlagged (array $flagged): void
	{
		if ($flagged === []) {
			return;
		}

		$byPair = [];

		foreach ($flagged as $path => $pairs) {
			foreach ($pairs as $old => $new) {
				$byPair[$old][$new][] = $path;
			}
		}

		$this->newLine();

		foreach ($byPair as $old => $replacements) {
			foreach ($replacements as $new => $paths) {
				$this->warn(class_basename($old)." has no direct replacement -- switch these to {$new} manually:");
				foreach (array_unique($paths) as $path) {
					$this->line("  {$path}");
				}
			}
		}
	}


	// Report Conflicts
	//
	// Prints one warning line per file/trait whose rename was skipped
	// because the new short name is already bound to something unrelated
	// there.
	//
	// @param $conflicts array - File path => [old FQCN => colliding short name, ...]
	//
	// @return void
	//
	protected function reportConflicts (array $conflicts): void
	{
		if ($conflicts === []) {
			return;
		}

		$this->newLine();
		$this->warn('The new trait name would collide with an existing, unrelated class already resolvable in these files -- left untouched, upgrade manually:');

		foreach ($conflicts as $path => $traits) {
			foreach ($traits as $old => $shortName) {
				$this->line("  {$path} -- ".class_basename($old)." needs {$shortName}, but {$shortName} already resolves to something else there");
			}
		}
	}


}
