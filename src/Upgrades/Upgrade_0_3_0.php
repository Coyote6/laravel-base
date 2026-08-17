<?php


namespace Coyote6\LaravelBase\Upgrades;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;


class Upgrade_0_3_0 implements UpgradeStep {


	// Renamed Traits
	//
	// Old fully-qualified trait name => new fully-qualified trait name, for
	// everything that moved namespace between v0.2.7 and v0.3.0.
	protected const RENAMED_TRAITS = [
		'Coyote6\LaravelBase\Traits\HasAuthor' => 'Coyote6\LaravelBase\Traits\Models\Boot\Author',
		'Coyote6\LaravelBase\Traits\HasClient' => 'Coyote6\LaravelBase\Traits\Models\Boot\Client',
		'Coyote6\LaravelBase\Traits\HasMachineNameAsId' => 'Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId',
		'Coyote6\LaravelBase\Traits\HasMachineName' => 'Coyote6\LaravelBase\Traits\Models\Boot\MachineName',
		'Coyote6\LaravelBase\Traits\BootTraits' => 'Coyote6\LaravelBase\Traits\Models\BootTraits',
		'Coyote6\LaravelBase\Traits\GetAsOptionsAbbr' => 'Coyote6\LaravelBase\Traits\Models\GetAsOptionsAbbr',
		'Coyote6\LaravelBase\Traits\GetAsOptions' => 'Coyote6\LaravelBase\Traits\Models\GetAsOptions',
		'Coyote6\LaravelBase\Traits\GetBySlug' => 'Coyote6\LaravelBase\Traits\Models\GetBySlug',
		'Coyote6\LaravelBase\Traits\DropsIndexes' => 'Coyote6\LaravelBase\Traits\Database\DropsIndexes',
		'Coyote6\LaravelBase\Traits\ServiceProviderSeedsDb' => 'Coyote6\LaravelBase\Traits\Database\ServiceProviderSeedsDb',
		'Coyote6\LaravelBase\Traits\ReadsCsv' => 'Coyote6\LaravelBase\Traits\Files\ReadsCsv',
	];

	// Mandatory Aliases
	//
	// Old FQCN => alias always used in place of the new trait's own bare
	// short name, for renames whose new short name is generic enough to
	// routinely collide with a real domain class -- Client and Author are
	// both confirmed live model names across every app in this ecosystem,
	// and MachineName/MachineNameAsId are the same shape of risk even
	// though nothing collides with them today. Always written aliased
	// (`use ...\Client as BootClient;`), regardless of whether this
	// specific file happens to collide, so the tool never has to guess
	// file-by-file and a developer reading the code always sees the same
	// name for the same trait everywhere.
	protected const MANDATORY_ALIASES = [
		'Coyote6\LaravelBase\Traits\HasAuthor' => 'BootAuthor',
		'Coyote6\LaravelBase\Traits\HasClient' => 'BootClient',
		'Coyote6\LaravelBase\Traits\HasMachineName' => 'BootMachineName',
		'Coyote6\LaravelBase\Traits\HasMachineNameAsId' => 'BootMachineNameAsId',
	];

	// Interactive Replacements
	//
	// Old fully-qualified trait name => new fully-qualified trait name, for
	// traits with no direct 1:1 replacement -- never rewritten
	// unconditionally the way RENAMED_TRAITS is. flagged() reports files
	// referencing one; UpgradeCommand::resolveFlaggedReplacements() asks
	// the developer once per distinct entry whether to apply it anyway,
	// and rewrite() only touches a file if that entry's old FQCN is present
	// in $confirmedReplacements.
	//
	// @ai
	//		HasUuid -> Illuminate\Database\Eloquent\Concerns\HasUuids is a
	//		safe mechanical swap (HasUuid followed the same BootTraits-hook
	//		pattern as every other trait here, so it doesn't conflict with
	//		HasUuids' own independent self-booting), but it's not a pure
	//		rename: HasUuids generates ordered (time-sortable) UUIDs by
	//		default, where HasUuid generated random ones (Str::uuid()). That
	//		behavior change is real enough that it shouldn't happen without
	//		the developer explicitly agreeing to it, unlike the unconditional
	//		renames above -- hence a yes/no confirmation rather than folding
	//		it into RENAMED_TRAITS.
	protected const INTERACTIVE_REPLACEMENTS = [
		'Coyote6\LaravelBase\Traits\HasUuid' => 'Illuminate\Database\Eloquent\Concerns\HasUuids',
	];

