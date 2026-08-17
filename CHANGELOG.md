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
  write immediately (e.g. in CI). `HasAuthor`/`HasClient`/`HasMachineName`/
  `HasMachineNameAsId` are always rewritten aliased as `BootAuthor`/
  `BootClient`/`BootMachineName`/`BootMachineNameAsId`, since those new
  short names are generic enough to routinely collide with a real domain
  class. If even that alias collides with an existing, unrelated class of
  the same short name already resolvable in a file — whether imported
  explicitly or just resolved via a shared namespace — the command asks
  once per distinct collision for a different alias to use instead, or
  skips and lists the file for manual review if left blank (never prompted
  under `--apply`). `HasUuid` (no direct replacement -- see Breaking above
  for why) is handled the same way: the command asks once whether to
  replace it with `Illuminate\Database\Eloquent\Concerns\HasUuids` anyway,
  applying that answer to every file referencing it this step, rather than
  only ever flagging it for a manual fix. If the scan finds machine-name
  generation in use at all, it also checks whether `coyote6-base`'s config
  is published, offers to publish it if not, and asks which
  `machine_name.method` this app should use (defaulting to whatever's
  already configured) rather than leaving the v0.3.0 default change to a
  README paragraph. See README's "Upgrade From 0.2.7" section.
- `Coyote6\LaravelBase\Upgrades\UpgradeStep` interface — the extension
  point future breaking releases use to add their own upgrade step
  alongside `Upgrade_0_3_0`, without changing `UpgradeCommand` itself.
  Includes `additionalChecks()`, a per-step hook for whatever isn't a
  per-file text rewrite (an environment or config check, like the
  `machine_name.method` one above).

### Fixed

- `composer.json`'s declared `php` floor raised from `^8.2` to `^8.3` --
  `pestphp/pest`/`pestphp/pest-plugin-laravel` `^4.7`/`^4.1` both hard-require
  PHP `^8.3.0`, so the CI matrix's PHP 8.2 leg was never actually
  installable, on either `prefer-lowest` or `prefer-stable`. `^8.2` was
  claimed but never verified by anything; `^8.3` is what CI can actually
  confirm.
- CI's PHP 8.2 matrix leg removed for the same reason. Dependency
  installation also switched from a hand-rolled `composer update` to
  [`ramsey/composer-install`](https://github.com/ramsey/composer-install),
  which caches downloaded packages across runs and retries transient
  failures -- fresh installs (no `composer.lock` is committed) were hitting
  `429`s from `codeload.github.com` on every matrix leg, a separate,
  unauthenticated host that a `github-oauth` token doesn't cover, unrelated
  to this package's own dependency graph.
- CI's `extensions:` list was missing `pdo_sqlite` -- every test touches
  the database via Eloquent through testbench's default in-memory SQLite
  connection, which needs the PDO driver specifically, not just the raw
  `sqlite3` extension.
- CI's `shivammathur/setup-php` step now pins `ini-values: zend.assertions=1`
  -- its default `production` ini sets `zend.assertions=-1`, which strips
  `assert()` calls (arguments included) at compile time. `pestphp/pest`
  v4.7.0's own `Configuration` plugin wraps the write of its generated
  `.pest.xml` in `assert(is_int(file_put_contents(...)))`, so under
  `zend.assertions=-1` that file is silently never written, `realpath()`
  on the missing path returns `false`, and Pest ends up passing PHPUnit an
  empty `--configuration` value -- surfacing as the unrelated-looking
  `Could not read XML from file "--cache-directory"` on every matrix leg.
  Not a bug in this package or its CI config -- a latent `pestphp/pest`
  v4.7.0 defect that only production-mode `zend.assertions` triggers.
- `composer.json`'s `description` still advertised "Provides UUID trait for
  models using it as a primary key" -- stale since `HasUuid` was removed in
  the Breaking changes above.

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
