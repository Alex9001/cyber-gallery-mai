# Release process

Version 2.0.0 is currently unreleased. Do not describe a workflow artifact as a release, and do not create a tag or GitHub Release until every gate below passes.

## Reproducible package

Use a committed, clean checkout with the locked Node and Composer dependencies:

```bash
npm ci
composer install --no-interaction --no-progress --prefer-dist
npm run build:zip
```

The release builder compares `scripts/release-manifest.json` with Git's tracked runtime files and inspects every path under `assets/`, `blocks/`, `classes/`, `includes/`, and `languages/`. Missing manifest entries, untracked or ignored extras, special files, and symlinks stop the build. The staged plugin directory contains only the exact source manifest plus generated `release-provenance.json`.

For version 2.0.0, a clean build produces:

- `build/cyber-gallery-mai-2.0.0.zip`
- `build/cyber-gallery-mai-2.0.0.zip.sha256`
- `build/cyber-gallery-mai-2.0.0.zip.provenance.json`

Verify the archive from inside `build/` with:

```bash
sha256sum --check cyber-gallery-mai-2.0.0.zip.sha256
unzip -p cyber-gallery-mai-2.0.0.zip cyber-gallery-mai/release-provenance.json
```

The embedded document records the plugin/version, source revision and tree, release-manifest digest, normalized source epoch, artifact name, and clean/dirty state. Its external companion adds the archive byte length and SHA-256 digest and binds the embedded document by its own digest. Neither document contains a wall-clock build time, so repeating the build at the same clean revision and `SOURCE_DATE_EPOCH` produces the same package inputs and provenance. By default the commit timestamp supplies `SOURCE_DATE_EPOCH`.

`CYBER_GALLERY_MAI_ALLOW_DIRTY_BUILD=1` is only for local diagnosis. Dirty output includes `-dirty-{revision}-{full fingerprint}` in every artifact basename and records the fingerprint in provenance. It is deliberately impossible for dirty output to overwrite or masquerade as the canonical release filename.

## CI trust boundary

Public pull requests and branch pushes run PHP checks, JavaScript lint/unit/distribution checks, and every self-contained Playwright fixture in Chromium, Firefox, and WebKit. The staged-distribution job downloads and verifies these exact inputs before giving their extracted directories to wp-env:

- WordPress 7.1: SHA-256 `d1ae02b5ae18428031ffc3943659fa87ab361d827f4aa804adf9276e4dc75df6`
- Plugin Check 2.1.0: SHA-256 `6ff4bd2145f3befcf907df158cc466b1649dafed5686de8369907403c3013fc4`
- Mai Engine 2.40.0: commit `f53e26fd2989bb2e6a50721ea86b771d5ffdff03`

Strict Plugin Check excludes only `plugin_header_requires_plugins_not_in_directory` for proprietary Mai Engine and `plugin_updater_detected` for the repository-specific Update URI. Any other finding fails CI.

The authenticated WordPress 7.1 editor test and `tests/runtime-smoke.php` are not public-fixture coverage. They run only from a `v*` tag or an explicit workflow dispatch with `run_release_integration=true`, after public jobs pass, on a runner labeled `self-hosted`, `linux`, `x64`, and `cyber-gallery-mai-release`. Protect the `cyber-gallery-mai-release` environment with required reviewers and configure:

- Variables: `CYBER_GALLERY_MAI_WORDPRESS_PATH`, `CYBER_GALLERY_MAI_WP_URL`, and `CYBER_GALLERY_MAI_WP_POST_ID`
- Secrets: `CYBER_GALLERY_MAI_WP_USERNAME` and `CYBER_GALLERY_MAI_WP_PASSWORD`

The authorized site must be disposable, run WordPress 7.1 and PHP 8.3 or later, use Genesis with an active Mai child theme, have Mai Engine 2.40.0 or later active, and contain the fixture post plus at least two image attachments.

## Publication checklist

1. Confirm `git status --short` is empty and the intended commit is on the protected default branch.
2. Run `npm ci`, `composer install`, `npm test`, `composer test`, and both dependency audits.
3. Run `npm run build:zip` twice from the same clean revision and confirm the ZIP and both sidecars are byte-identical.
4. Run or dispatch the protected release-integration workflow and require every job to pass.
5. Recheck the `.zip.sha256` file and inspect both provenance documents.
6. Create the signed `v2.0.0` tag only after the protected gate passes for the exact commit.
7. Create a durable GitHub Release from that tag and attach the ZIP plus both sidecars without rebuilding them.
8. Update the repository/readme release-status wording in the release commit; never point users at an expiring workflow artifact.

This repository is prepared for those steps, but this work does not create the tag or GitHub Release.
