<?php


namespace Coyote6\LaravelBase\Upgrades;


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

	// Interactive Replacements
	//
	// Old fully-qualified trait name => new fully-qualified trait name, for
	// traits with no direct 1:1 replacement -- these are never rewritten
	// unconditionally. flagged() reports files referencing one so the
	// developer can decide by hand.
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
	//		renames above.
	protected const INTERACTIVE_REPLACEMENTS = [
		'Coyote6\LaravelBase\Traits\HasUuid' => 'Illuminate\Database\Eloquent\Concerns\HasUuids',
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
	// $contents: the fully-qualified name wherever it appears, plus the
	// bare short class name (word-boundary-safe) to catch `use ShortName;`
	// trait inclusions left pointing at the old name once its import line
	// changes.
	//
	// @param $contents string - The file contents to rewrite
	//
	// @return string
	//
	public function rewrite (string $contents): string
	{
		$renamed = $this->sortedRenames();
		$conflicts = $this->detectConflicts($contents, $renamed);
		$applicable = $conflicts === [] ? $renamed : array_diff_key($renamed, $conflicts);

		foreach ($applicable as $old => $new) {
			$contents = str_replace($old, $new, $contents);

			$oldShort = class_basename($old);
			$newShort = class_basename($new);

			if ($oldShort !== $newShort) {
				$contents = preg_replace('/\b'.preg_quote($oldShort, '/').'\b/', $newShort, $contents);
			}
		}

		return $contents;
	}


	// Conflicts
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array Old FQCN => colliding new short name
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
	// Checks $contents for renames that would introduce a bare short name
	// already bound to a *different* class by some other import already in
	// this file (e.g. this file already has `use App\Models\Author;`, and
	// a plain `use Coyote6\LaravelBase\Traits\HasAuthor;` here would need
	// to become `use Coyote6\LaravelBase\Traits\Models\Boot\Author;`).
	//
	// @ai
	//		Only unaliased old imports are checked. If the developer already
	//		wrote `use Coyote6\LaravelBase\Traits\HasAuthor as Whatever;`,
	//		rewrite() preserves that alias verbatim -- the new FQCN never
	//		introduces a bare "Author" into the file's import table, so
	//		there's nothing to collide.
	//
	//		Known gap: this only catches collisions against other `use`
	//		import statements, via existingImports() below. It can't detect
	//		a same-namespace class of the same short name resolved with no
	//		import at all (e.g. this file and `Author` both live in
	//		`App\Models`) -- that would need a real classmap lookup, not
	//		just a text scan of this one file. Rare in practice since PSR-4
	//		project layouts put one class per file/namespace, but worth
	//		knowing this check isn't exhaustive.
	//
	// @param $contents string - The file contents to inspect
	// @param $renamed array - Old FQCN => new FQCN
	//
	// @return array Old FQCN => colliding new short name, for every conflict found
	//
	protected function detectConflicts (string $contents, array $renamed): array
	{
		$existingImports = $this->existingImports($contents);
		$conflicts = [];

		foreach ($renamed as $old => $new) {
			if (!str_contains($contents, $old)) {
				continue;
			}

			if (preg_match('/^use\s+'.preg_quote($old, '/').'\s+as\s+/mi', $contents)) {
				continue;
			}

			$newShort = class_basename($new);

			if (
				array_key_exists($newShort, $existingImports) &&
				$existingImports[$newShort] !== $old &&
				$existingImports[$newShort] !== $new
			) {
				$conflicts[$old] = $newShort;
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


}
