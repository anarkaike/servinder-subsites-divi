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

The network runs Divi 5.15.1, with adapter 0.1.1 network-active on blogs 1–4. The published `Sample Page` (blog 3, post 2) was not modified. A disposable draft (post 34, blog 3) exposed a fatal when opening the Visual Builder:

```text
Class "Servinder\\Subsites\\Divi\\Divi5\\ConditionalContentModule" not found
```

The module PHP file had been included during `after_setup_theme`, but an early guard returned before declaring the class because Divi's `ModuleRegistration` class was not yet available at that point. The adapter bootstrap already checks the dependency interface; removing the premature `ModuleRegistration` guard lets the class be declared and its later registration hook run at the appropriate Divi lifecycle point. The smoke suite now asserts that the class is loaded. This fix was released as commit `eb661a6fcff2c10fb94ac3dee1fee2e5bf1f63ca`; release package SHA-256: `1610ebb036cb62766a41d12439edd0cad18201987f3ea21d8dfc9eeeb7b2b596`. Previous package backup: `/root/wp-releases/servinder-subsites-divi-20261014T034308Z/servinder-subsites-divi-before.tgz`.

The release smoke passed, the plugin remained network-active on all four blogs, the WordPress container was healthy, and `https://guiagaysaopaulo.servinder.com.br/` returned HTTP 200. These checks establish deployment and public-site health, not yet successful insertion/rendering of the module. Reopen the authenticated editor on disposable draft 34, confirm the Visual Builder loads without a new fatal, locate **Conteúdo por subsite** in the module library, inspect its controls, and verify conditional visibility in a real subsite context. Remove the draft after that verification. Do not use the published sample page as a test target.

The adapter's module registration follows the documented Divi 5 server/client pattern and uses the core `servinder_subsites_is_block_visible()` visibility decision. It does not edit Divi files. Divi 4 modules, page assignment, dynamic per-subsite fields, and Theme Builder templates remain unimplemented.
