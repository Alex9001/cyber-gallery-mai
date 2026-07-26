=== Mai Cyber Gallery ===
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: mai-engine
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Nine responsive masonry, tiled mosaic, filmstrip, and slider gallery layouts powered by Mai Engine.

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

The block reuses Mai Engine's ACF Pro integration, responsive columns, image sizes, gap and margin fields, Genesis markup API, and design tokens.

Click actions include a keyboard-accessible lightbox, media file, attachment page, custom per-image links, or no link. Captions can appear below images, as overlays, or be hidden. Lightbox arrows can use Frosted Circle, Rounded Square, Minimal Chevron, or Edge Tab designs.

Editorial Mosaic can start its feature image on the left or right and optionally alternate sides every five images.

Masonry places each image in the current shortest column to reduce unnecessary gaps. Tiled Mosaic uses source image ratios to create mixed full-height and stacked tiles while preserving image order, with the responsive column setting capped at four visual groups.

Slider navigation can use arrows and circles, arrows only, circles only, a slide counter, or no controls. Navigation can overlay the image or appear below it. Slider settings include the shared arrow designs, maximum width, separate desktop and mobile heights, and crop-to-fill or show-full-image fitting.

The Image Quality / Source Size setting chooses the WordPress image source. A larger source can look sharper at large display dimensions, but uses more bandwidth.

== Installation ==

1. Install and activate Genesis, a Mai child theme, and Mai Engine 2.39.0 or later.
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

= Does the filmstrip autoplay? =

No. It uses native touch/mouse scrolling, scroll snap, and accessible previous/next controls.

= Does the Slider autoplay? =

No. It uses native scroll snap with accessible navigation and keyboard controls.

== Changelog ==

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
