# Mai Cyber Gallery

Mai Cyber Gallery is a Mai Engine add-on with nine advanced image gallery layouts. It follows the same dynamic ACF block, responsive columns, image sizes, spacing controls, and Genesis markup APIs used by official Mai add-ons.

## Requirements

- WordPress 6.5 or later
- PHP 8.3 or later
- Genesis Framework
- A Genesis child theme with Mai Engine support
- Mai Engine 2.39.0 or later

Mai Engine is a deliberate dependency. It supplies ACF Pro, responsive column controls, image sizes, sanitizers, design tokens, and the Genesis/Mai runtime.

Version 0.4.0 raises the WordPress and PHP minimums for compatibility-safe typing and development tooling. Saved block content, gallery settings, and public PHP API behavior are unchanged, so no content migration is required.

Version 0.4.1 adds Slider width modes and editor-control refinements. Existing content and settings require no migration; choose **Contained** to retain the previous centered maximum-width presentation.

## Layouts

- **Mai Grid** — the familiar responsive Mai flex-column gallery.
- **Masonry Columns** — a natural-ratio waterfall that places each image in the current shortest column to avoid unnecessary empty space.
- **Justified Rows** — upload-order rows balanced around a configurable target height.
- **Tiled Mosaic** — a ratio-aware, source-order mosaic of mixed full-height and stacked tiles.
- **Editorial Mosaic** — a responsive five-image bento pattern with a selectable starting side and optional side alternation.
- **Square Tiles** — responsive 1:1 cropped tiles.
- **Circle Tiles** — responsive circular 1:1 tiles.
- **Filmstrip** — touch-friendly horizontal scroll snap with accessible controls.
- **Slider** — a responsive content slider with per-slide titles, subtext, CTA buttons, horizontal and vertical content alignment, selectable navigation, dimensions, and image fit.

Non-Slider layouts use Mai's column/row gap controls. Grid, Masonry, Square, Circle, and Filmstrip also use Mai's responsive column counts; Tiled Mosaic uses those controls as a maximum of four visual groups. Every layout retains top and bottom block margins.

## Editor usage

1. Activate Mai Engine and a Mai child theme.
2. Activate Mai Cyber Gallery.
3. Add the **Mai Cyber Gallery** block.
4. Choose a layout and add gallery images or structured Slider rows in the block sidebar.

For non-Slider layouts, click actions include no link, the built-in dialog lightbox, the original media file, the attachment page, and per-image custom links.

For Masonry, Justified, and Tiled Mosaic, choose an uncropped image size such as `large`. Mai Grid and Filmstrip use Mai's selected image orientation when an oriented size exists.

Editorial Mosaic adds a **First Feature Side** control plus an option to alternate that side after every five images.

When the click action is Lightbox, choose Frosted Circle, Rounded Square, Minimal Chevron, or Edge Tab navigation arrows. The controls keep a stable hit target across hover, focus, and active states.

Slider uses a dedicated **Slides** repeater. Every row has a required image plus optional title, plain-text subtext, Start/Center/End horizontal alignment, Top/Center/Bottom vertical alignment, and up to two CTA links. Vertical alignment defaults to Bottom. CTA styles reuse Mai Engine's Primary Button, Secondary Button, Outline Button, and Link Button styles. Slide images are intentionally non-interactive so CTAs remain the only destinations; Media Library captions and hover effects are not displayed in Slider.

Slider does not fall back to the legacy `images` array. Slider blocks created before 0.3.0 must be repopulated through the **Slides** repeater.

Slider navigation choices are Arrows + Circles, Arrows, Circles, Slide Counter, or None. Navigation can overlay the images or appear below them, and overlay circles can sit at the top or bottom. Slider controls also set the arrow design, width mode, maximum width, desktop height, mobile height, and whether images crop to fill or fit completely inside the slide. Column, gallery alignment, and gap controls are hidden because a Slider always displays one full-width slide.

Slider width modes are **Full**, **Contained**, and **Inset**. Full is the default and fills the available block or parent canvas. Contained applies **Maximum Slider Width** and preserves the previous centered presentation. Inset fills the canvas while retaining responsive side gutters. In the compact block inspector, maximum width and the desktop and mobile height fields stack one per row; the expanded editor keeps those three fields together in one row. Button-group choices use equal widths throughout the gallery controls.

**Image Quality / Source Size** selects the WordPress image source used in the responsive image markup. Choose a source at least as large as the displayed image: larger sources can look sharper but use more bandwidth. Slider dimensions control the display box independently.

