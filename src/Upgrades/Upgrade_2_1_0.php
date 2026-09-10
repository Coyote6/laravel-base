<?php


namespace Coyote6\LaravelBase\Upgrades;

use Illuminate\Console\Command;


class Upgrade_2_1_0 implements UpgradeStep {


	// Abbr Trait / Options Trait
	//
	// The deprecated trait this step migrates away from, and the trait that
	// replaces it. As of v2.1.0 GetAsOptionsAbbr::getAsOptions() is just a
	// shim over GetAsOptions::getAsOptions('abbr') that raises an
	// E_USER_DEPRECATED notice, so the migration is a behavior-preserving
	// 1:1: swap the trait, and pass 'abbr' at every call site that used to
	// rely on the old abbr-keyed default.
	protected const ABBR_TRAIT = 'Coyote6\LaravelBase\Traits\Models\GetAsOptionsAbbr';

	protected const OPTIONS_TRAIT = 'Coyote6\LaravelBase\Traits\Models\GetAsOptions';


	// Abbr Models
	//
	// FQCN => short name, for every class found composing GetAsOptionsAbbr
	// anywhere in this run's scan. Populated by prepare() before any file is
	// rewritten, so a `State::getAsOptions()` call in one file can be
	// rewritten from what a different file (State's own) declares. Empty
	// after everything has already been migrated, which is what makes a
	// second run a no-op.
	//
	// @var array<string, string>
	protected array $abbrModels = [];


	// Version
	//
	// @return string
	//
	public function version (): string
	{
		return '2.1.0';
	}


	// Prepare
	//
	// Records every class that composes GetAsOptionsAbbr -- by both its
	// short name and its fully-qualified name -- so rewrite() can recognise
	// `<Model>::getAsOptions()` call sites to them in any scanned file, not
	// just the model's own.
	//
	// @param $contentsByPath array - File path => contents, from this step's scan
	//
	// @return void
	//
	public function prepare (array $contentsByPath): void
	{
		$this->abbrModels = [];

		foreach ($contentsByPath as $contents) {
			if (!$this->composesAbbrTrait($contents)) {
				continue;
			}

			$namespace = $this->fileNamespace($contents);

			if (!preg_match_all('/^(?:(?:abstract|final|readonly)\s+)*class\s+(\w+)/mi', $contents, $matches)) {
				continue;
			}

			foreach ($matches[1] as $class) {
				$fqcn = $namespace !== null ? $namespace.'\\'.$class : $class;
				$this->abbrModels[$fqcn] = $class;
			}
		}
	}


	// Rewrite
	//
	// Two independent, individually idempotent rewrites:
	//
	//  1. In a file that composes GetAsOptionsAbbr: swap the trait for
	//     GetAsOptions -- its fully-qualified `use ...;` import (keeping any
	//     `as Alias`) and its bare class-body `use GetAsOptionsAbbr;`
	//     inclusion -- then add 'abbr' as the first argument to every
	//     no-argument self::/static::/$this-> getAsOptions() call in that
	//     same file. Skipped whole if the file also already references the
	//     real GetAsOptions trait: composing both was never supported (they
	//     declare the same method), so there's no safe automatic merge --
	//     additionalChecks() reports it for a hand fix.
	//
	//  2. In every file: add 'abbr' as the first argument to every
	//     no-argument `<Model>::getAsOptions()` call whose `<Model>` resolves
	//     -- imported (under its own name or an alias), same-namespace, or
	//     fully qualified -- to a class prepare() found composing
	//     GetAsOptionsAbbr.
	//
	// Only no-argument calls are rewritten. `getAsOptions('abbr')` already
	// carries its key, and any call that passes something else needs a human
	// to decide whether that argument still belongs first now that $key
	// comes first -- additionalChecks() lists those, along with
	// `->getAsOptions()` calls on a receiver this step can't resolve to a
	// class.
	//
	// @param $contents string - The file contents to rewrite
	// @param $customAliases array - Unused; a trait swap here can't collide
	//                                the way a namespaced rename can, and the
	//                                one unsupported case (both traits) is
	//                                reported rather than aliased around.
	// @param $confirmedReplacements array - Unused; the swap preserves
	//                                        behavior (GetAsOptionsAbbr
	//                                        already delegates here), so it
	//                                        needs no explicit sign-off.
	//
	// @return string
	//
	public function rewrite (string $contents, array $customAliases = [], array $confirmedReplacements = []): string
	{
		if ($this->composesAbbrTrait($contents) && !$this->alsoComposesOptionsTrait($contents)) {
			$contents = $this->swapTrait($contents);
			$contents = $this->addKeyToLocalCalls($contents);
		}

		return $this->addKeyToModelCalls($contents);
	}