	// Machine Name Methods
	//
	// Every value coyote6-base.machine_name.method accepts -- see
	// ResolvesMachineName and the published config's own comments for what
	// each one produces. Kept here (not read from the trait itself) so
	// additionalChecks() can offer the exact same list without booting a
	// model.
	protected const MACHINE_NAME_METHODS = [
		'strictKebab', 'strictSnake', 'pureKebab', 'pureSnake', 'kebab',
		'snake', 'dot', 'slug', 'studly', 'pascal', 'camel', 'lower',
		'upper', 'deduplicate', 'transliterate',
	];


	// Version
	//
	// @return string
	//
	public function version (): string
	{
		return '0.3.0';
	}


	// Rewrite
	//
	// Applies every non-conflicting renamed-trait substitution to
	// $contents: the fully-qualified name wherever it appears, aliased to
	// its MANDATORY_ALIASES entry when it has one (otherwise its own bare
	// short name), plus the bare short class name (word-boundary-safe) to
	// catch `use ShortName;` trait inclusions left pointing at the old name
	// once its import line changes. Also applies any INTERACTIVE_REPLACEMENTS
	// entry the developer confirmed via $confirmedReplacements.
	//
	// @param $contents string - The file contents to rewrite
	// @param $customAliases array - Old FQCN => developer-chosen alias, for
	//                                renames still conflicting even under
	//                                their MANDATORY_ALIASES entry (see
	//                                UpgradeCommand::resolveConflictAliases())
	// @param $confirmedReplacements array - Old FQCN => true, for
	//                                        INTERACTIVE_REPLACEMENTS entries
	//                                        the developer confirmed applying
	//                                        (see
	//                                        UpgradeCommand::resolveFlaggedReplacements())
	//
	// @return string
	//
	public function rewrite (string $contents, array $customAliases = [], array $confirmedReplacements = []): string
	{
		$renamed = $this->sortedRenames();
		$conflicts = $this->detectConflicts($contents, $renamed);

		foreach ($renamed as $old => $new) {
			if (!str_contains($contents, $old)) {
				continue;
			}

			if (array_key_exists($old, $conflicts)) {
				if (!isset($customAliases[$old]) || $customAliases[$old] === '') {
					continue;
				}

				$contents = $this->applyRename($contents, $old, $new, $customAliases[$old]);

				continue;
			}

			$contents = $this->applyRename($contents, $old, $new, self::MANDATORY_ALIASES[$old] ?? class_basename($new));
		}

		foreach (self::INTERACTIVE_REPLACEMENTS as $old => $new) {
			if (empty($confirmedReplacements[$old]) || !str_contains($contents, $old)) {
				continue;
			}

			$contents = $this->applyRename($contents, $old, $new, class_basename($new));
		}

		return $contents;
	}


