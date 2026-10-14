# Changelog

## 0.2.0

- Add per-subsite visibility controls to Divi 5 modules that support Loop, including Group and Group Carousel.
- Hide loop modules outside their selected subsite scope and scope loop queries to the active subsite so Divi's existing post type, taxonomy, term, and item filters select the content shown.
- Keep existing saved modules visible everywhere by default.
- Normalize Divi's numeric select indexes so include/exclude modes match the values actually saved by the Visual Builder.

## 0.1.1

- Load the Divi 5 server module when its dependency interface is available, without requiring `ModuleRegistration` to be autoloaded during `after_setup_theme`.
- Add a smoke assertion that the server module class is loaded for Visual Builder requests.

## 0.1.0

- Add an optional Divi 5 Visual Builder module for include/exclude conditional content by published subsite.
- Use the core visibility service and document extension filters.
- Add network release and integrated smoke workflows.
