# Changelog

All notable changes to `coyote6/laravel-base` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.3.1] - 2026-08-13

### Added

- `php artisan coyote6-base:upgrade` — runs every registered upgrade step
  against a consuming application, each rewriting old trait references to
  whatever that step's target version needs. Currently ships one step, the
  v0.2.7 → v0.3.0 rename described below. Scans `app/` and `database/` by
  default (`--path` to override); reports a per-step file count and asks
  for confirmation before writing, or pass `--apply` to skip the prompt and
  write immediately (e.g. in CI). A rename is skipped and listed separately
  for manual review, rather than applied blindly, when it would collide
  with an existing, unrelated class of the same short name already
  resolvable in a file — whether imported explicitly or just resolved via
  a shared namespace. See README's "Upgrade From 0.2.7" section.
- `Coyote6\LaravelBase\Upgrades\UpgradeStep` interface — the extension
  point future breaking releases use to add their own upgrade step
  alongside `Upgrade_0_3_0`, without changing `UpgradeCommand` itself.

## [0.3.0] - 2026-08-13

### Breaking

Every trait moved out of the flat `Coyote6\LaravelBase\Traits` namespace into a
subdirectory grouped by purpose, and four were renamed along the way:

| Old | New |
|---|---|
| `Coyote6\LaravelBase\Traits\HasAuthor` | `Coyote6\LaravelBase\Traits\Models\Boot\Author` |
| `Coyote6\LaravelBase\Traits\HasClient` | `Coyote6\LaravelBase\Traits\Models\Boot\Client` |
| `Coyote6\LaravelBase\Traits\HasMachineName` | `Coyote6\LaravelBase\Traits\Models\Boot\MachineName` |
| `Coyote6\LaravelBase\Traits\HasMachineNameAsId` | `Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId` |
| `Coyote6\LaravelBase\Traits\HasUuid` | removed — use Laravel's native `Illuminate\Database\Eloquent\Concerns\HasUuids` |
| `Coyote6\LaravelBase\Traits\BootTraits` | `Coyote6\LaravelBase\Traits\Models\BootTraits` |
| `Coyote6\LaravelBase\Traits\GetAsOptions` | `Coyote6\LaravelBase\Traits\Models\GetAsOptions` |
| `Coyote6\LaravelBase\Traits\GetAsOptionsAbbr` | `Coyote6\LaravelBase\Traits\Models\GetAsOptionsAbbr` |
| `Coyote6\LaravelBase\Traits\GetBySlug` | `Coyote6\LaravelBase\Traits\Models\GetBySlug` |
| `Coyote6\LaravelBase\Traits\DropsIndexes` | `Coyote6\LaravelBase\Traits\Database\DropsIndexes` |
| `Coyote6\LaravelBase\Traits\ServiceProviderSeedsDb` | `Coyote6\LaravelBase\Traits\Database\ServiceProviderSeedsDb` |
| `Coyote6\LaravelBase\Traits\ReadsCsv` | `Coyote6\LaravelBase\Traits\Files\ReadsCsv` |

No deprecation shim ships for the old names — this is a hard cutover. Update
every `use` statement in consuming models/providers/migrations to the new
namespace.

The rename of `Author`/`Client`/`MachineName`/`MachineNameAsId` isn't a
find-and-replace only: their hardcoded field names moved into config too
(see Added below). Defaults match the old hardcoded names exactly, so no
config publishing is required unless you want to override them.

Behavioral changes beyond the namespace/class renames:

- `GetBySlug::getBySlug()` now queries `config('coyote6-base.slug.field')`
  (default `slug`) instead of a hardcoded `'slug'` column.
- `getCurrentUserClientId()` now reads
  `config('coyote6-base.client.reference')` (default `client_id`) off the
  current user instead of a hardcoded `'client_id'` attribute.
- `Client` no longer calls `$this->getUser()` — that method (along with
  `BootTraits::getUserId()`) is gone. `Client` now calls the global
  `getCurrentUserClientId()` helper directly.

### Added

- `OriginalAuthor` trait — stamps the current user's id the same way
  `Author` does, independently, as a permanent foreign-key-free record for
  schemas where `author_id` has an `ON DELETE SET NULL` foreign key.
- `Slug` trait — generates a slug via `Str::slug()`.
- `ResolvesMachineName` trait (internal, composed by `MachineName` and
  `MachineNameAsId`) — resolves `machine_name.method` against an allowlist
  of `Str::` methods/macros, running every one through `Str::ascii()` first.
- `config/coyote6-base.php` — publishable config covering every
  field/reference/method option across `machine_name`, `author`,
  `original_author`, `client`, and `slug`.
- Pest test suite (via `orchestra/testbench`) covering every documented
  trait and helper.
- GitHub Actions CI running the test suite on PHP 8.2-8.4, against both
  `prefer-lowest` and `prefer-stable` dependency resolutions.
- `php` (`^8.2`) and `illuminate/support`/`illuminate/database`
  (`^12.0|^13.0`) requirements in `composer.json` — previously undeclared
  entirely. Narrower than it could theoretically be: `--prefer-lowest`
  showed the package's own dev tooling (`pestphp/pest-plugin-laravel`)
  can't actually resolve below Laravel 12, so that's the lowest version
  this range claims support for verification, not `illuminate/support`'s
  own floor.

### Fixed

- `src/Helpers/Helpers.php` had a syntax error in `getCurrentUserId()`'s
  signature that broke composer's `files` autoload for the whole package on
  every request.
- `BootTraits::getUser()`'s authentication check was inverted: it returned
  a blank `User::make()` when a user *was* authenticated, and `null` when
  there wasn't — so `HasClient`'s `client_id` was silently always null in
  the normal case. The method is removed; see Breaking above.

[Unreleased]: https://github.com/Coyote6/laravel-base/compare/v0.3.1...master
[0.3.1]: https://github.com/Coyote6/laravel-base/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/Coyote6/laravel-base/compare/v0.2.7...v0.3.0
