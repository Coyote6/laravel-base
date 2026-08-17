<?php


namespace Coyote6\LaravelBase\Upgrades;


interface UpgradeStep {


	// Version
	//
	// The coyote6/laravel-base version this step upgrades a consuming
	// application to.
	//
	// @return string
	//
	public function version (): string;


	// Rewrite
	//
	// Returns $contents with every applicable change for this step
	// applied. Must be idempotent -- safe to call against a file this step
	// doesn't apply to, or has already been applied to, since
	// UpgradeCommand always re-scans from scratch rather than tracking
	// which steps already ran against a given application.
	//
	// @param $contents string - The file contents to rewrite
	// @param $customAliases array - Old FQCN => developer-chosen alias, for
	//                                changes conflicts() reported that the
	//                                developer resolved interactively (see
	//                                UpgradeCommand::resolveConflictAliases()).
	//                                A step with nothing needing this may
	//                                ignore the parameter entirely.
	// @param $confirmedReplacements array - Old FQCN => true, for changes
	//                                        flagged() reported that the
	//                                        developer confirmed applying
	//                                        anyway despite the behavior
	//                                        change (see
	//                                        UpgradeCommand::resolveFlaggedReplacements()).
	//                                        A step with nothing needing
	//                                        this may ignore the parameter
	//                                        entirely.
	//
	// @return string
	//
	public function rewrite (string $contents, array $customAliases = [], array $confirmedReplacements = []): string;


	// Conflicts
	//
	// Checks $contents for changes this step would normally apply that
	// instead need manual attention -- e.g. a name this step would
	// introduce is already bound to something unrelated in this file.
	// Anything reported here must NOT also be applied by rewrite() unless
	// resolved via $customAliases.
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array Old FQCN => colliding short name, one entry per
	//               conflict found
	//
	public function conflicts (string $contents): array;


	// Flagged
	//
	// Checks $contents for old references this step recognizes but never
	// rewrites unconditionally, because the replacement is more than a
	// pure rename (e.g. a real behavior change) -- these need the
	// developer's own explicit go-ahead, via $confirmedReplacements on
	// rewrite(), not just a name collision to resolve.
	//
	// @param $contents string - The file contents to inspect
	//
	// @return array Old FQCN => suggested new FQCN, one entry per old
	//               reference found
	//
	public function flagged (string $contents): array;


}
