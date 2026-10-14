# Adapter API

## Divi module

Module identifier: `servinder-subsites-divi/conditional-content`.

The module stores:

- `targetIds.innerContent.desktop.value`: selected published subsite IDs.
- `visibilityMode.innerContent.desktop.value`: `include` or `exclude`. Divi may serialize these select choices as indexes `0` (include) and `1` (exclude); the adapter normalizes both the labels and serialized indexes.
- `content.innerContent.desktop.value`: rich content rendered by Divi.

## Divi Loop visibility

Loop-capable Divi 5 modules (including `divi/group` and `divi/group-carousel`) receive two attributes:

- `servinderSubsiteTargets.innerContent.desktop.value`: selected published subsite IDs.
- `servinderSubsiteMode.innerContent.desktop.value`: `all`, `include`, or `exclude`.

The default is `all`, preserving existing layouts. Divi may serialize these select options as indexes `0` (`all`), `1` (`include`), and `2` (`exclude`); the adapter normalizes them before evaluating visibility. The front-end wrapper is hidden when the active subsite fails the configured rule. The Visual Builder sends the same rule with its loop query; the adapter returns no results when it does not match and supplies the active subsite context to the core query scoping. Divi's native Loop controls continue to select post types, terms/taxonomies, users, and specific items.

The server renderer applies `servinder_subsites_is_block_visible()` with `mode` and `subsiteIds`. No active context, unpublished targets, or an invalid rule fail closed.

## Dynamic content

The Divi 5 text source **Nome do subsite atual** resolves the title of the active published subsite. It also resolves an unpublished subsite only in a core-authorized preview context. Requests without a valid active context return an empty value. Other dynamic fields such as colors, logo, background image, and route are not yet provided by this adapter.

## Hooks

### `servinder_subsites_divi_visibility_rule`

Filter the proposed rule before evaluating it.

- Arguments: rule array (`mode`, `subsiteIds`), `WP_Block` or `null`.
- Return: rule array. Non-array results hide the content.

### `servinder_subsites_divi_is_visible`

Filter the final visibility boolean after the core rule is evaluated.

- Arguments: boolean, validated rule array, `WP_Block` or `null`.
- Return: boolean. Return `false` to suppress output.

### `servinder_subsites_divi_rendered_content`

Filter content after the visibility rule passes and shortcodes have rendered.

- Arguments: rendered HTML string, validated rule array, `WP_Block` or `null`.
- Return: HTML string.

These hooks do not change the core API, global post status, or subsite visibility metadata.

### `servinder_subsites_divi_dynamic_content_value`

Filter the text value emitted by the active-subsite dynamic content option.

- Arguments: string value, option name (`servinder_subsite_name`), active `WP_Post`, and Divi data arguments.
- Return: scalar value. Non-scalar results produce an empty string; the final text is HTML-escaped by the adapter.
