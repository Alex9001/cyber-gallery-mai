=== Mai Cyber Gallery ===
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.3
Requires Plugins: mai-engine
Stable tag: 0.7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Nine responsive masonry, tiled mosaic, filmstrip, and content-slider layouts powered by Mai Engine.

== Description ==

Mai Cyber Gallery is a deliberate Mai Engine add-on. It adds a dynamic ACF gallery block with nine layouts:

* Mai Grid
* Masonry Columns
* Justified Rows
* Tiled Mosaic
* Editorial Mosaic
* Square Tiles
* Circle Tiles
* Filmstrip
* Slider

The block reuses Mai Engine's ACF Pro integration, responsive columns, image sizes, gap and margin fields, color formatting, configured breakpoints, controls, utility classes, Genesis markup API, and design tokens. Mai Engine 2.40.0 or later is required.

For non-Slider layouts, click actions include a keyboard-accessible lightbox, media file, attachment page, custom per-image links, or no link. Captions can appear below images, as overlays, or be hidden. Lightbox arrows can use Frosted Circle, Rounded Square, Minimal Chevron, or Edge Tab designs.

Editorial Mosaic can start its feature image on the left or right and optionally alternate sides every five images.

Masonry places each image in the current shortest column to reduce unnecessary gaps. Tiled Mosaic uses source image ratios to create mixed full-height and stacked tiles while preserving image order, with the responsive column setting capped at four visual groups.

Slider uses structured rows with a required image, optional title, plain-text subtext, Start/Center/End horizontal alignment, Top/Center/Bottom vertical alignment, and up to two CTA links. Vertical alignment defaults to Bottom. CTA styles reuse Mai Engine's Primary, Secondary, Outline, and Link Button designs. Slider images are non-interactive so CTAs remain the only destinations.

Slider has independent Primary and Secondary Navigation selectors, so arrows, circles, and the slide counter can be combined or disabled. Duplicate choices render only once. Navigation can overlay the image or appear below it, and overlay circles can be placed at the top or bottom. Slider settings include arrow design, width mode, maximum width, separate desktop and mobile heights, and crop-to-fill or show-full-image fitting. Full width is the default and fills the available block or parent canvas. Contained width uses the maximum width and preserves the previous centered presentation. Inset width fills the canvas with responsive side gutters. Irrelevant image-click, caption, column, gallery-alignment, and gap controls are hidden for Slider.

Switching layouts preserves ordinary Images, Linked Images, structured Slides, and dormant layout settings. An unpopulated destination layout falls back to retained attachments, so changing to or from Slider no longer empties the preview, while returning to Slider restores its titles, copy, CTA buttons, and alignment.

Slider transitions include Smooth Slide (`native`), Fade, Zoom In, Zoom Out, Rise, Soft Focus, Reveal, and Cinematic. Smooth Slide keeps native horizontal movement. Controls and autoplay render the other presets as in-place crossfades with their selected entrance treatment; direct touch and trackpad gestures remain native. Navigation hover effects include Color Shift, Grow, Nudge, Glow, Fill, and Ring while retaining stable hit targets. Motion effects honor the visitor's reduced-motion preference.

Optional Slider autoplay has a configurable 3–30 second interval, mouse-over pause, and an accessible Pause/Start control whenever motion is allowed.

Slider navigation background, border, and inner colors have independent normal and hover settings. Each reuses the Mai Engine palette and supports a custom color.

Image overlays now work across every layout and support None, Solid, four directional gradients, and Vignette styles with Mai palette/custom color and 0–100 opacity controls. In Slider the image overlay remains separate from the content-legibility scrim behind slide copy.

Hover treatments also work across every layout. Desaturate removes a configurable 0–100 percent of color at rest and restores full color on mouse-over or keyboard focus. Slider defaults to None so existing Sliders remain motionless until a treatment is selected.

Filmstrip supports optional timed group movement with an accessible Pause/Start control whenever the strip overflows and motion is allowed, optional mouse-over pause, and arrows that can be disabled, overlaid, or aligned above or below the images. Controls hide when every item fits. Filmstrip arrow design, navigation hover effects, and Mai palette/custom colors are shared with Slider.

Filmstrip also includes Natural, Uniform Crop, and Logo / Contain image modes. Logo mode centers artwork in consistent responsive cells with shared inner padding and automatically replaces a selected hard-cropped preset with the uncropped original. Per-image scale overrides compensate for unusual transparent whitespace or optically small logos without changing the source files; values above 100 percent intentionally zoom within the cell and can clip if overdone.

Button-group choices use equal widths throughout the gallery controls. In the compact block inspector, maximum width and the desktop and mobile height fields stack one per row; the expanded editor keeps them together in one row. Browser up/down spinner controls are removed from those Slider dimension inputs while their numeric validation remains.

