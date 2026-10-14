# Adapter API

## Divi module

Module identifier: `servinder-subsites-divi/conditional-content`.

The module stores:

- `targetIds.innerContent.desktop.value`: selected published subsite IDs.
- `visibilityMode.innerContent.desktop.value`: `include` or `exclude`.
- `content.innerContent.desktop.value`: rich content rendered by Divi.

The server renderer applies `servinder_subsites_is_block_visible()` with `mode` and `subsiteIds`. No active context, unpublished targets, or an invalid rule fail closed.

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
