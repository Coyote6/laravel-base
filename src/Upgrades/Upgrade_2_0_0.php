<?php


namespace Coyote6\LaravelBase\Upgrades;

use Illuminate\Console\Command;


class Upgrade_2_0_0 implements UpgradeStep {


	// Renamed Methods
	//
	// Old BootTraits convention method name => new name, for every one
	// renamed in v2.0.0 to make explicit that it fires on Eloquent's
	// `creating` event -- an "OnBoot" suffix was considered and rejected,
	// since it would have collided in spirit with this package's own
	// unrelated "Boot" terminology (BootTraits, the Boot/ namespace,
	// Laravel's own boot()). Owner/User ship new in this same release
	// already named this way, so neither needs an entry here.
	protected const RENAMED_METHODS = [
		'createAuthor' => 'assignAuthorOnModelCreation',
		'createOriginalAuthor' => 'assignOriginalAuthorOnModelCreation',
		'createClient' => 'assignClientOnModelCreation',
		'createMachineName' => 'assignMachineNameOnModelCreation',
		'createSlug' => 'assignSlugOnModelCreation',
	];


	// Version
	//
	// @return string
	//
	public function version (): string
	{
		return '2.0.0';
	}


	// Rewrite
	//
	// Renames every RENAMED_METHODS method definition and `->` call site
	// found in $contents to its new name -- a pure, unconditional rename.
	// A consuming app only ever sees these names by defining or
	// overriding the method itself (BootTraits always calls them
	// internally by name, never anything a model's own code has to spell
	// out), so there's nothing more than text to change. Whatever
	// whitespace already sits between the name and its `(` is captured
	// and reinserted as-is, rather than imposing this package's own
	// space-before-parenthesis style onto a consuming app's code.
	//
	// Naturally idempotent: once a name has already changed,
	// "createAuthor(" no longer appears as its own token in the rewritten
	// text -- it's followed by "OnModelCreation" rather than
	// whitespace/"(" -- so a second pass matches nothing. Also skips a
	// method entirely if the new name is already defined in the same
	// file, to avoid introducing a duplicate method declaration.
	//
	// @param $contents string - The file contents to rewrite
	// @param $customAliases array - Unused; a method rename can't collide
	//                                with an existing class the way a
	//                                trait rename can (see conflicts()).
	// @param $confirmedReplacements array - Unused; every rename here is
	//                                        a pure rename, not a
	//                                        behavior change needing
	//                                        explicit sign-off.
	//
	// @return string
	//
	public function rewrite (string $contents, array $customAliases = [], array $confirmedReplacements = []): string
	{
		foreach (self::RENAMED_METHODS as $old => $new) {
			if (!str_contains($contents, $old)) {
				continue;
			}

			if (str_contains($contents, "function {$new}")) {
				continue;
			}

			$contents = preg_replace('/\bfunction\s+'.preg_quote($old, '/').'(\s*)\(/', 'function '.$new.'$1(', $contents);
			$contents = preg_replace('/->(\s*)'.preg_quote($old, '/').'(\s*)\(/', '->$1'.$new.'$2(', $contents);
		}

		return $contents;
	}


	// Conflicts
	//
	// A method rename can't collide with an existing class the way a
	// trait rename can (see Upgrade_0_3_0::detectConflicts()) -- there's
	// no import table to consult, so this is always empty.
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
	// Every RENAMED_METHODS entry is a pure rename, not a behavior
	// change -- nothing here needs the developer's explicit go-ahead the
	// way Upgrade_0_3_0::INTERACTIVE_REPLACEMENTS does.
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
	// Nothing outside a per-file text rewrite for this step.
	//
	// @param $command Command - The running console command, for prompting
	// @param $contentsByPath array - File path => contents, from this step's scan
	// @param $apply bool - Skip every prompt
	//
	// @return void
	//
	public function additionalChecks (Command $command, array $contentsByPath, bool $apply): void
	{
		//
	}


}
