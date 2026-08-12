<?php


namespace Coyote6\LaravelBase\Traits\Models\Boot;

use Illuminate\Support\Str;
use InvalidArgumentException;


trait ResolvesMachineName {


	// Allowed Machine Name Methods
	protected const ALLOWED_MACHINE_NAME_METHODS = [
		'strictKebab',
		'strictSnake',
		'pureKebab',
		'pureSnake',
		'kebab',
		'snake',
		'dot',
		'slug',
		'studly',
		'pascal',
		'camel',
		'lower',
		'upper',
		'deduplicate',
		'transliterate',
	];


	// Resolve Machine Name
	//
	// Runs $source through Str::ascii(), then whichever Str:: method is
	// configured under machine_name.method, passing along any configured
	// machine_name.method_parameters.
	//
	// @ai
	//		$method is checked against ALLOWED_MACHINE_NAME_METHODS before use
	//		-- an unlisted or misspelled method would otherwise throw deep
	//		inside Laravel's macro resolution on every single model save, or
	//		worse, silently misbehave (Str::remove()'s subject is its 2nd
	//		argument, not its 1st, so calling it here would treat $source as
	//		$search instead).
	//
	//		Str::ascii() always runs first because only Str::slug() already
	//		transliterates internally -- every other candidate either passes
	//		non-ASCII bytes through untouched (kebab/snake/studly/pascal/
	//		camel) or mangles them: this package's own pureKebab/pureSnake/
	//		strictKebab/strictSnake/dot macros use a non-Unicode-aware
	//		[^a-z0-9-] pattern, so "café" loses the é entirely instead of
	//		becoming "cafe". Since ascii() always runs, plain "ascii" is
	//		deliberately left off the allowed list -- picking it as the end
	//		method would just repeat the same call for no effect.
	//
	//		method_parameters is cast to an array so config can hold null, a
	//		single scalar (e.g. pureKebab's $consecutiveDashes), or a list
	//		(e.g. slug's [$separator, $language, $dictionary]) without the
	//		caller needing to know which shape a given method expects --
	//		(array) null collapses to [], so a method with no extra
	//		parameters (e.g. strictKebab) is just called with $source.
	//
	// @param $source string - The raw string to convert [Ex: "Test - My Machine Name"]
	//
	// @return string
	//
	protected function resolveMachineName (string $source): string
	{
		$method = config('coyote6-base.machine_name.method', 'strictKebab');

		if (!in_array($method, self::ALLOWED_MACHINE_NAME_METHODS, true)) {
			throw new InvalidArgumentException("Invalid coyote6-base.machine_name.method: [{$method}].");
		}

		$parameters = (array) config('coyote6-base.machine_name.method_parameters');

		return Str::{$method}(Str::ascii($source), ...$parameters);
	}


	// Resolve Machine Name Reference
	//
	// Returns the attribute name to read the raw source string from, per
	// the machine_name.reference config value.
	//
	// @return string
	//
	protected function resolveMachineNameReference (): string
	{
		return config('coyote6-base.machine_name.reference', 'name');
	}


}
