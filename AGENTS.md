# Servinder Subsites — Divi development instructions

- This repository is the canonical source for the optional Divi adapter. Do not edit plugin files in the live WordPress volume.
- Divi remains an optional integration. Do not add Divi classes, assets, or dependencies to the `servinder-subsites` core plugin.
- Keep visibility decisions in the core `servinder_subsites_is_block_visible()` API. The adapter only maps Divi module settings to that rule.
- Use the official Divi 5 module APIs and preserve graceful behavior when the core plugin or Divi 5 is unavailable.
- Run `npm ci && npm run build` in `visual-builder/` when changing its source; commit the generated production bundle.
- Before release, lint PHP, validate module JSON, inspect the production ZIP, record SHA-256, back up the prior plugin, install with WP-CLI, run the integration smoke, and verify all network blogs and health.
- Each coherent requested modification is committed and pushed, then released with `bin/release.sh`. Never install from an unreviewed working tree or create automatic push hooks.
- Keep the family-wide WordPress network map in `/Users/junio/Documents/IA_Central/docs/technical/servinder-wordpress-central.md` current after a network release.