	// Conflicts
	//
	// Nothing here fits the alias-collision model conflicts() drives: the
	// one unsupported case (a model composing both traits) has no
	// alias-based resolution, so it's surfaced by additionalChecks()
	// instead.
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array
	//
	public function conflicts (string $contents): array
	{
		return [];
	}


	// Flagged
	//
	// The trait swap is a pure, behavior-preserving 1:1, so nothing here is
	// gated behind a "replace anyway?" confirmation the way
	// Upgrade_0_3_0::INTERACTIVE_REPLACEMENTS is.
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array
	//
	public function flagged (string $contents): array
	{
		return [];
	}


	// Additional Checks
	//
	// Reports what this step deliberately won't rewrite on its own:
	//
	//  - A model composing both GetAsOptions and GetAsOptionsAbbr -- never
	//    valid, no safe merge.
	//  - `<Model>::getAsOptions(...)` calls that already pass arguments --
	//    with GetAsOptions the first argument is the key, so a human needs to
	//    confirm whether 'abbr' should be inserted ahead of it.
	//  - `->getAsOptions()` calls on a receiver (a variable, a relation, a
	//    query result) this step can't resolve to a class.
	//
	// Purely informational -- prints nothing when there's nothing to report,
	// and never prompts.
	//
	// @param $command Command - The running console command, for output
	// @param $contentsByPath array - File path => contents, from this step's scan
	// @param $apply bool - Unused; this step only ever reports here, never prompts
	//
	// @return void
	//
	public function additionalChecks (Command $command, array $contentsByPath, bool $apply): void
	{
		$bothTraits = [];
		$argfulCalls = [];
		$unresolvedCalls = [];

		foreach ($contentsByPath as $path => $contents) {
			if ($this->composesAbbrTrait($contents) && $this->alsoComposesOptionsTrait($contents)) {
				$bothTraits[] = $path;
			}

			if ($this->abbrModels === []) {
				continue;
			}

			foreach ($this->callablePrefixes($contents) as $prefix) {
				if (preg_match('/(?<![\w\\\\])\\\\?'.preg_quote($prefix, '/').'::getAsOptions\s*\(\s*[^)\s]/', $contents)) {
					$argfulCalls[] = $path;
					break;
				}
			}

			if (preg_match('/(?<!\$this)->\s*getAsOptions\s*\(\s*\)/', $contents)) {
				$unresolvedCalls[] = $path;
			}
		}

		$this->reportPaths(
			$command,
			$bothTraits,
			'These files compose both GetAsOptions and GetAsOptionsAbbr, which was never supported -- left untouched, migrate by hand:'
		);

		$this->reportPaths(
			$command,
			$argfulCalls,
			"These files already pass arguments to a migrated model's getAsOptions() -- \$key is now the first argument, so check whether 'abbr' belongs ahead of what's there:"
		);

		$this->reportPaths(
			$command,
			$unresolvedCalls,
			"These files call ->getAsOptions() on a value this step can't resolve to a class -- if the receiver is a migrated model, add 'abbr' as the first argument by hand:"
		);
	}


	// Composes Abbr Trait
	//
	// True when $contents pulls GetAsOptionsAbbr in -- either its
	// fully-qualified `use ...;` import (with or without an `as` alias) or a
	// bare class-body `use GetAsOptionsAbbr;` inclusion (alone or in a
	// comma-separated list). A bare mention in a comment or string doesn't
	// count.
	//
	// @param $contents string
	//
	// @return bool
	//
	protected function composesAbbrTrait (string $contents): bool
	{
		return preg_match('/^use\s+'.preg_quote(self::ABBR_TRAIT, '/').'(?:\s+as\s+\w+)?\s*;/mi', $contents) === 1
			|| preg_match('/^[ \t]+use\s+[^;]*\bGetAsOptionsAbbr\b[^;]*;/m', $contents) === 1;
	}


	// Also Composes Options Trait
	//
	// True when a file already references the real GetAsOptions trait --
	// its FQCN import or a bare class-body `use GetAsOptions;` inclusion.
	// `\bGetAsOptions\b` never matches inside "GetAsOptionsAbbr" (no word
	// boundary between "...Options" and "Abbr"), so this stays false for a
	// file that only has the deprecated trait.
	//
	// @param $contents string
	//
	// @return bool
	//
	protected function alsoComposesOptionsTrait (string $contents): bool
	{
		return preg_match('/^use\s+'.preg_quote(self::OPTIONS_TRAIT, '/').'\s*;/mi', $contents) === 1
			|| preg_match('/^[ \t]+use\s+[^;]*\bGetAsOptions\b[^;]*;/m', $contents) === 1;
	}


