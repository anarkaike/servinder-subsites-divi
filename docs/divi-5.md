# Divi 5 integration notes

The adapter follows the official Divi 5 module pattern: a shared `module.json`, a Visual Builder registration bundle, and a PHP server renderer registered through `ModuleRegistration` and the module dependency tree. It loads the documented dependency interface from the active Divi theme path, without editing theme files.

Official references:

- [Create Divi 5 Module in 10 Minutes](https://dev.elegantthemes.com/docs/tutorials/module/beginner/create-simple-quick-module/)
- [Divi 5 extension examples](https://dev.elegantthemes.com/docs/extension-examples/)
- [Extending the Divi 5 Visual Builder](https://dev.elegantthemes.com/docs/tutorials/general/advanced/extending-d5-visual-builder/)
- [Divi 5 Select field API](https://dev.elegantthemes.com/api/js/divi-field-library/functions/Select/)
- [Divi 5 Checkboxes field API](https://dev.elegantthemes.com/api/js/divi-field-library/functions/Checkboxes/)

The adapter uses Divi's documented server/client module registration and field library. It does not modify Divi files and exits cleanly if the active theme does not provide the Divi 5 dependency interface.
