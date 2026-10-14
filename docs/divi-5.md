# Divi 5 integration notes

The adapter follows the official Divi 5 module pattern: a shared `module.json`, a Visual Builder registration bundle, and a PHP server renderer registered through `ModuleRegistration` and the module dependency tree. It loads the documented dependency interface from the active Divi theme path, without editing theme files.

Official references:

- [Create Divi 5 Module in 10 Minutes](https://dev.elegantthemes.com/docs/tutorials/module/beginner/create-simple-quick-module/)
- [Divi 5 extension examples](https://dev.elegantthemes.com/docs/extension-examples/)
- [Extending the Divi 5 Visual Builder](https://dev.elegantthemes.com/docs/tutorials/general/advanced/extending-d5-visual-builder/)
- [Divi 5 Select field API](https://dev.elegantthemes.com/api/js/divi-field-library/functions/Select/)
- [Divi 5 Checkboxes field API](https://dev.elegantthemes.com/api/js/divi-field-library/functions/Checkboxes/)
- [Adding a custom Divi 5 dynamic content field](https://dev.elegantthemes.com/docs/tutorials/module/advanced/custom-dynamic-content-field/adding-custom-dynamic-content-field/)
- [Adding an image field to custom Divi dynamic content](https://dev.elegantthemes.com/docs/tutorials/module/advanced/custom-dynamic-content-field/adding-image-field-in-custom-dynamic-content-field/)

The adapter uses Divi's documented server/client module registration and field library. It does not modify Divi files and exits cleanly if the active theme does not provide the Divi 5 dependency interface.

## Production editor verification (2026-10-14)

The network runs Divi 5.15.1, with adapter 0.1.1 network-active on blogs 1–4. The published `Sample Page` (blog 3, post 2) was not modified. A disposable draft (post 34, blog 3) exposed a fatal when opening the Visual Builder:

```text
Class "Servinder\\Subsites\\Divi\\Divi5\\ConditionalContentModule" not found
```

The module PHP file had been included during `after_setup_theme`, but an early guard returned before declaring the class because Divi's `ModuleRegistration` class was not yet available at that point. The adapter bootstrap already checks the dependency interface; removing the premature `ModuleRegistration` guard lets the class be declared and its later registration hook run at the appropriate Divi lifecycle point. The smoke suite now asserts that the class is loaded. This fix was released as commit `eb661a6fcff2c10fb94ac3dee1fee2e5bf1f63ca`; release package SHA-256: `1610ebb036cb62766a41d12439edd0cad18201987f3ea21d8dfc9eeeb7b2b596`. Previous package backup: `/root/wp-releases/servinder-subsites-divi-20261014T034308Z/servinder-subsites-divi-before.tgz`.

The release smoke passed, the plugin remained network-active on all four blogs, the WordPress container was healthy, and `https://guiagaysaopaulo.servinder.com.br/` returned HTTP 200. In the authenticated blog 3 editor, draft 34 first returned 404 because its core visibility was set to `none` and the request had no subsite context. After resolving its visibility to Guiya (subsite ID 23) and opening the Divi builder URL with `servinder_subsite_id=23`, the Visual Builder loaded without a new fatal. The module library contained **Conteúdo por subsite**. Its controls rendered: checkboxes for available subsites, a **Mostrar Em** selector with “Os subsites selecionados” and “Todos, exceto os selecionados”, a rich-text **Conteúdo Condicional** editor, and the default placeholder in the canvas.

This confirms Visual Builder loading, module discovery, and field rendering. On 2026-10-14, a new private page (ID 38) was created through the WordPress editor, limited to subsite 23 (**Guiya Editora**), and given a real **Conteúdo por subsite** Divi module with a unique text marker. Divi saved the module in native `post_content` as `servinder-subsites-divi/conditional-content`, with `targetIds` set to `23`, mode value `0` (the adapter maps all non-`exclude` values to include), and the unique rich text. `_et_pb_use_builder=on` and `_et_pb_use_divi_5=on` were also present.

Using the saved page's actual `post_content`, WordPress `do_blocks()` rendered the marker for Guiya (subsite 23) and omitted it for Guia Gay Floripa (subsite 22). The callback output included the expected Divi module wrapper. However, the authenticated browser preview request returned HTTP 200 but displayed the page shell without the module text. Therefore saved attributes and the server block renderer are verified, but end-to-end public page output remains unresolved. The page was changed to private during diagnosis and then permanently deleted; no test page remains. The public `Sample Page` (ID 2) remains untouched. Further validation should investigate why the real front-end request differs from the direct `do_blocks()` render before claiming the Divi module is complete. The earlier drafts 34 and 36 were also disposable and are absent. The initial 03:41 fatal remains in historical container logs; subsequent builder loading had no new fatal, and the release smoke, healthy container, and Guia HTTP 200 were confirmed.

The adapter's module registration follows the documented Divi 5 server/client pattern and uses the core `servinder_subsites_is_block_visible()` visibility decision. It does not edit Divi files. Divi 4 modules, additional per-subsite dynamic fields, page assignment, and Theme Builder templates remain unimplemented.

## Active-subsite dynamic text source (2026-10-14)

Version 0.3.0 registered **Nome do subsite atual** through Divi 5's `DynamicContentOptionBase` and `DynamicContentOptionInterface`. Version 0.4.0 adds four dynamic color values and image sources for the subsite logo and background. The shared resolver uses the core's active context; the smoke covers registration, color resolution, missing media, extension filtering, and empty output without a context. This confirms the PHP integration contract; using the sources in authored Visual Builder fields and verifying public output still requires browser end-to-end checks.

## Adapter 0.2.0 release (2026-10-14)

Version 0.2.0 adds subsite visibility to Divi 5 Loop-capable modules such as Group and Group Carousel. The Visual Builder adds all/include/exclude controls; the server scopes post type loop queries to the active subsite and suppresses the module when its saved rule does not match. Existing Loop modules default to all subsites. Divi numeric select values are normalized to their intended modes. The production smoke passed these server checks on release commit `644a0e9b01850d1cd2f440d88c130c3804979457`; package SHA-256 `dda54ba4ede6b291ebf2a192314bfb389e58bbe3590e9429603b62ebc26919f7`. The plugin is active-network on blogs 1–4; the container was healthy and the Guia route returned HTTP 200. Previous plugin backup: `/root/wp-releases/servinder-subsites-divi-20261014T041240Z/servinder-subsites-divi-before.tgz`.

This release verifies server-side visibility logic and query scoping. A Loop module was not authored and saved in the Visual Builder during this release, so its editor controls and rendered posts still need a browser end-to-end check. The separately tested conditional-content module's saved block was rendered correctly through `do_blocks()` for subsite 23 and suppressed for subsite 22, but its authenticated browser page preview returned the page shell without the module content. Full request rendering remains under investigation.

## Loop visibility field compatibility (2026-10-14)

Version 0.5.0 aligns dynamically injected Loop visibility controls with Divi 5's `GroupItemProps` field contract: defaults are supplied through `defaultAttr`, and the fields use only supported hover, responsive, and sticky feature flags. The select control no longer receives render selectors, empty output attributes, a children sanitizer, or a content preset. Integrated smoke assertions guard this schema. The production Visual Builder still needs a clean save/reopen check for an existing Loop module to confirm selection persistence end to end.
