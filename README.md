# Servinder Subsites — Divi

Optional Divi 5 adapter for [Servinder Subsites](https://github.com/anarkaike/servinder-subsites).

The **Conteúdo por subsite** module wraps rich content in a native Divi 5 Visual Builder module. Editors choose published subsites by name and choose whether to include or exclude that selection. Front-end visibility delegates to `servinder_subsites_is_block_visible()`, so the Divi module follows the same active context, validation, and extension filters as Gutenberg blocks.

Modules with Divi Loop support, including **Grupo** and **Carrossel de Grupos**, also receive **Visibilidade por subsite** controls. Existing loop settings continue to select the post types, taxonomies, terms, and items to display. Their query results are scoped to content visible and available in the active subsite, and the module can be limited to all, selected, or all except selected subsites. Existing modules default to all subsites.

## Requirements

- WordPress 6.5+
- PHP 8.1+
- Servinder Subsites core plugin
- Divi 5

The adapter has no runtime dependency on WooCommerce, ACF, or other extensions. It loads after the active theme is available, then registers the module through Divi 5’s module dependency tree. If Divi 5 or the core plugin is unavailable, it displays an administrator notice and does not load Divi integration code.

## Use

1. Open a page in the Divi 5 Visual Builder.
2. Add **Conteúdo por subsite**.
3. Select one or more published subsites, choose include or exclude, and add the conditional content.
4. Save and inspect each public subsite route.

The module hides its output when no active subsite context exists. Shortcodes inside the module content are processed only after the visibility rule allows that content.

## Extension hooks

- `servinder_subsites_divi_visibility_rule` — receives the proposed core rule and `WP_Block`; return a validated rule array.
- `servinder_subsites_divi_is_visible` — receives the core decision, rule, and block; return the final visibility boolean.
- `servinder_subsites_divi_rendered_content` — receives rendered content, rule, and block; return the final output.

For core visibility behavior and API contracts, see the [core API reference](https://github.com/anarkaike/servinder-subsites/blob/main/docs/api.md).

## Development

```sh
cd visual-builder
npm ci
npm run build
```

The committed `visual-builder/build/servinder-subsites-divi.js` is the production asset. Run `bin/release.sh` from a clean `main` checkout to package, back up, install network-wide, verify the shared visibility behavior, confirm network activation on every blog, and check WordPress health and the Guia public route. The CLI smoke checks server-side include/exclude rendering, loop-module visibility and query scoping, and that hidden content shortcodes do not execute. Visual Builder registration, saved module attributes, and public output from an authored Divi page still require verification in Divi itself.

## Scope

This release provides a conditional content module for Divi 5. Divi 4 modules, Divi Theme Builder template assignment, per-subsite dynamic fields, and per-subsite page assignment remain planned.
