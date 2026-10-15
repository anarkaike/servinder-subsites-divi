# Changelog

## 0.6.0

- Add per-subsite Divi Theme Builder template selection for subsite home requests.
- Reuse the selected template's configured header, body, and footer layouts without changing other pages.


## 0.5.0

- Align the Loop visibility fields with Divi 5's field contract, including explicit field defaults and supported feature flags.
- Remove unrelated render selectors and preset metadata from the visibility controls.
- Extend the integrated smoke checks to guard the Divi select field definition.

## 0.4.0

- Expose per-subsite primary, accent, surface, and text colors as Divi dynamic color sources.
- Expose per-subsite logo and background image as Divi dynamic image sources.
- Reuse the shared validated active-subsite context and normalized core appearance API.
- Extend smoke coverage for dynamic option registration, color resolution, missing media, and context isolation.

## 0.3.0

- Register the active subsite name as an official Divi 5 dynamic text source.
- Resolve the value from core subsite context and allow draft contexts only in authorized previews.
- Add the `servinder_subsites_divi_dynamic_content_value` extension filter and smoke coverage.

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
