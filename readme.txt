=== Cyber Gallery for Mai Theme ===
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.3
Requires Plugins: mai-engine
Tags: gallery, masonry, slider, image comparison, filmstrip
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ten responsive gallery layouts, including an accessible Before / After comparison, powered by Mai Engine.

== Description ==

[Built by CYBER BRAND: Custom Web Development →](https://cyberbrand.net/)

Copyright (C) 2026 Aleksandr Oreshkin. Licensed under GPLv2 or any later version.

Related projects: [Mai Engine](https://github.com/maithemewp/mai-engine) and [Mai Grid Gallery](https://github.com/maithemewp/mai-grid-gallery). Cyber Gallery for Mai Theme is an independent add-on and is not presented as an official Mai Theme project.

Cyber Gallery for Mai Theme is a deliberate Mai Engine add-on. It adds a dynamic ACF gallery block with ten layouts:

* Mai Grid
* Masonry Columns
* Justified Rows
* Tiled Mosaic
* Editorial Mosaic
* Square Tiles
* Circle Tiles
* Before / After
* Filmstrip
* Slider

The block reuses Mai Engine's ACF Pro integration, responsive columns, image sizes, gap and margin fields, color formatting, configured breakpoints, controls, utility classes, Genesis markup API, and design tokens. Mai Engine 2.40.0 or later is required.

Mai Engine is proprietary and distributed separately from this plugin. The dependency header prevents activation when Engine is not active, but WordPress.org cannot download the missing dependency. Install and activate it manually from the Mai Theme distribution or the [Mai Engine GitHub project](https://github.com/maithemewp/mai-engine). Cyber Gallery consumes Mai Engine's supported APIs but does not vendor, fork, or modify Engine.

For ordinary image layouts other than Slider and Before / After, click actions include a keyboard-accessible lightbox, media file, attachment page, custom per-image links, or no link. Captions can appear below images, as overlays, or be hidden. Lightbox arrows can use Frosted Circle, Rounded Square, Minimal Chevron, or Edge Tab designs.

Editorial Mosaic can place 1–8 secondary images after each feature, close incomplete groups without blank cells, align an odd final image, alternate feature sides by group, and use automatic or fixed tile heights.

Masonry supports balanced shortest-column placement or an ordered column rhythm. Justified Rows can align their final partial row to Start, Center, or End. Tiled Mosaic uses source image ratios and Organic, Calm, or Dense pattern presets while preserving image order, with the responsive column setting capped at four visual groups.

Every layout has independent top, right, bottom, and left outer margins using Mai Engine's spacing scale.

Slider uses structured rows with a required image, optional title, plain-text subtext, Start/Center/End horizontal alignment, Top/Center/Bottom vertical alignment, and up to two CTA links. Vertical alignment defaults to Bottom. CTA styles reuse Mai Engine's Primary, Secondary, Outline, and Link Button designs. Slider images are non-interactive so CTAs remain the only destinations.

Slider has independent Primary and Secondary Navigation selectors, so arrows, circles, and the slide counter can be combined or disabled. Duplicate choices render only once. Navigation can overlay the image or appear below it, and overlay circles can be placed at the top or bottom. Slider settings include arrow design, width mode, maximum width, separate desktop and mobile heights, and crop-to-fill or show-full-image fitting. Full width is the default and fills the available block or parent canvas. Contained width uses the maximum width and preserves the previous centered presentation. Inset width fills the canvas with responsive side gutters. Irrelevant image-click, caption, column, gallery-alignment, and gap controls are hidden for Slider.

Switching layouts preserves ordinary Images, Linked Images, structured Slides, and dormant layout settings. Slider renders only structured Slides, so an empty Slider shows its setup prompt rather than stale content from the previous layout. Returning to another layout restores its retained images and settings.

Filmstrip, Slider, and Before / After have an optional accessibility label for distinguishing multiple regions on one page. Slider titles can use Heading 2 through Heading 6, or Paragraph when they should not enter the page heading outline.

Before / After uses two dedicated images and never falls back to retained ordinary gallery or Slider sources. It provides editable labels, a configurable starting position, Mai orientation/source-size integration, and an RTL-aware native range control for pointer, touch, keyboard, and assistive technology. It ignores responsive columns, gallery alignment, inter-item gaps, captions, links, overlays, and hover effects; its independent four-sided block margins still apply. Without JavaScript, the inactive range is not exposed and the configured split remains visible as a static comparison. Print output also keeps the split without the divider control.

Slider transitions include Smooth Slide (`native`), Fade, Zoom In, Zoom Out, Rise, Soft Focus, Reveal, and Cinematic. Smooth Slide keeps native horizontal movement. Controls and autoplay render the other presets as in-place crossfades with their selected entrance treatment; direct touch and trackpad gestures remain native. Navigation hover effects include Color Shift, Grow, Nudge, Glow, Fill, and Ring while retaining stable hit targets. Motion effects honor the visitor's reduced-motion preference.

Optional Slider autoplay has a configurable 3–30 second interval, mouse-over pause, and an optional Pause/Start control. The control can be hidden without disabling rotation.

Slider navigation background, border, and inner colors have independent normal and hover settings. Each reuses the Mai Engine palette and supports a custom color.

Image overlays work across the ordinary image layouts and Slider, and support None, Solid, four directional gradients, and Vignette styles with Mai palette/custom color and 0–100 opacity controls. Before / After intentionally omits overlays so neither source is obscured. In Slider the image overlay remains separate from the content-legibility scrim behind slide copy.

Hover treatments work across the ordinary image layouts and Slider. Before / After forces None so dragging remains its only image interaction. Desaturate removes a configurable 0–100 percent of color at rest and restores full color on mouse-over or keyboard focus. Slider defaults to None so existing Sliders remain motionless until a treatment is selected.

Filmstrip supports optional timed movement, a configurable number of items per advance, a complete controls toggle, an optional Pause/Start control, optional mouse-over pause, and opt-in arrows that can be overlaid or aligned above or below the images. Autoplay advances one item by default, continues when controls are hidden, and accepts intervals from 0.1 through 30 seconds. This intentionally supports moving logo rails and similar presentations that do not need playback controls. Touch swipes and mouse/pen throws use restrained momentum and a light elastic snap; autoplay, arrows, and keyboard navigation use a calm no-overshoot glide. Controls hide when every item fits. Filmstrip arrow design, navigation hover effects, and Mai palette/custom colors are shared with Slider.

Filmstrip also includes Natural, Uniform Crop, and Logo / Contain image modes. Logo mode preserves each source's intrinsic aspect ratio and centers it within an explicit fixed-height rail. Horizontal and vertical breathing room can be controlled independently and are optional rather than necessary for containment. Logo mode also automatically replaces a selected hard-cropped preset with the uncropped original. Select an image and enable its block-local custom-size checkbox beside the image metadata when unusual transparent whitespace or optical size needs compensation; Linked Images expose the same control below each image. Values above 100 percent intentionally zoom within the cell and can clip if overdone.

Existing Mai Gallery and Core Gallery blocks can be transformed into Cyber Gallery for Mai Theme's familiar Mai Grid layout from the block-type/Transform menu in the editor. Mai Gallery conversion preserves the active ordinary or custom-linked image source in order, lightbox behavior, shadow, image orientation and custom source size, responsive columns, alignment, gaps, margins, and supported block presentation settings. Because Mai Gallery has no caption or hover fields, conversion applies Cyber's Below Captions and Zoom defaults. Keep Mai Galleries active while loading its blocks for conversion. Core Gallery conversion preserves local attachment order and compatible gallery-level settings. Uncropped Core galleries retain their selected source size; cropped galleries map to the closest Mai orientation and retain the old size only as a dormant Custom-orientation value. A source gallery without any valid local attachment remains unchanged.

Frontend gallery behavior is natively deferred and divided into packing-layout, carousel, comparison, and lightbox scripts. An early scan requests the requirements visible in queried block content, including nested and synced/reusable blocks, while applying the same layout exclusions as the renderer. The renderer supplies a safety net for PHP/API output and content the scan cannot see. The split prevents every gallery from inherently depending on every enhancement. Dynamic gallery updates are batched to avoid repeated document rescans, and attachment data is primed in bulk for faster uncached rendering.

LiteSpeed Cache compatibility preserves runtime-only gallery selectors during UCSS generation, prevents native deferred scripts from being postponed until user interaction, promotes the selected Slider image through LiteSpeed lazy loading, and tags cached pages by attachment for targeted purging when image metadata changes. Offscreen gallery images remain eligible for LiteSpeed lazy loading, VPI, CDN, and WebP/AVIF optimization.

An empty Cyber Gallery for Mai Theme preview includes a Manage Gallery action that opens ACF's expanded block editor. The corresponding block-settings launcher is labeled Open Expanded Editor.

Button-group choices use equal widths throughout the gallery controls. In the compact block inspector, maximum width and the desktop and mobile height fields stack one per row; the expanded editor keeps them together in one row. Browser up/down spinner controls are removed from those Slider dimension inputs while their numeric validation remains.

The Image Quality / Source Size setting chooses the WordPress image source. A larger source can look sharper at large display dimensions, but uses more bandwidth.

Image Corners can be Square or Rounded. Circle Tiles always remain circular.

== Upgrade Notice ==

= 1.0.0 =

Adds a Before / After comparison layout with dedicated image sources, Mai image sizing, editable labels, an interactive RTL/keyboard/touch enhancement, and static no-JavaScript and print fallbacks.

= 0.9.0 =

Adds configurable Editorial Mosaic groups, new Masonry/Justified/Tiled composition controls, and independent four-sided margins while preserving existing gallery defaults.

= 0.8.4 =

Adds one-click editor conversion from original Mai Gallery blocks while preserving ordinary or custom-linked images and compatible layout settings.

= 0.8.3 =

Makes Filmstrip arrows opt-in, separates horizontal and vertical logo padding, and clarifies the expanded-editor launcher.

= 0.8.2 =

Fixes clipped and vertically misaligned mixed-logo Filmstrips without requiring global padding.

= 0.8.1 =

Makes Filmstrip autoplay advance one item by default, adds configurable step size, and gives manual dragging a restrained professional bounce.

= 0.8.0 =

Adds Core Gallery conversion, a direct empty-gallery editor action, deferred and LiteSpeed-aware public assets, and repaired Filmstrip autoplay, controls, dragging, and layout switching.

= 0.7.2 =

Adds discreet CYBER BRAND authorship in the gallery editor and standard plugin author metadata without adding front-end credits or tracking.

= 0.7.1 =

Moves logo sizing beside each image, adopts Mai Icons for lightbox controls when available, and removes the Filmstrip's visible scrollbar without removing native scrolling.

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

1. Obtain Mai Engine separately from the Mai Theme distribution or its GitHub project. If installing a GitHub archive directly, ensure its final plugin directory is named `mai-engine`, not a versioned archive name.
2. Install and activate Genesis, a Mai child theme, and Mai Engine 2.40.0 or later.
3. Upload the `mai-cyber-gallery` directory to `/wp-content/plugins/`.
4. Activate Cyber Gallery for Mai Theme.
5. Add the Cyber Gallery for Mai Theme block in the block editor.

== Frequently Asked Questions ==

= Does this replace Mai Engine? =

No. Mai Engine is a separately maintained, proprietary requirement and intentionally supplies the plugin's core block, image, responsive-layout, spacing, and theme infrastructure. Cyber Gallery uses Engine's APIs and does not edit Engine.

= Why does WordPress.org not install Mai Engine automatically? =

Mai Engine is not a WordPress.org plugin. WordPress can enforce its installed `mai-engine` dependency slug but cannot download it or enforce the required version. Obtain, install, and activate Mai Engine 2.40.0 or later separately from the Mai Theme distribution or its GitHub project before activating Cyber Gallery for Mai Theme. WordPress matches the installed directory slug, so a manually extracted GitHub archive must use `wp-content/plugins/mai-engine/`, not a versioned directory such as `mai-engine-2.40.0/`.

= Can Mai Galleries remain active? =

Yes. Cyber-owned block, field, class, function, asset, filter, and markup names are unique. Shared responsive controls deliberately clone Mai Engine's fields.

= Which image size should I use for masonry? =

Use an uncropped size such as Large so portrait and landscape images retain their natural proportions.

= How do I control Slider image quality? =

Set Image Quality / Source Size to a WordPress image size at least as large as the Slider's display dimensions. Width and height control the display box; the image size controls the source pixels and bandwidth.

= How do I control Slider width? =

Full fills the available block or parent canvas and is the default. Contained respects Maximum Slider Width for the previous centered look. Inset fills the canvas with responsive side gutters.

= Does the filmstrip autoplay? =

Optionally. It always retains native touch, mouse, trackpad, keyboard scrolling, and scroll snap. Timed movement advances one item by default, or a user-selected number of items. The complete control group, including Pause/Start, can intentionally be hidden for logo rails and similar presentations without disabling timed movement. Reduced-motion, visibility, focus, and manual-interaction safeguards still apply.

= Does the Slider autoplay? =

Autoplay is optional. Set the time between slides from 3 through 30 seconds, optionally pause while the pointer is over the Slider, and choose whether to show its Pause/Start control. Reduced-motion preferences disable autoplay.

== Changelog ==

= 1.0.0 =

* Added a strict, full-width Before / After comparison layout with dedicated Before and After image fields.
* Added editable labels and a configurable initial divider position.
* Added an RTL-aware native range control for pointer, touch, keyboard, and assistive technology.
* Reused Mai Engine image orientations, large crop sizes, responsive WordPress image markup, icons, shadows, radii, spacing, and design tokens.
* Kept a static configured split without JavaScript and in print, without exposing the inactive divider control.
* Added layout-aware required-field validation while preserving dormant gallery and cloned Mai column values across layout changes.
* Added distinct accessibility labels, selectable Slider title semantics, registered-size fallback, synced-block asset discovery, cache tags for both attachments, and expanded release/test coverage.
* Hardened filtered choice lists, filtered defaults, and incomplete argument-filter results so extension points cannot leave unusable editor or runtime state.
* Moved public behavior scripts to safe late footer output for galleries rendered through the PHP API.
* Preserved literal dollar sequences in comparison labels and lightbox captions when formatting accessible status text.
* Brought all shipped PHP onto the complete WordPress Coding Standards ruleset and conventionally named class files without changing the public API or Mai Engine integration.
* Added explicit coding-standards and PHP-compatibility coverage for repository-only tests and external API stubs.

= 0.9.0 =

* Added configurable Editorial Mosaic groups with one feature followed by 1–8 secondary images.
* Closed incomplete groups automatically and added Fill, Start, Center, and End treatments for an odd final secondary.
* Added an optional fixed Mosaic tile height while retaining responsive automatic sizing by default.
* Added Balanced/Ordered Masonry packing, Organic/Calm/Dense Tiled patterns, and Justified final-row alignment.
* Added independent left and right outer margins to every layout using Mai's spacing scale.
* Preserved the former appearance of existing galleries through migration-safe defaults.
* Added the complete GPLv2 license, explicit Aleksandr Oreshkin copyright notice, and links to the related Mai Engine and Mai Grid Gallery repositories.

= 0.8.4 =

* Added a native block-toolbar transform from Mai Gallery to Cyber Gallery for Mai Theme.
* Preserved ordinary and custom-linked image order, URLs, lightbox behavior, shadow, orientation, image size, responsive columns, alignment, gaps, margins, and supported block presentation settings.
* Left galleries without a valid active attachment source unchanged.

= 0.8.3 =

* Removed Filmstrip arrows from editor previews unless Filmstrip Arrows is selected.
* Split Logo Padding into independently configurable horizontal and vertical controls with legacy-value migration.
* Renamed the right-sidebar action to Open Expanded Editor.

= 0.8.2 =

* Fixed oversized square logo boxes being clipped by the fixed-height Filmstrip rail.
* Preserved intrinsic logo proportions inside an explicit centered height constraint, making shared padding optional and per-image sizing reliable.

= 0.8.1 =

* Changed Filmstrip autoplay to advance one item by default and added a configurable Items Per Advance setting.
* Toned manual Filmstrip throws down to a shorter, restrained bounce with subtler edge stretch and tilt.
* Hardened attachment metadata cache purging during plugin-updater cleanup.

= 0.8.0 =

* Added best-effort Core Gallery conversion into the Mai Grid layout while preserving supported attachment and block settings.
* Added a direct Manage Gallery action to empty previews with an accessible settings fallback.
* Split public and editor-only JavaScript/CSS into independent asset bundles.
* Added automated transform, version-consistency, and asset-reproducibility checks.
* Fixed Filmstrip autoplay and Pause/Start icon state.
* Added a complete Filmstrip-controls toggle and momentum-driven, elastically snapping touch/mouse throws.
* Kept Filmstrip autoplay, arrow, and keyboard movement calm while reserving playful overshoot for manual dragging.
* Lowered the Filmstrip autoplay interval floor to 0.1 seconds.
* Added an option to hide the Slider Pause/Start button without disabling autoplay.
* Restored interactive Filmstrip and Slider preview controls for testing motion effects in the editor.
* Preserved the selected Slider slide while its editor preview refreshes during content changes.
* Matched Slider CTA button variants between editor previews and the frontend.
* Fixed visited Outline CTA links incorrectly using their black hover text in editor previews.
* Prevented stale Filmstrip output and validation state when switching to an empty Slider.

= 0.7.2 =

* Added a compact CYBER BRAND authorship and custom-development link to both gallery settings editors.
* Added standard CYBER BRAND plugin author metadata and project documentation attribution.
* Removed the redundant feature summary from the compact block inspector.
* Kept the credit out of public gallery markup and added no settings screen, dashboard notice, or tracking.

= 0.7.1 =

* Moved Filmstrip logo sizing into an optional block-local control beside each selected image's metadata.
* Preserved the existing override data and public PHP API, with a progressively enhanced editor fallback.
* Reused Mai Icons for lightbox controls when available and consolidated navigation styling on Mai's button system.
* Hid Filmstrip scrollbar chrome while preserving native scrolling.
* Added local sizing guards against gallery-driven horizontal overflow.

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