The Image Quality / Source Size setting chooses the WordPress image source. A larger source can look sharper at large display dimensions, but uses more bandwidth.

Image Corners can be Square or Rounded. Circle Tiles always remain circular.

== Upgrade Notice ==

= 0.7.0 =

Preserves images and settings when layouts change; adds shared overlays and desaturation, Filmstrip autoplay and positioned optional arrows, and standardized no-crop logo rows with per-image sizing.

= 0.6.0 =

Adds true in-place Slider transitions, two combinable navigation selectors, sharper circles, and accessible autoplay with timing and mouse-over pause controls. Existing Arrows + Circles settings are preserved.

= 0.5.1 =

Completes the Mai Engine integration audit, delegates more shared behavior to Engine, follows the configured XL breakpoint, and requires Mai Engine 2.40.0 or later.

= 0.5.0 =

Adds Slider transition and speed choices, navigation hover and color controls, configurable image overlays, and cleaner Slider dimension inputs.

= 0.4.1 =

Adds Full, Contained, and Inset Slider width modes plus more consistent editor controls. Existing content and settings require no migration; choose Contained to retain the previous centered maximum-width presentation.

= 0.4.0 =

Requires WordPress 6.5 or later and PHP 8.3 or later. Saved galleries and public API behavior are unchanged; no content migration is required.

= 0.3.1 =

Slider content can now be positioned at the Top, Center, or Bottom of each slide. Existing slides remain Bottom-aligned by default.

= 0.3.0 =

Slider now uses canonical Slides rows with no legacy Images fallback. Re-add images from older Slider blocks through the new Slides repeater.

== Installation ==

1. Install and activate Genesis, a Mai child theme, and Mai Engine 2.40.0 or later.
2. Upload the `mai-cyber-gallery` directory to `/wp-content/plugins/`.
3. Activate Mai Cyber Gallery.
4. Add the Mai Cyber Gallery block in the block editor.

== Frequently Asked Questions ==

= Does this replace Mai Engine? =

No. Mai Engine is required and intentionally supplies the plugin's core block, image, responsive-layout, spacing, and theme infrastructure.

= Can Mai Galleries remain active? =

Yes. Cyber-owned block, field, class, function, asset, filter, and markup names are unique. Shared responsive controls deliberately clone Mai Engine's fields.

= Which image size should I use for masonry? =

Use an uncropped size such as Large so portrait and landscape images retain their natural proportions.

= How do I control Slider image quality? =

Set Image Quality / Source Size to a WordPress image size at least as large as the Slider's display dimensions. Width and height control the display box; the image size controls the source pixels and bandwidth.

= How do I control Slider width? =

Full fills the available block or parent canvas and is the default. Contained respects Maximum Slider Width for the previous centered look. Inset fills the canvas with responsive side gutters.

= Does the filmstrip autoplay? =

Optionally. It always retains native touch, mouse, trackpad, keyboard scrolling, and scroll snap. Timed movement advances visible groups and includes Pause/Start whenever movement is available, plus reduced-motion, visibility, focus, and manual-interaction safeguards.

= Does the Slider autoplay? =

Autoplay is optional. Set the time between slides from 3 through 30 seconds and optionally pause while the pointer is over the Slider. A Pause/Start control remains available whenever motion is allowed; reduced-motion preferences disable autoplay.

== Changelog ==

= 0.7.0 =

* Preserved Images, Linked Images, Slides, and dormant settings across layout changes instead of dropping hidden ACF fields from block data.
* Added non-destructive retained-image fallbacks when switching to an unpopulated Slider or gallery layout.
* Shared image overlays and hover treatments across every layout.
* Added configurable desaturation until mouse-over or keyboard focus.
* Preserved the motionless default for existing Sliders while making hover treatments opt-in.
* Added Filmstrip timed movement, optional arrows, overlay and aligned above/below control positions, shared arrow designs, navigation hover effects, and colors.
* Added Uniform Crop and Logo / Contain Filmstrip modes with responsive heights, padding, and per-logo scale overrides.
* Corrected Slider Edge Tab placement and separated the previous/next hit targets.

= 0.6.0 =

* Changed Fade and the animated Slider presets to true in-place crossfades for control-driven and autoplay navigation instead of combining them with horizontal movement.
* Kept direct touch, pointer, and trackpad gestures native while preserving reduced-motion behavior.
* Sharpened Slider circle navigation by removing fractional scaling and blurred shadows.
* Replaced the combined navigation selector with independent Primary and Secondary selectors; duplicate controls are prevented in the editor and rendered only once.
* Preserved legacy Arrows + Circles settings through runtime mapping.
* Added optional autoplay, a 3–30 second time-between-slides setting, mouse-over pause, and an accessible Pause/Start control.

= 0.5.1 =

