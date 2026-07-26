=== Mai Cyber Gallery ===
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.1
Requires Plugins: mai-engine
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced masonry, tiled, mosaic, and filmstrip galleries powered by Mai Engine.

== Description ==

Mai Cyber Gallery is a deliberate Mai Engine add-on. It adds a dynamic ACF gallery block with seven layouts:

* Mai Grid
* Masonry Columns
* Justified Rows
* Editorial Mosaic
* Square Tiles
* Circle Tiles
* Filmstrip

The block reuses Mai Engine's ACF Pro integration, responsive columns, image sizes, gap and margin fields, Genesis markup API, and design tokens.

Click actions include a keyboard-accessible lightbox, media file, attachment page, custom per-image links, or no link. Captions can appear below images, as overlays, or be hidden.

Editorial Mosaic can start its feature image on the left or right and optionally alternate sides every five images.

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

= Does the filmstrip autoplay? =

No. It uses native touch/mouse scrolling, scroll snap, and accessible previous/next controls.

== Changelog ==

= 0.1.1 =

* Prevented avoidable single-image rows in the Justified layout.
* Added independent starting-side and alternating controls for Editorial Mosaic feature tiles.

= 0.1.0 =

* Initial development release.
* Added Mai Grid, Masonry, Justified, Mosaic, Square, Circle, and Filmstrip layouts.
* Added lightbox, media, attachment, and custom link actions.
* Added caption placement, hover effect, image shadow, and Mai responsive layout controls.
