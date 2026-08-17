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
	// Scans every .php file under $directories for one upgrade step in two
	// passes: first conflicts()/flagged() against every file's current
	// contents, then -- once any conflict has either been resolved to a
	// developer-chosen alias (or given up on) and any flagged replacement
	// has either been confirmed (or given up on) -- rewrite() against those
	// same contents. Reports what it found, and applies the changes
	// immediately if --apply was passed, otherwise only after the developer
	// confirms. Declining, or finding nothing to do, moves on without
	// writing anything; it never blocks a later step from running.
	//
	// @param $step UpgradeStep
	// @param $directories array - Absolute paths already confirmed to exist
	// @param $apply bool - Skip every prompt (confirmation, alias, and replacement) and write immediately
	//
	// @return void
	//
	protected function runStep (UpgradeStep $step, array $directories, bool $apply): void
	{
		$this->newLine();
		$this->info("Running v{$step->version()} upgrades");

		$contentsByPath = [];

		foreach ($this->phpFiles($directories) as $path) {
			$contentsByPath[$path] = File::get($path);
		}

		$conflictsByPath = [];
		$flaggedByPath = [];

		foreach ($contentsByPath as $path => $original) {
			$fileConflicts = $step->conflicts($original);
			if ($fileConflicts !== []) {
				$conflictsByPath[$path] = $fileConflicts;
			}

			$fileFlagged = $step->flagged($original);
			if ($fileFlagged !== []) {
				$flaggedByPath[$path] = $fileFlagged;
			}
		}

		$aliases = $this->resolveConflictAliases($conflictsByPath, $apply);
		$confirmedReplacements = $this->resolveFlaggedReplacements($flaggedByPath, $apply);

		$changedFiles = [];
		$remainingConflicts = [];
		$remainingFlagged = [];

		foreach ($contentsByPath as $path => $original) {
			$updated = $step->rewrite($original, $aliases, $confirmedReplacements);

			if ($updated !== $original) {
				$changedFiles[$path] = $updated;
			}

			if (isset($conflictsByPath[$path])) {
				$stillConflicting = array_diff_key($conflictsByPath[$path], $aliases);

				if ($stillConflicting !== []) {
					$remainingConflicts[$path] = $stillConflicting;
				}
			}

			if (isset($flaggedByPath[$path])) {
				$stillFlagged = array_diff_key($flaggedByPath[$path], $confirmedReplacements);

				if ($stillFlagged !== []) {
					$remainingFlagged[$path] = $stillFlagged;
				}
			}
		}

		$this->reportFlagged($remainingFlagged);
		$this->reportConflicts($remainingConflicts);

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


	// Php Files
	//
	// @param $directories array - Absolute paths already confirmed to exist
	//
	// @return array<string> Absolute paths of every .php file found
	//
	protected function phpFiles (array $directories): array
	{
		$paths = [];

		foreach ($directories as $fullPath) {
			foreach (File::allFiles($fullPath) as $file) {
				if ($file->getExtension() === 'php') {
					$paths[] = $file->getPathname();
				}
			}
		}

		return $paths;
	}


	// Resolve Conflict Aliases
	//
	// Asks the developer once per distinct old-FQCN conflict found across
	// every file scanned this step -- not once per file, since the same
	// trait/short-name collision typically repeats across many files (e.g.
	// every model using the Client trait, in an app with its own Client
	// model) and re-asking the same question per file would be tedious for
	// no benefit; one alias is applied everywhere that trait conflicts.
	// Never prompts when $apply is set, since --apply means fully
	// non-interactive (e.g. CI) -- conflicts just stay unresolved and get
	// reported for manual review, same as declining here would.
	//
	// @param $conflictsByPath array - File path => [old FQCN => colliding short name, ...]
	// @param $apply bool - Skip prompting entirely
	//
	// @return array Old FQCN => developer-chosen alias, only for conflicts
	//               actually given a non-blank answer
	//
	protected function resolveConflictAliases (array $conflictsByPath, bool $apply): array
	{
		$distinct = [];
		$fileCounts = [];

		foreach ($conflictsByPath as $traits) {
			foreach ($traits as $old => $shortName) {
				$distinct[$old] = $shortName;
				$fileCounts[$old] = ($fileCounts[$old] ?? 0) + 1;
			}
		}

		if ($distinct === [] || $apply) {
			return [];
		}

		$aliases = [];

		foreach ($distinct as $old => $shortName) {
			$count = $fileCounts[$old];
			$label = Str::plural('file', $count);

			$answer = $this->ask(
				"{$shortName} (".class_basename($old)."'s replacement) collides with an existing class in {$count} {$label}. Please provide a new alias for the trait, or leave blank to skip and manually review"
			);

			if (is_string($answer) && trim($answer) !== '') {
				$aliases[$old] = trim($answer);
			}
		}

		return $aliases;
	}


	// Resolve Flagged Replacements
	//
	// Asks the developer once per distinct old=>new pair flagged() reported
	// across every file scanned this step, whether to apply it anyway --
	// not once per file, for the same reason resolveConflictAliases() asks
	// once per trait rather than once per file. A flagged entry is never
	// applied without an explicit yes, since (unlike RENAMED_TRAITS) it's
	// not a pure rename -- it can carry a real behavior change the
	// developer needs to actually agree to, not just discover after the
	// fact. Never prompts when $apply is set; a flagged replacement just
	// stays unconfirmed and gets reported for manual review, same as
	// declining here would.
	//
	// @param $flaggedByPath array - File path => [old FQCN => new FQCN, ...]
	// @param $apply bool - Skip prompting entirely
	//
	// @return array Old FQCN => true, only for replacements the developer confirmed
	//
	protected function resolveFlaggedReplacements (array $flaggedByPath, bool $apply): array
	{
		$distinct = [];
		$fileCounts = [];

		foreach ($flaggedByPath as $pairs) {
			foreach ($pairs as $old => $new) {
				$distinct[$old] = $new;
				$fileCounts[$old] = ($fileCounts[$old] ?? 0) + 1;
			}
		}

		if ($distinct === [] || $apply) {
			return [];
		}

		$confirmed = [];

		foreach ($distinct as $old => $new) {
			$count = $fileCounts[$old];
			$label = Str::plural('file', $count);

			$newShort = class_basename($new);

			$answer = $this->confirm(
				"{$newShort} (".class_basename($old)."'s replacement) is not a pure rename -- see README/CHANGELOG for the behavior difference. Replace it anyway in {$count} {$label}?"
			);

			if ($answer) {
				$confirmed[$old] = true;
			}
		}

		return $confirmed;
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
