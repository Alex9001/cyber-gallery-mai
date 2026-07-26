# Changelog

## 0.6.0

- Reworked Fade and every animated Slider preset into an in-place compositor transition for control-driven and autoplay navigation, eliminating the underlying horizontal slide and background flash.
- Retained native direct touch, pointer, and trackpad scrolling, with instant reduced-motion behavior.
- Sharpened Slider circle navigation by replacing fractional parent scaling and blurred shadows with integer-sized inner marks.
- Added independent Primary and Secondary Slider navigation selectors so arrows, circles, and the slide counter can be combined; duplicate choices are prevented in the editor and normalized at render time.
- Preserved the legacy `arrows-dots` setting by mapping it to Primary Arrows plus Secondary Circles.
- Added `slider_autoplay`, `slider_autoplay_interval`, and `slider_pause_on_hover`, plus a visible Pause/Start control and lifecycle handling for visibility, viewport, focus, and manual navigation.

## 0.5.1

- Completed a source-level Mai Engine integration audit and documented the deliberate boundary between framework-owned behavior and gallery-specific code.
- Replaced the local ACF palette/custom-color resolver with Mai Engine's color migration and formatting hooks for all seven Slider color fields.
- Delegated column defaults, instance indexes, CSS unit formatting, the configured XL breakpoint, control foundations, drop shadows, icons, and Slider typography to Mai Engine.
- Made the default contained Slider width follow the site's configured Mai XL breakpoint, typically 1200px.
- Raised the minimum Mai Engine version to 2.40.0 so delegated responsive-column fractions include the current corruption fix.
- Retained Cyber's integer responsive-column mirror because Mai's fractional column variables cannot drive CSS `column-count` or the JavaScript packing algorithms.

## 0.5.0

- Added the `slider_transition` argument with `native`, `fade`, `zoom-in`, `zoom-out`, `rise`, `soft-focus`, `reveal`, and `cinematic` choices.
- Added the `slider_transition_speed` argument with `fast`, `normal`, and `slow` choices; motion effects honor reduced-motion preferences.
- Added the `slider_navigation_hover` argument with `color`, `grow`, `nudge`, `glow`, `fill`, and `ring` choices while retaining stable navigation hit targets.
- Added Mai Engine palette and custom-color support for `slider_navigation_main`, `slider_navigation_border`, `slider_navigation_inner`, `slider_navigation_main_hover`, `slider_navigation_border_hover`, and `slider_navigation_inner_hover`.
- Added `slider_overlay_style` with `none`, `solid`, `gradient-bottom`, `gradient-top`, `gradient-left`, `gradient-right`, and `vignette` choices.
- Added Mai palette/custom `slider_overlay_color` and `slider_overlay_opacity` from 0 through 100, defaulting to 35. The user-selected image overlay remains separate from the content-legibility scrim.
- Removed browser up/down spinner controls from Slider width and height inputs while retaining their numeric validation.

## 0.4.1

- Added the `slider_width_mode` argument with `full`, `contained`, and `inset` choices. Full is the default and fills the available block or parent canvas, Contained uses the Slider maximum width and preserves the previous centered presentation, and Inset fills the canvas with responsive side gutters.
- Made button-group choices equal width throughout the block editor for a more consistent control layout.
- Stacked maximum width, desktop height, and mobile height fields one per row in the compact inspector while retaining their single-row layout in the expanded editor.
- Preserved existing block content and settings; no content migration is required.

## 0.4.0

- Raised the minimum requirements to WordPress 6.5 and PHP 8.3 while retaining compatibility through WordPress 7.0.
- Added compatibility-safe PHP typing and static-analysis tooling without changing public function inputs or runtime behavior, filters, arguments, or item data.
- Declared Block API version 3 explicitly for predictable editor iframe behavior.
- Added pinned, reproducible CSS and JavaScript builds with continuous integration validation.
- Preserved all saved block content and gallery settings; no content migration is required.

## 0.3.1

- Added per-slide Top, Center, and Bottom vertical content alignment, defaulting to Bottom.
- Added position-aware contrast scrims for Top and Center slide content.
- Added the `align_vertical` Slider row value and normalized `slide_align_vertical` item key to the PHP API.

## 0.3.0

- Replaced Slider's image-only source with canonical slide rows containing a required image plus optional title, plain-text subtext, and Start/Center/End horizontal alignment.
- Added up to two CTA links per slide using Mai Engine's Primary, Secondary, Outline, and Link Button styles.
- Enabled ACF block validation and required every CTA to have both a URL and visible button label.
- Added responsive overlay typography, a contrast scrim, navigation-safe spacing, mobile wrapping, long-copy fallback, and print styling.
- Made Slider images non-interactive and suppressed irrelevant click-action, caption, hover, column, gallery-alignment, and gap controls.
- Added titled slide, circle, and live-region labels plus focus handoff when a swipe deactivates a focused CTA.
- Raised Slider minimum heights to 320px desktop and 280px mobile.
- Added the `slides` PHP API argument and the `mai_cyber_gallery_button_style_choices` and `mai_cyber_gallery_button_style_classes` filters.

## 0.2.1

- Added Top and Bottom placement for Slider circle navigation when controls overlay the image.
- Hid column, alignment, and gap controls for Slider while retaining separate block margin controls.
- Added Square and Rounded image-corner choices; Circle Tiles remain circular.

## 0.2.0

- Added Tiled Mosaic, a ratio-aware layout that combines mixed full-height and stacked tiles in source order.
- Added Slider, bringing the gallery to nine layouts.
- Improved Masonry with shortest-column placement to remove avoidable empty spaces while retaining its CSS multi-column fallback.
- Added Slider navigation modes for `arrows-dots`, `arrows`, `dots`, `counter`, and `none`.
- Added `overlay` and `below` Slider navigation placement.
- Added `circle`, `square`, `minimal`, and `edge` arrow designs shared by Slider and lightbox navigation.
- Added Slider maximum width, desktop height, mobile height, and `cover`/`contain` image-fit controls.
- Clarified that `image_size` selects the WordPress source dimensions and image quality independently of Slider display dimensions.
- Prevented Mai theme button hover, focus, and active transforms from displacing lightbox navigation controls.
- Added the `mai_cyber_gallery_arrow_styles` and `mai_cyber_gallery_slider_navigation_choices` filters.
- Added the `lightbox_arrow_style`, `slider_navigation`, `slider_navigation_position`, `slider_arrow_style`, `slider_width`, `slider_height`, `slider_height_mobile`, and `slider_image_fit` PHP API arguments.

## 0.1.1

- Prevented avoidable single-image Justified rows by balancing leftover items into the preceding row.
- Added independent starting-side and alternating controls for Editorial Mosaic feature tiles.

## 0.1.0

- Added the `acf/mai-cyber-gallery` dynamic ACF block.
- Added Mai Grid, Masonry Columns, Justified Rows, Editorial Mosaic, Square Tiles, Circle Tiles, and Filmstrip layouts.
- Reused Mai Engine responsive column, image size, spacing, margin, sanitization, and Genesis markup APIs.
- Added no-link, lightbox, media-file, attachment-page, and custom-link click actions.
- Added below, overlay, and hidden caption options.
- Added zoom, lift, fade, and disabled hover effects with reduced-motion support.
- Added a native dialog lightbox with focus restoration and keyboard navigation.
- Added responsive, native-scroll filmstrip controls without autoplay.
- Added public PHP helpers and filterable layout/item APIs.
- Added strict coexistence namespacing for the original Mai Galleries plugin.