**Image Corners** switches gallery images between Square and Rounded corners. Circle Tiles remain circular.

## PHP API

```php
echo mai_get_cyber_gallery(
	[
		'layout'       => 'masonry',
		'images'       => [ 123, 456, 789 ],
		'click_action' => 'lightbox',
		'columns'      => 3,
		'column_gap'   => 'md',
		'row_gap'      => 'md',
	]
);
```

`mai_do_cyber_gallery( $args )` is the echoing equivalent.

Slider uses structured rows rather than the gallery image arrays:

```php
echo mai_get_cyber_gallery(
	[
		'layout'            => 'slider',
		'slider_width_mode' => 'full',
		'slides'            => [
			[
				'image'          => 123,
				'title'          => 'Build something memorable',
				'subtext'        => 'A concise supporting message that remains readable at every breakpoint.',
				'align'          => 'start',
				'align_vertical' => 'bottom',
				'buttons'        => [
					[
						'link'  => [
							'url'    => 'https://example.com/',
							'title'  => 'Start a Project',
							'target' => '',
						],
						'style' => 'button',
					],
				],
			],
		],
	]
);
```

### Gallery arguments

| Argument | Default | Accepted value |
| --- | --- | --- |
| `id` | generated | Optional HTML ID; `anchor` takes precedence. |
| `anchor` | empty | Block/API anchor ID. |
| `class` | empty | Space-separated additional classes. |
| `align` | empty | Empty, `wide`, or `full`. |
| `layout` | `masonry` | `grid`, `masonry`, `justified`, `tiled`, `mosaic`, `square`, `circle`, `filmstrip`, or `slider`. |
| `mosaic_feature_side` | `left` | `left` or `right`; positions the first feature tile and sets the starting side. |
| `mosaic_alternate` | `false` | Boolean; swaps the feature side every five images. |
| `click_action` | `lightbox` | `none`, `lightbox`, `media`, `attachment`, or `custom`; ignored by Slider. |
| `lightbox_arrow_style` | `circle` | `circle`, `square`, `minimal`, or `edge`; used by non-Slider lightboxes. |
| `images` | empty array | Ordered attachment ID array for non-Slider, non-custom click actions. |
| `images_links` | empty array | Non-Slider image/link rows used when `click_action` is `custom`; schema shown below. |
| `slides` | empty array | Canonical Slider rows. Each accepts `image`, `title`, `subtext`, horizontal `align` (`start`, `center`, or `end`), vertical `align_vertical` (`top`, `center`, or `bottom`; default `bottom`), and up to two `buttons`. |
| `image_orientation` | `landscape` | A Mai image-orientation slug or `custom`; used by Grid and Filmstrip. |
| `image_size` | `large` | Any registered WordPress image-size slug. This controls source dimensions and quality, not the rendered display box. |
| `image_radius` | `rounded` | `square` or `rounded`. Circle Tiles always remain circular. |
| `captions` | `below` | `none`, `below`, or `overlay`; ignored by Slider, whose custom copy replaces captions. |
| `shadow` | `false` | Boolean value sanitized by Mai Engine. |
| `hover` | `zoom` | `none`, `zoom`, `lift`, or `fade`; ignored by Slider. |
| `justified_row_height` | `240` | Target pixel height from 100 through 600. |
| `slider_navigation` | `arrows-dots` | `arrows-dots`, `arrows`, `dots`, `counter`, or `none`. |
| `slider_navigation_position` | `overlay` | `overlay` or `below`. |
| `slider_dots_position` | `top` | `top` or `bottom`; applies to circle navigation when it overlays the image. |
| `slider_arrow_style` | `circle` | `circle`, `square`, `minimal`, or `edge`; used by navigation modes that include arrows. |
| `slider_width_mode` | `full` | `full` fills the available block or parent canvas, `contained` uses `slider_width`, and `inset` fills the canvas with responsive side gutters. |
| `slider_width` | `1200` | Maximum Slider width in pixels from 0 through 2400 when `slider_width_mode` is `contained`; `0` removes the maximum. |
| `slider_height` | `560` | Desktop slider height in pixels from 320 through 1200. |
| `slider_height_mobile` | `320` | Mobile slider height in pixels from 280 through 800. |
| `slider_image_fit` | `cover` | `cover` crops to fill the slide; `contain` shows the complete image. |
| `columns` | Mai default (`3`) | Main column count from 1 through 8; ignored by Slider. |
| `columns_responsive` | Mai default (`false`) | Use explicit breakpoint counts when true; ignored by Slider. |
| `columns_md`, `columns_sm`, `columns_xs` | Mai defaults | Explicit breakpoint counts; ignored by Slider. |
| `align_columns`, `align_columns_vertical` | Mai defaults | Mai column-alignment values; ignored by Slider. |
| `column_gap`, `row_gap` | Mai defaults | Mai spacing-size slugs or empty for no gap; ignored by Slider. |
| `margin_top`, `margin_bottom` | Mai defaults | Mai spacing-size slugs or empty. |

