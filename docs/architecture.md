# Architecture

The adapter stays separate from `servinder-subsites` so the core can run without Divi. The WordPress plugin bootstrap checks for the core API and Divi 5 after theme setup, then loads the Divi server module. The module registers with Divi's module dependency tree and uses the Divi 5 `module.json` contract for Visual Builder controls.

The target selector is populated from published Servinder Subsites records in 100-item pages. The editor stores selected post IDs in the Divi module attributes. PHP rendering converts these attributes to the same `mode` / `subsiteIds` rule used by native Gutenberg block visibility and delegates the decision to `servinder_subsites_is_block_visible()`.

Content is evaluated server-side. Without an active published subsite context, the core API returns false and the conditional module renders nothing. User-authored shortcodes run only after the rule allows the content. The module never changes a post's global status or subsite records.

The production WP-CLI smoke invokes the adapter's conditional rendering method against an active published subsite. It checks include and exclude outcomes, confirms a shortcode runs for visible content, and confirms it is not evaluated when content is hidden. This exercises the server rule mapping without relying on Visual Builder UI automation; it does not prove that a saved Divi page serializes module attributes correctly or that the module wrapper renders on a public page.

The adapter also registers an official Divi 5 dynamic content option for the active subsite title. It reads only `servinder_subsites_current()` from the core, permits drafts only during the core's authorized preview context, and returns an empty value when no valid context is active. Its extension filter runs before HTML escaping. This is a text source; appearance and media values remain future adapter work.

Divi's select fields can serialize options as numeric indexes. The adapter maps the conditional-content indexes `0/1` to `include/exclude` and Loop indexes `0/1/2` to `all/include/exclude`; the production smoke covers these mappings, loop wrapper decisions, and query scoping.

An authenticated disposable Divi page was saved and inspected on 2026-10-14. Its serialized `servinder-subsites-divi/conditional-content` block rendered with `do_blocks()` in the selected subsite and was omitted in a different subsite. The same page's browser preview returned the page shell without the module output, so a full front-end request still needs investigation.

## Extension points

`servinder_subsites_divi_visibility_rule` can adjust a proposed rule; the core API validates selected subsite IDs. `servinder_subsites_divi_is_visible` can further constrain the visibility decision. `servinder_subsites_divi_rendered_content` can adjust rendered output after visibility has passed. These adapter filters do not bypass the core rule unless a site integrator explicitly changes the final decision.

## Current limits

- Divi 5 is supported. Divi 4 does not receive a native module from this release.
- Theme Builder templates and page assignment per subsite are not implemented.
- Dynamic content currently covers only the active subsite name in text fields; color, logo, image, audio/video, and route sources are not implemented.
- The selector lists published records in the current WordPress blog; it does not merge records across Multisite blogs.
