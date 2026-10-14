# Architecture

The adapter stays separate from `servinder-subsites` so the core can run without Divi. The WordPress plugin bootstrap checks for the core API and Divi 5 after theme setup, then loads the Divi server module. The module registers with Divi's module dependency tree and uses the Divi 5 `module.json` contract for Visual Builder controls.

The target selector is populated from published Servinder Subsites records in 100-item pages. The editor stores selected post IDs in the Divi module attributes. PHP rendering converts these attributes to the same `mode` / `subsiteIds` rule used by native Gutenberg block visibility and delegates the decision to `servinder_subsites_is_block_visible()`.

Content is evaluated server-side. Without an active published subsite context, the core API returns false and the conditional module renders nothing. User-authored shortcodes run only after the rule allows the content. The module never changes a post's global status or subsite records.

The production WP-CLI smoke invokes the adapter's conditional rendering method against an active published subsite. It checks include and exclude outcomes, confirms a shortcode runs for visible content, and confirms it is not evaluated when content is hidden. This exercises the server rule mapping without relying on Visual Builder UI automation; it does not prove that a saved Divi page serializes module attributes correctly or that the module wrapper renders on a public page.

## Extension points

`servinder_subsites_divi_visibility_rule` can adjust a proposed rule; the core API validates selected subsite IDs. `servinder_subsites_divi_is_visible` can further constrain the visibility decision. `servinder_subsites_divi_rendered_content` can adjust rendered output after visibility has passed. These adapter filters do not bypass the core rule unless a site integrator explicitly changes the final decision.

## Current limits

- Divi 5 is supported. Divi 4 does not receive a native module from this release.
- Templates, dynamic fields, and page assignment per subsite are not implemented.
- The selector lists published records in the current WordPress blog; it does not merge records across Multisite blogs.