	// Swap Trait
	//
	// Replaces GetAsOptionsAbbr with GetAsOptions in $contents: the trait's
	// fully-qualified name wherever it appears (covering the `use ...;`
	// import and keeping any `as Alias`), then its bare short name (covering
	// the class-body inclusion, when it isn't aliased). Naturally idempotent
	// -- once done, "GetAsOptionsAbbr" no longer appears, so a second pass
	// finds nothing.
	//
	// @param $contents string
	//
	// @return string
	//
	protected function swapTrait (string $contents): string
	{
		$contents = str_replace(self::ABBR_TRAIT, self::OPTIONS_TRAIT, $contents);

		return preg_replace('/\bGetAsOptionsAbbr\b/', 'GetAsOptions', $contents);
	}


	// Add Key To Local Calls
	//
	// Adds 'abbr' as the first argument to every no-argument
	// self::/static::/$this-> getAsOptions() call -- the forms a model's own
	// file uses to call the trait method on itself. Whatever whitespace
	// already sits before the `(` is kept.
	//
	// @param $contents string
	//
	// @return string
	//
	protected function addKeyToLocalCalls (string $contents): string
	{
		$contents = preg_replace(
			'/(\b(?:self|static)::getAsOptions\s*\()\s*\)/',
			'${1}\'abbr\')',
			$contents
		);

		return preg_replace(
			'/(\$this->getAsOptions\s*\()\s*\)/',
			'${1}\'abbr\')',
			$contents
		);
	}


	// Add Key To Model Calls
	//
	// Adds 'abbr' as the first argument to every no-argument
	// `<Model>::getAsOptions()` call whose `<Model>` resolves to a class
	// prepare() recorded. callablePrefixes() yields every name this file can
	// spell one of those models as; the optional leading `\` in the pattern
	// covers a fully-qualified call written with a root-namespace slash.
	//
	// @param $contents string
	//
	// @return string
	//
	protected function addKeyToModelCalls (string $contents): string
	{
		if ($this->abbrModels === []) {
			return $contents;
		}

		foreach ($this->callablePrefixes($contents) as $prefix) {
			$contents = preg_replace(
				'/(?<![\w\\\\])(\\\\?'.preg_quote($prefix, '/').'::getAsOptions\s*\()\s*\)/',
				'${1}\'abbr\')',
				$contents
			);
		}

		return $contents;
	}


	// Callable Prefixes
	//
	// Every string `X` such that `X::getAsOptions()` in this file refers to
	// a recorded abbr model: the model's fully-qualified name, whatever name
	// it's imported under here (its own or an `as` alias), and its bare
	// short name when this file shares the model's namespace and hasn't
	// imported that short name as something else.
	//
	// @param $contents string
	//
	// @return array<string>
	//
	protected function callablePrefixes (string $contents): array
	{
		$namespace = $this->fileNamespace($contents);
		$imports = $this->existingImports($contents);
		$prefixes = [];

		foreach ($this->abbrModels as $fqcn => $short) {
			$prefixes[$fqcn] = true;

			foreach ($imports as $name => $importedFqcn) {
				if ($importedFqcn === $fqcn) {
					$prefixes[$name] = true;
				}
			}

			if ($namespace !== null && $namespace.'\\'.$short === $fqcn && !array_key_exists($short, $imports)) {
				$prefixes[$short] = true;
			}
		}

		return array_keys($prefixes);
	}


	// Report Paths
	//
	// Prints $message followed by one indented line per path, or nothing
	// when $paths is empty. Mirrors UpgradeCommand's own conflict/flagged
	// reporting style.
	//
	// @param $command Command
	// @param $paths array<string>
	// @param $message string
	//
	// @return void
	//
	protected function reportPaths (Command $command, array $paths, string $message): void
	{
		$paths = array_unique($paths);

		if ($paths === []) {
			return;
		}

		$command->newLine();
		$command->warn($message);

		foreach ($paths as $path) {
			$command->line("  {$path}");
		}
	}


	// File Namespace
	//
	// Parses $contents' own `namespace X;` declaration, if any. Mirrors
	// Upgrade_0_3_0's copy -- a plain text heuristic, not a real parser.
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


	// Existing Imports
	//
	// Parses every top-level `use Some\Name;` / `use Some\Name as Alias;`
	// statement in $contents into a short-name-or-alias => FQCN map. Matches
	// only unindented `use` lines, so the indented class-body trait
	// inclusions are excluded. Mirrors Upgrade_0_3_0's copy.
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


}
