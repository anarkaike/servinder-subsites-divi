# Divi 5 integration notes

The adapter follows the official Divi 5 module pattern: a shared `module.json`, a Visual Builder registration bundle, and a PHP server renderer registered through `ModuleRegistration` and the module dependency tree. It loads the documented dependency interface from the active Divi theme path, without editing theme files.

Official references:

- [Create Divi 5 Module in 10 Minutes](https://dev.elegantthemes.com/docs/tutorials/module/beginner/create-simple-quick-module/)
- [Divi 5 extension examples](https://dev.elegantthemes.com/docs/extension-examples/)
- [Extending the Divi 5 Visual Builder](https://dev.elegantthemes.com/docs/tutorials/general/advanced/extending-d5-visual-builder/)
- [Divi 5 Select field API](https://dev.elegantthemes.com/api/js/divi-field-library/functions/Select/)
- [Divi 5 Checkboxes field API](https://dev.elegantthemes.com/api/js/divi-field-library/functions/Checkboxes/)

The adapter uses Divi's documented server/client module registration and field library. It does not modify Divi files and exits cleanly if the active theme does not provide the Divi 5 dependency interface.

## Production editor verification (2026-10-14)

The network has Divi 5.15.1 and adapter 0.1.0 active. The published `Sample Page` (blog 3, post 2) exposes **Use The Divi Builder** and therefore has builder support, but it must not be converted just to test this adapter. The temporary draft used for verification (post 30) had no Divi layout metadata; pressing the button converted it to a Divi placeholder, but **Edit With The Divi Builder** was disabled. That screen alone cannot verify the module picker.

Divi's own `et_builder_enabled_for_post()` checks whether the post type is supported or the post already uses Divi. Pages are supported by default. The placeholder's Edit button is rendered only when `et_pb_is_allowed('divi_builder_control')` is true. A second QA draft (post 32, author ID 1) confirmed via WP-CLI that `et_builder_enabled_for_post(32)`, `current_user_can('edit_post', 32)`, and `et_pb_is_allowed('divi_builder_control')` were true, and `_et_pb_use_builder` was `on`. In the authenticated browser, the placeholder still rendered **Edit With The Divi Builder** disabled. The built-in Gutenberg preview opened a blank browser tab; a manually generated preview nonce from WP-CLI was rejected and is not a valid browser-session test. This narrows the issue to editor/preview behavior but does not establish its cause. Inspect the actual authenticated response and Divi settings before changing permissions or adapter code.

The source and built Visual Builder bundle were confirmed served from the live plugin path. The adapter's registered asset dependencies and `divi.moduleLibrary.registerModuleStore.after` hook match the official Divi 5 module tutorial. Continue testing on a disposable draft with an authenticated administrator session; do not edit the published sample page. After the module picker is confirmed, test inserting **Conteúdo por subsite**, verify its fields/options, and check front-end visibility in an actual subsite context. Both disposable QA posts (IDs 30 and 32) were deleted after inspection.