* Replaced the local ACF palette/custom-color resolver with Mai Engine's color migration and formatting callbacks.
* Delegated column defaults, indexes, units, configured XL width, button foundations, shadows, icons, and Slider typography to Mai Engine.
* Raised the minimum Mai Engine version to 2.40.0 for its current responsive-column fraction fix.
* Documented why gallery algorithms and integer responsive-column values remain plugin-owned.

= 0.5.0 =

* Added `slider_transition` choices: `native`, `fade`, `zoom-in`, `zoom-out`, `rise`, `soft-focus`, `reveal`, and `cinematic`.
* Added `slider_transition_speed` choices: `fast`, `normal`, and `slow`; motion effects honor reduced-motion preferences.
* Added `slider_navigation_hover` choices: `color`, `grow`, `nudge`, `glow`, `fill`, and `ring`, with stable navigation hit targets.
* Added Mai palette/custom color settings for `slider_navigation_main`, `slider_navigation_border`, `slider_navigation_inner`, `slider_navigation_main_hover`, `slider_navigation_border_hover`, and `slider_navigation_inner_hover`.
* Added `slider_overlay_style` choices: `none`, `solid`, `gradient-bottom`, `gradient-top`, `gradient-left`, `gradient-right`, and `vignette`.
* Added Mai palette/custom `slider_overlay_color` plus `slider_overlay_opacity` from 0 through 100, defaulting to 35. The image overlay remains separate from the content-legibility scrim.
* Removed browser up/down spinner controls from Slider maximum-width and desktop/mobile-height inputs without removing their numeric validation.

= 0.4.1 =

* Added the `slider_width_mode` setting and PHP API argument with Full, Contained, and Inset choices.
* Made Full the default width mode so the Slider fills its available block or parent canvas.
* Preserved the previous centered maximum-width presentation as Contained and added an Inset presentation with responsive side gutters.
* Made button-group choices equal width throughout the block editor.
* Stacked maximum width, desktop height, and mobile height fields in the compact inspector while retaining their single-row expanded-editor layout.
* Preserved existing block content and settings; no content migration is required.

= 0.4.0 =

* Raised the minimum requirements to WordPress 6.5 and PHP 8.3 while retaining compatibility through WordPress 7.0.
* Added compatibility-safe PHP typing and static-analysis tooling without changing public API behavior.
* Declared Block API version 3 explicitly for predictable editor iframe behavior.
* Added pinned, reproducible asset builds and continuous integration checks.
* Preserved all saved gallery content and settings; no content migration is required.

= 0.3.1 =

* Added per-slide Top, Center, and Bottom vertical content alignment, defaulting to Bottom.
* Added position-aware contrast scrims for Top and Center slide content.
* Added the `align_vertical` Slider row value and normalized `slide_align_vertical` item key to the PHP API.

= 0.3.0 =

* Added canonical Slider rows with per-slide titles, plain-text subtext, horizontal alignment, and up to two CTA buttons.
* Reused Mai Engine Primary, Secondary, Outline, and Link Button styles.
* Enabled field validation for required slide images, CTA URLs, and visible button labels.
* Added a responsive contrast scrim, navigation-safe content spacing, titled accessibility labels, and print behavior.
* Made Slider images non-interactive and removed irrelevant click, caption, hover, column, alignment, and gap controls.
* Raised minimum Slider heights to 320px desktop and 280px mobile.
* Added the `slides` PHP API argument and filterable CTA style choices/classes.

= 0.2.1 =

* Added Top and Bottom placement for overlay Slider circles.
* Hid column, alignment, and gap controls for Slider while preserving block margins.
* Added Square and Rounded image-corner choices.

= 0.2.0 =

* Added the Tiled Mosaic and Slider layouts, bringing the total to nine.
* Changed Masonry to place images in the current shortest column, eliminating avoidable empty spaces while retaining a CSS fallback.
* Added Slider navigation modes for arrows and circles, arrows, circles, counter, or none, with overlay and below placement.
* Added Slider maximum width, desktop height, mobile height, cover/contain image fit, and source-quality controls.
* Added Frosted Circle, Rounded Square, Minimal Chevron, and Edge Tab arrow designs shared by the Slider and lightbox.
* Kept lightbox navigation controls stable across hover, focus, active, and click states.
* Added filters for shared arrow designs and Slider navigation choices.

= 0.1.1 =

* Prevented avoidable single-image rows in the Justified layout.
* Added independent starting-side and alternating controls for Editorial Mosaic feature tiles.

= 0.1.0 =

* Initial development release.
* Added Mai Grid, Masonry, Justified, Mosaic, Square, Circle, and Filmstrip layouts.
* Added lightbox, media, attachment, and custom link actions.
* Added caption placement, hover effect, image shadow, and Mai responsive layout controls.