	// Conflicts
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array Old FQCN => colliding target short name (the
	//               MANDATORY_ALIASES entry when the rename has one,
	//               otherwise the new trait's own bare short name)
	//
	public function conflicts (string $contents): array
	{
		return $this->detectConflicts($contents, $this->sortedRenames());
	}


	// Flagged
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array Old FQCN => suggested new FQCN
	//
	public function flagged (string $contents): array
	{
		$flagged = [];

		foreach (self::INTERACTIVE_REPLACEMENTS as $old => $new) {
			if (str_contains($contents, $old) || str_contains($contents, class_basename($old))) {
				$flagged[$old] = $new;
			}
		}

		return $flagged;
	}


	// Additional Checks
	//
	// Reminds about the machine_name.method default change if this run's
	// scan found any machine-name generation in use at all -- under either
	// name, old (HasMachineName/HasMachineNameAsId) or new
	// (BootMachineName/BootMachineNameAsId), so this still fires on a
	// second run after the rename itself has already been applied and
	// neither old name appears in the scanned files anymore.
	//
	// @param $command Command - The running console command, for prompting
	// @param $contentsByPath array - File path => contents, from this step's scan
	// @param $apply bool - Skip every prompt, same contract as rewrite()'s
	//                       callers -- never publish or edit the config
	//                       automatically, just print the same informational
	//                       note declining to publish would show
	//
	// @return void
	//
	public function additionalChecks (Command $command, array $contentsByPath, bool $apply): void
	{
		foreach ($contentsByPath as $contents) {
			if (str_contains($contents, 'MachineName')) {
				$this->checkMachineNameMethod($command, $apply);

				return;
			}
		}
	}


	// Check Machine Name Method
	//
	// Prior to v0.3.0, machine names were always generated snake_case-
	// shaped; v0.3.0 changed the package default to strictKebab
	// (dash-separated) -- see the README's "machine_name's default
	// generation method changed" note. An app that never explicitly set
	// machine_name.method gets the new dash-separated behavior silently, so
	// this offers to publish the config (if not already) and update
	// machine_name.method right in the upgrade flow, rather than leaving it
	// to a README paragraph the developer has to go find on their own.
	//
	// @ai
	//		Only a targeted regex swap of the 'method' => '...' line, not a
	//		full parse-and-regenerate of the config file -- consistent with
	//		this whole command's "textual rewrite, not a full PHP-aware
	//		refactor" approach elsewhere, and specifically to avoid
	//		destroying the config file's own per-method documentation
	//		comments, which a var_export()-based regeneration would. Same
	//		accepted tradeoff as everywhere else in this class: a heavily
	//		reformatted or hand-edited config file could evade the regex.
	//
	// @param $command Command - The running console command, for prompting
	// @param $apply bool - Skip every prompt and never publish/edit the config
	//
	// @return void
	//
	protected function checkMachineNameMethod (Command $command, bool $apply): void
	{
		$note = 'machine_name.method defaults to strictKebab as of v0.3.0 -- prior versions always generated snake_case-shaped names. Publish the config (php artisan vendor:publish --tag=coyote6-base-config) and set machine_name.method explicitly if you need to preserve the old behavior.';

		if ($apply) {
			$command->warn($note);
			$command->line('(not prompted under --apply)');

			return;
		}

		$publishedPath = config_path('coyote6-base.php');

		if (!File::exists($publishedPath)) {
			$shouldPublish = $command->confirm(
				"This app uses machine-name generation (MachineName/MachineNameAsId), but hasn't published coyote6-base's config yet. Publish it now so machine_name.method can be reviewed?"
			);

			if (!$shouldPublish) {
				$command->warn($note);

				return;
			}

			Artisan::call('vendor:publish', ['--tag' => 'coyote6-base-config']);
			$command->line("<info>Published</info> {$publishedPath}");
		}

		$configContents = File::get($publishedPath);

		if (!preg_match("/'method'\s*=>\s*'([^']*)'/", $configContents, $match)) {
			return;
		}

		$current = $match[1];
		$default = in_array($current, self::MACHINE_NAME_METHODS, true) ? $current : null;

		$chosen = $command->choice(
			"machine_name.method is currently \"{$current}\". v0.3.0 changed the package default to strictKebab (dash-separated); every version before it always generated snake_case-shaped names, closest to strictSnake. Which method should this app use? (@see https://packagist.org/packages/coyote6/laravel-str and https://packagist.org/packages/coyote6/laravel-base)",
			self::MACHINE_NAME_METHODS,
			$default
		);

		if ($chosen === $current) {
			return;
		}

		$updated = preg_replace(
			"/'method'(\s*=>\s*)'[^']*'/",
			"'method'\$1'{$chosen}'",
			$configContents,
			1
		);

		File::put($publishedPath, $updated);

		$command->info("Updated machine_name.method to \"{$chosen}\" in {$publishedPath}.");
	}


	// Apply Rename
	//
	// Swaps $old's FQCN for $new wherever it appears in $contents, aliases
	// the resulting `use $new;` import line to $useAs if that differs from
	// $new's own bare short name, and swaps every bare occurrence of
	// $old's short name (e.g. the class-body `use OldShortName;`
	// trait-inclusion) to $useAs.
	//
	// @param $contents string - The file contents to rewrite
	// @param $old string - Old FQCN
	// @param $new string - New FQCN
	// @param $useAs string - The short name this rename should end up
	//                        using everywhere in $contents -- $new's own
	//                        bare short name, its MANDATORY_ALIASES entry,
	//                        or a developer-chosen custom alias
	//
	// @return string
	//
	protected function applyRename (string $contents, string $old, string $new, string $useAs): string
	{
		$contents = str_replace($old, $new, $contents);

		$newShort = class_basename($new);

		if ($newShort !== $useAs) {
			$contents = preg_replace(
				'/^(use\s+'.preg_quote($new, '/').')\s*;/mi',
				'$1 as '.$useAs.';',
				$contents,
				1
			);
		}

		$oldShort = class_basename($old);

		if ($oldShort !== $useAs) {
			$contents = preg_replace('/\b'.preg_quote($oldShort, '/').'\b/', $useAs, $contents);
		}

		return $contents;
	}


	// Sorted Renames
	//
	// RENAMED_TRAITS sorted by key length descending before use, as a
	// defensive measure against substring-prefix collisions -- a couple of
	// old names (HasMachineName / HasMachineNameAsId) are literal prefixes
	// of each other. They happen to still resolve correctly either order
	// today, since "AsId" is preserved verbatim in both the old and new
	// names, but that's a coincidence of the current naming, not something
	// future entries should have to rely on -- sorting removes the need to
	// reason about it at all.
	//
	// @return array Old FQCN => new FQCN, longest key first
	//
	protected function sortedRenames (): array
	{
		$renamed = self::RENAMED_TRAITS;
		uksort($renamed, fn ($a, $b) => strlen($b) <=> strlen($a));

		return $renamed;
	}


	// Detect Conflicts
	//
	// Checks $contents for renames that would introduce a target short name
	// (the MANDATORY_ALIASES entry when the rename has one, otherwise the
	// new trait's own bare short name) already bound to a *different*
	// class -- either by another import already in this file (e.g. this
	// file already has `use App\Models\BootClient;` for some unrelated
	// reason, and the Client rename would need that same alias), or by a
	// same-namespace class resolved with no import at all (e.g. this file
	// and `BootClient` both live in `App\Models`).
	//
	// @ai
	//		Only unaliased old imports are checked. If the developer already
	//		wrote `use Coyote6\LaravelBase\Traits\HasAuthor as Whatever;`,
	//		applyRename() preserves that alias verbatim -- the new FQCN
	//		never introduces a bare "Author"/"BootAuthor" into the file's
	//		import table, so there's nothing to collide.
	//
	//		The same-namespace check uses class_exists()/interface_exists()/
	//		trait_exists()/enum_exists() against the file's own declared
	//		namespace + the target short name, rather than another text
	//		scan -- this command runs inside the consuming app via
	//		`php artisan`, so the app's real autoloader is already booted,
	//		and that's the only reliable way to know whether an unimported
	//		bare name resolves to something. Only checked when no `use`
	//		import already claims that short name in this file, so a real
	//		collision is never reported twice under both checks.
	//
	//		This loop runs over every entry in RENAMED_TRAITS, not just the
	//		4 with a MANDATORY_ALIASES entry -- but in practice only those 4
	//		(Author, Client, MachineName, MachineNameAsId) can ever actually
	//		trigger a conflict, because they're the only renames whose bare
	//		short name changes. The other 7 (BootTraits, GetAsOptions,
	//		GetAsOptionsAbbr, GetBySlug, DropsIndexes,
	//		ServiceProviderSeedsDb, ReadsCsv) keep the exact same short name
	//		across the rename -- only their namespace/directory moves -- so
	//		either the old import already legitimately claims that bare slot
	//		in this file (existingImports()'s own `!== $old` guard already
	//		excludes that), or the trait is referenced only via a
	//		fully-qualified name with no `use` import at all, in which case
	//		applyRename() only swaps that FQCN text in place and never
	//		introduces a new bare import to begin with. There's no starting
	//		file, valid as PHP, where renaming one of those 7 introduces a
	//		collision that didn't already exist before this tool touched it.
	//
	// @param $contents string - The file contents to inspect
	// @param $renamed array - Old FQCN => new FQCN
	//
	// @return array Old FQCN => colliding target short name, for every conflict found
	//
	protected function detectConflicts (string $contents, array $renamed): array
	{
		$existingImports = $this->existingImports($contents);
		$namespace = $this->fileNamespace($contents);
		$conflicts = [];

		foreach ($renamed as $old => $new) {
			if (!str_contains($contents, $old)) {
				continue;
			}

			if (preg_match('/^use\s+'.preg_quote($old, '/').'\s+as\s+/mi', $contents)) {
				continue;
			}

			$targetShort = self::MANDATORY_ALIASES[$old] ?? class_basename($new);

			if (array_key_exists($targetShort, $existingImports)) {
				if ($existingImports[$targetShort] !== $old && $existingImports[$targetShort] !== $new) {
					$conflicts[$old] = $targetShort;
				}

				continue;
			}

			if ($namespace === null) {
				continue;
			}

			$candidate = $namespace.'\\'.$targetShort;

			if (
				$candidate !== $new &&
				(class_exists($candidate) || interface_exists($candidate) || trait_exists($candidate) || enum_exists($candidate))
			) {
				$conflicts[$old] = $targetShort;
			}
		}

		return $conflicts;
	}


	// Existing Imports
	//
	// Parses every top-level `use Some\Namespace\Name;` or
	// `use Some\Namespace\Name as Alias;` import statement in $contents
	// into a short-name/alias => FQCN map. Matches only unindented `use`
	// lines, which excludes the indented `use SomeTrait;` trait-inclusion
	// statements inside a class body under this package's own formatting
	// convention -- a plain text heuristic, not a real parser, so a
	// differently-formatted file could confuse it.
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array Short name/alias => FQCN
	//
	protected function existingImports (string $contents): array
	{
		$imports = [];

		if (!preg_match_all('/^use\s+([^\s;{]+)(?:\s+as\s+(\w+))?\s*;/mi', $contents, $matches, PREG_SET_ORDER)) {
			return $imports;
		}

		foreach ($matches as $match) {
			$fqcn = ltrim($match[1], '\\');
			$alias = $match[2] ?? '';
			$short = $alias !== '' ? $alias : class_basename($fqcn);
			$imports[$short] = $fqcn;
		}

		return $imports;
	}


	// File Namespace
	//
	// Parses $contents' own `namespace X;` declaration, if any.
	//
	// @param $contents string - The file contents to inspect
	//
	// @return string|null The declared namespace, or null if the file has none
	//
	protected function fileNamespace (string $contents): ?string
	{
		if (!preg_match('/^namespace\s+([^\s;{]+)\s*;/mi', $contents, $match)) {
			return null;
		}

		return $match[1];
	}


}
