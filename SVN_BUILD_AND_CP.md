# SVN Build and Copy

`build-and-cp` builds the production version of TechByIt SMTP and copies its production files into the WordPress.org SVN `trunk`. It does not run SVN commands, delete files from the SVN working copy, or modify `assets/`, `branches/`, or `tags/`.

## Paths

- Source: `/home/mdabbasuddin/www/smtp/`
- Destination: `/home/mdabbasuddin/Abbas Document/wp-plugin-dir/techbyit-smtp/trunk/`
- Command: `/home/mdabbasuddin/.local/bin/build-and-cp`

The paths are absolute, so the command works from any current directory.

## Production build

The project documentation and configuration define this production sequence:

```bash
composer install --no-dev --optimize-autoloader
pnpm build
```

The copy starts only if both commands succeed. A failed build returns its nonzero status and leaves SVN `trunk` unchanged.

## Production files copied

The command copies only the paths used by the existing production release package:

```text
techbyit-smtp.php
readme.txt
LICENSE
composer.json
composer.lock
src/
vendor/
dist/
licenses/
```

This allowlist excludes development-only content, including Git metadata, GitHub configuration, `node_modules/`, the pnpm store, environment files, editor settings, logs, Docker files, tests, coverage, screenshots, release archives, frontend source, build configuration, and PHP lint configuration. `.svn/` is also excluded defensively.

The copy uses `rsync` without `--delete`. It adds new files and updates existing files, but it never removes stale files from SVN `trunk`.

## Run

From any directory:

```bash
build-and-cp
```

## Dry run

A dry run performs the production build and previews the files that rsync would copy without changing SVN `trunk`:

```bash
build-and-cp --dry-run
```

## Manual SVN review

After a normal copy, inspect and manage SVN manually:

```bash
cd "/home/mdabbasuddin/Abbas Document/wp-plugin-dir/techbyit-smtp"
svn status
svn diff
```

Run `svn add`, `svn delete`, `svn commit`, and tag operations only after reviewing the changes. `build-and-cp` never runs them.
