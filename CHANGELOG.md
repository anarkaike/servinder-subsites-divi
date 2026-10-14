# Changelog

## 0.1.1

- Load the Divi 5 server module when its dependency interface is available, without requiring `ModuleRegistration` to be autoloaded during `after_setup_theme`.
- Add a smoke assertion that the server module class is loaded for Visual Builder requests.

## 0.1.0

- Add an optional Divi 5 Visual Builder module for include/exclude conditional content by published subsite.
- Use the core visibility service and document extension filters.
- Add network release and integrated smoke workflows.