Custom links use the same array shape returned by ACF's Link field:

```php
echo mai_get_cyber_gallery(
	[
		'layout'       => 'filmstrip',
		'click_action' => 'custom',
		'images_links' => [
			[
				'image' => 123,
				'link'  => [
					'url'    => 'https://example.com/',
					'title'  => 'Visit the project',
					'target' => '_blank',
				],
			],
		],
	]
);
```

Slider button styles accept `button`, `button_secondary`, `button_outline`, or `button_link`. A button row is discarded unless its ACF-style `link` array contains both a valid URL and visible title. Only `_blank` targets are retained, and Slider rows are limited to two valid buttons. If the choices filter removes the primary `button` style, its first valid replacement becomes the editor and API fallback.

The helpers are available after Mai Engine has set up the theme. A gallery rendered through the block is detected early and gets its critical assets in the document head. If a theme template will render a Masonry, Justified, Tiled Mosaic, Filmstrip, Slider, or lightbox gallery through the PHP API after `wp_head`, enqueue the registered `mai-cyber-gallery` style and script handles from `wp_enqueue_scripts` so the first layout is ready before paint.

## Extension points

- `mai_cyber_gallery_layouts( array $layouts ): array` filters layout slug/label pairs. A custom layout must supply its own CSS and behavior.
- `mai_cyber_gallery_arrow_styles( array $styles ): array` filters the arrow design slug/label pairs shared by the lightbox and Slider.
- `mai_cyber_gallery_slider_navigation_choices( array $choices ): array` filters Slider navigation slug/label pairs.
- `mai_cyber_gallery_button_style_choices( array $choices ): array` filters Slider CTA style slug/label pairs.
- `mai_cyber_gallery_button_style_classes( array $styles ): array` filters CTA style-to-class mappings.
- `mai_cyber_gallery_args( array $args ): array` filters parsed arguments before sanitization.
- `mai_cyber_gallery_image_size( string $image_size, array $args ): string` filters the thumbnail size.
- `mai_cyber_gallery_item( array $item, array $args ): array` filters each normalized item.
- `mai_cyber_gallery_items( array $items, array $args ): array` filters the complete ordered item list.

An item contains `id`, rendered `image` HTML, `caption`, `alt`, attachment `title`, numeric `ratio`, `width`, `height`, and a `link` array. Slider items also contain sanitized `slide_title`, `slide_subtext`, `slide_align`, `slide_align_vertical`, and `slide_buttons`. Entries returned by the all-items filter are normalized again; non-array entries and entries without image HTML are discarded.

Genesis attribute filters are available for the `mai-cyber-gallery`, `mai-cyber-gallery-items`, `mai-cyber-gallery-item`, and `mai-cyber-gallery-image-link` contexts. The standard Genesis markup open/content/close filters are available for the first three contexts; the image link uses `genesis_attr()` only.

Every Cyber-owned PHP symbol, ACF field key, block name, asset handle, CSS selector, and Genesis context is distinct from Mai Galleries, so both plugins can be active during migration.

## Design notes

- Thumbnail markup comes from `wp_get_attachment_image()` so WordPress controls intrinsic dimensions, responsive sources, lazy loading, decoding, and fetch priority.
- Masonry progressively enhances its CSS multi-column fallback with a shortest-column layout that preserves source order and removes avoidable holes.
- Justified Rows progressively enhances the normal Mai grid and recalculates through `ResizeObserver`.
- Tiled Mosaic uses image ratios to build mixed, close-packed groups while preserving source order.
- Filmstrip uses native scrolling and never autoplays.
- Slider uses canonical content rows, a responsive contrast scrim, Mai-styled CTAs, native scroll snap, keyboard navigation, counters, titled status updates, and no autoplay.
- The lightbox uses the browser's modal `<dialog>` behavior, supports Escape/arrow/Home/End keys, offers four arrow designs, and returns focus to its trigger.
- Motion effects honor `prefers-reduced-motion`.
- No updater is bundled until this fork has its own release repository. It will never use the upstream Mai Galleries update channel.

## License

GPL-2.0-or-later.
