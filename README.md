# Mai Cyber Gallery

Mai Cyber Gallery is a Mai Engine add-on with nine advanced image gallery layouts. It follows the same dynamic ACF block, responsive columns, image sizes, spacing controls, and Genesis markup APIs used by official Mai add-ons.

## Requirements

- WordPress 6.5 or later
- PHP 8.3 or later
- Genesis Framework
- A Genesis child theme with Mai Engine support
- Mai Engine 2.40.0 or later

Mai Engine is a deliberate dependency. It supplies ACF Pro, responsive column controls, image sizes, sanitizers, design tokens, and the Genesis/Mai runtime.

Version 0.4.0 raises the WordPress and PHP minimums for compatibility-safe typing and development tooling. Saved block content, gallery settings, and public PHP API behavior are unchanged, so no content migration is required.

Version 0.4.1 adds Slider width modes and editor-control refinements. Existing content and settings require no migration; choose **Contained** to retain the previous centered maximum-width presentation.

Version 0.5.0 adds selectable Slider transitions, navigation hover treatments and Mai-aware colors, plus configurable image overlays.

Version 0.5.1 completes a source-level Mai Engine integration audit. ACF color migration/formatting, column defaults and sanitization, instance indexes, CSS unit formatting, the configured XL breakpoint, control foundations, shadows, icons, typography, palettes, and design tokens now delegate to Mai Engine wherever it exposes a matching implementation.

Version 0.6.0 gives Slider effects true in-place transitions instead of layering them over a horizontal slide, sharpens circle navigation, adds two independently combinable navigation slots, and adds accessible autoplay timing and hover-pause controls.

Version 0.7.0 preserves every layout's image source and dormant settings when layouts change, shares image overlays and hover treatments across layouts, and expands Filmstrip with accessible autoplay, optional and positionable arrows, and purpose-built logo normalization.

Version 0.7.1 moves per-image logo sizing next to the selected image's metadata, uses Mai Icons for lightbox controls when available, and removes the Filmstrip's visual scrollbar while preserving native scrolling.

## Mai Engine integration boundary

Mai Cyber Gallery intentionally treats Mai Engine as its framework rather than merely a compatibility dependency. The plugin consumes Mai's ACF field clones, responsive column attributes, palette/color resolvers, image choices, sanitizers, counters, breakpoint and unit helpers, Genesis markup, buttons, utility classes, and CSS variables.

Gallery-specific responsibilities remain local because Mai Engine has no equivalent API: attachment normalization, masonry/justified/tiled placement, slider and filmstrip state, the dialog lightbox, accessibility lifecycle, transitions, and overlays. Cyber's integer `--cyber-columns-*` values are also intentional: Mai's standard `--columns-*` values are CSS fractions such as `1 / 3`, while CSS `column-count` and the packing algorithms require whole-number counts.

## Layouts

- **Mai Grid** — the familiar responsive Mai flex-column gallery.
- **Masonry Columns** — a natural-ratio waterfall that places each image in the current shortest column to avoid unnecessary empty space.
- **Justified Rows** — upload-order rows balanced around a configurable target height.
- **Tiled Mosaic** — a ratio-aware, source-order mosaic of mixed full-height and stacked tiles.
- **Editorial Mosaic** — a responsive five-image bento pattern with a selectable starting side and optional side alternation.
- **Square Tiles** — responsive 1:1 cropped tiles.
- **Circle Tiles** — responsive circular 1:1 tiles.
- **Filmstrip** — touch-friendly horizontal scroll snap with optional positioned controls, accessible timed movement, uniform crop cells, and a no-crop logo mode.
- **Slider** — a responsive content slider with per-slide titles, subtext, CTA buttons, horizontal and vertical content alignment, selectable transitions and navigation treatments, dimensions, image fit, and color overlays.

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

Slider uses a dedicated **Slides** repeater. Every row has a required image plus optional title, plain-text subtext, Start/Center/End horizontal alignment, Top/Center/Bottom vertical alignment, and up to two CTA links. Vertical alignment defaults to Bottom. CTA styles reuse Mai Engine's Primary Button, Secondary Button, Outline Button, and Link Button styles. Slide images are intentionally non-interactive so CTAs remain the only destinations; Media Library captions are replaced by the structured slide content.

Switching between Slider and another layout is non-destructive. The block keeps ordinary Images, Linked Images, structured Slides, and layout-specific settings serialized while they are hidden. If the newly selected layout has not yet been populated, it renders the retained source images as a fallback; returning to Slider restores its titles, copy, buttons, and alignment.

Slider has Primary and Secondary Navigation selectors. Each can use Arrows, Circles, Slide Counter, or None, so combinations such as Arrows + Circles and Circles + Counter are possible. Choosing the same control twice renders it only once, and the editor prevents duplicate selections. Navigation can overlay the images or appear below them, and overlay circles can sit at the top or bottom. Slider controls also set the arrow design, width mode, maximum width, desktop height, mobile height, and whether images crop to fill or fit completely inside the slide. Column, gallery alignment, and gap controls are hidden because a Slider always displays one full-width slide.

Slider transitions include Smooth Slide (`native`), Fade, Zoom In, Zoom Out, Rise, Soft Focus, Reveal, and Cinematic. Smooth Slide uses native horizontal scrolling. Controls and autoplay render every other preset as an in-place crossfade, with the selected entrance treatment layered into that crossfade instead of exposing an underlying slide. Direct touch and trackpad gestures remain native. The animated presets use Fast, Normal, and Slow speeds, and motion is removed when the visitor requests reduced motion.

Optional autoplay uses a configurable 3–30 second interval and can pause while the pointer is over the Slider. When motion is allowed, a visible Pause/Start control is provided while autoplay is enabled, including when both navigation selectors are None. Reduced-motion preferences disable rotation and hide its unnecessary toggle. Keyboard focus and manual navigation stop rotation until the visitor explicitly starts it again.

Navigation hover choices are Color Shift, Grow, Nudge, Glow, Fill, and Ring. Main, border, and inner colors can each be set independently for normal and hover states using the Mai Engine palette or a custom color. Hover treatments preserve the navigation controls' hit targets.

Image overlays are shared by every layout and support None, Solid, Gradient Bottom, Gradient Top, Gradient Left, Gradient Right, and Vignette styles. Overlay color uses the Mai Engine palette or a custom color, and opacity can be set from 0 through 100. In Slider this treatment remains separate from the content-legibility scrim used behind slide copy and CTAs.

Hover treatments are also shared across layouts. Desaturate removes a configurable 0–100 percent of color at rest and restores full color on mouse-over or keyboard focus. Zoom, Lift, Fade, and None remain available, and reduced-motion preferences suppress movement. Slider defaults to None so existing Sliders do not gain motion until a treatment is deliberately selected; other layouts retain the existing Zoom default.

Filmstrip arrows can be disabled or placed over the images, above them, or below them with Start, Center, and End alignment. Arrow design, hover treatment, and Mai palette/custom navigation colors are shared with Slider, including the autoplay control. Optional timed movement advances one visible group at a time and wraps at the end. When the strip overflows and motion is allowed, it provides a visible Pause/Start control even if arrows are disabled; controls stay out of the way when every item already fits. Rotation pauses for manual interaction, keyboard focus, hidden tabs, offscreen galleries, reduced-motion preferences, and optionally mouse-over.

For mixed logo rows, choose **Logo / Contain**. Every item receives the same responsive cell height, its complete source is centered with consistent inner breathing room, and any selected hard-cropped preset automatically falls back to the uncropped original. Select an image in the **Images** field and enable **Use a custom size for this image** beside its Title, Caption, and other metadata when unusual transparent whitespace makes that logo look optically small or large. Linked Images expose the same checkbox below each image. The setting belongs only to the current gallery; values above 100 percent deliberately zoom within the fixed cell and can clip if pushed too far. **Uniform Crop** provides equal-height photographic cells, while **Natural** preserves the original Filmstrip behavior.

Slider width modes are **Full**, **Contained**, and **Inset**. Full is the default and fills the available block or parent canvas. Contained applies **Maximum Slider Width** and preserves the previous centered presentation. Inset fills the canvas while retaining responsive side gutters. In the compact block inspector, maximum width and the desktop and mobile height fields stack one per row; the expanded editor keeps those three fields together in one row. Their number inputs retain validation but omit browser up/down spinner controls. Button-group choices use equal widths throughout the gallery controls.

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
		'layout'                       => 'slider',
		'slider_width_mode'            => 'full',
		'slider_transition'            => 'cinematic',
		'slider_transition_speed'      => 'normal',
		'slider_navigation'            => 'arrows',
		'slider_navigation_secondary'  => 'dots',
		'slider_navigation_hover'      => 'glow',
		'slider_autoplay'              => true,
		'slider_autoplay_interval'     => 5,
		'slider_pause_on_hover'        => true,
		'slider_overlay_style'         => 'gradient-bottom',
		'slider_overlay_color'         => 'black',
		'slider_overlay_opacity'       => 35,
		'slides'                       => [
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
| `click_action` | `lightbox` | `none`, `lightbox`, `media`, `attachment`, or `custom`. Slider never creates image links; when `slides` is empty, the retained value only selects ordinary versus linked-image fallback priority. |
| `lightbox_arrow_style` | `circle` | `circle`, `square`, `minimal`, or `edge`; used by non-Slider lightboxes. |
| `images` | empty array | Ordered attachment IDs used by non-custom galleries and as a Slider fallback when `slides` is empty. |
| `images_links` | empty array | Image/link rows used by `click_action=custom` and as a retained-image fallback; schema shown below. |
| `slides` | empty array | Structured Slider rows and a retained-image fallback for other empty layouts. Each accepts `image`, `title`, `subtext`, horizontal `align` (`start`, `center`, or `end`), vertical `align_vertical` (`top`, `center`, or `bottom`; default `bottom`), and up to two `buttons`. |
| `image_orientation` | `landscape` | A Mai image-orientation slug or `custom`; used by Grid and Filmstrip Natural/Uniform Crop modes, but intentionally ignored by Logo / Contain. |
| `image_size` | `large` | Any registered WordPress image-size slug. This controls source dimensions and quality, not the rendered display box; Logo / Contain replaces a registered hard crop with `full`. |
| `image_radius` | `rounded` | `square` or `rounded`. Circle Tiles always remain circular. |
| `captions` | `below` | `none`, `below`, or `overlay`; ignored by Slider, whose custom copy replaces captions. |
| `shadow` | `false` | Boolean value sanitized by Mai Engine. |
| `hover` | `zoom` (`none` for Slider when omitted) | `none`, `zoom`, `lift`, `fade`, or `desaturate`; available to every layout. |
| `hover_desaturate_amount` | `100` | Color removal from 0 through 100 percent at rest when `hover=desaturate`; full color returns on hover or focus. |
| `justified_row_height` | `240` | Target pixel height from 100 through 600. |
| `filmstrip_autoplay` | `false` | Boolean; advances visible Filmstrip groups automatically. |
| `filmstrip_autoplay_interval` | `5` | Seconds between automatic Filmstrip movements, clamped from 3 through 30. |
| `filmstrip_pause_on_hover` | `true` | Boolean; temporarily pauses Filmstrip autoplay while a mouse or pen is over it. |
| `filmstrip_arrows` | `true` | Boolean; shows or hides Filmstrip previous/next controls. |
| `filmstrip_arrow_position` | `below-end` | Positions Filmstrip controls at `overlay`, `above-start`, `above-center`, `above-end`, `below-start`, `below-center`, or `below-end`. |
| `filmstrip_arrow_style` | `circle` | `circle`, `square`, `minimal`, or `edge`. |
| `filmstrip_image_mode` | `natural` | `natural`, equal-height `uniform-cover`, or no-crop `logo-contain`. |
| `filmstrip_height` | `320` | Desktop cell height in pixels from 120 through 800 for uniform and logo modes. |
| `filmstrip_height_mobile` | `220` | Mobile cell height in pixels from 100 through 600 for uniform and logo modes. |
| `filmstrip_logo_padding` | `24` | Inner cell padding in pixels from 0 through 120 in logo mode. |
| `filmstrip_logo_overrides` | empty array | Rows containing an `image` attachment ID and optical `scale` from 50 through 200 percent; the last row for a repeated ID wins. |
| `slider_navigation` | `arrows` | Primary navigation: `arrows`, `dots`, `counter`, or `none`. The legacy aggregate `arrows-dots` value remains accepted and maps to Arrows + Circles. |
| `slider_navigation_secondary` | `dots` | Secondary navigation: `arrows`, `dots`, `counter`, or `none`. A duplicate of the primary value is reduced to `none`. |
| `slider_navigation_position` | `overlay` | `overlay` or `below`. |
| `slider_dots_position` | `top` | `top` or `bottom`; applies to circle navigation when it overlays the image. |
| `slider_arrow_style` | `circle` | `circle`, `square`, `minimal`, or `edge`; used by navigation modes that include arrows. |
| `slider_transition` | `native` | `native`, `fade`, `zoom-in`, `zoom-out`, `rise`, `soft-focus`, `reveal`, or `cinematic`. |
| `slider_transition_speed` | `normal` | `fast`, `normal`, or `slow`; applies to in-place animated transitions while `native` keeps browser scroll timing. |
| `slider_autoplay` | `false` | Boolean; automatically advances and wraps the Slider when enabled. |
| `slider_autoplay_interval` | `5` | Seconds between automatic slides, clamped from 3 through 30. |
| `slider_pause_on_hover` | `true` | Boolean; temporarily pauses autoplay while a mouse or pen pointer is over the Slider. |
| `slider_navigation_hover` | `color` | `color`, `grow`, `nudge`, `glow`, `fill`, or `ring`; shared by interactive Slider and Filmstrip navigation. |
| `slider_navigation_main`, `slider_navigation_border`, `slider_navigation_inner` | empty/site default | A Mai Engine palette slug or custom color for Slider and Filmstrip navigation background, border, and inner icon/text/dot. |
| `slider_navigation_main_hover`, `slider_navigation_border_hover`, `slider_navigation_inner_hover` | empty/site default | A Mai Engine palette slug or custom color for the corresponding navigation hover state. |
| `slider_overlay_style` | `none` | Shared image overlay: `none`, `solid`, `gradient-bottom`, `gradient-top`, `gradient-left`, `gradient-right`, or `vignette`. The legacy name is retained for compatibility. |
| `slider_overlay_color` | `black` | A Mai Engine palette slug or custom shared overlay color. |
| `slider_overlay_opacity` | `35` | Shared overlay opacity from 0 through 100. |
| `slider_width_mode` | `full` | `full` fills the available block or parent canvas, `contained` uses `slider_width`, and `inset` fills the canvas with responsive side gutters. |
| `slider_width` | Mai XL breakpoint (typically `1200`) | Maximum Slider width in pixels from 0 through 2400 when `slider_width_mode` is `contained`; `0` removes the maximum. |
| `slider_height` | `560` | Desktop slider height in pixels from 320 through 1200. |
| `slider_height_mobile` | `320` | Mobile slider height in pixels from 280 through 800. |
| `slider_image_fit` | `cover` | `cover` crops to fill the slide; `contain` shows the complete image. |
| `columns` | Mai default (`3`) | Main column count from 1 through 8; ignored by Slider. |
| `columns_responsive` | Mai default (`false`) | Use explicit breakpoint counts when true; ignored by Slider. |
| `columns_md`, `columns_sm`, `columns_xs` | Mai defaults | Explicit breakpoint counts; ignored by Slider. |
| `align_columns`, `align_columns_vertical` | Mai defaults | Mai column-alignment values; ignored by Slider. |
| `column_gap`, `row_gap` | Mai defaults | Mai spacing-size slugs or empty for no gap; ignored by Slider. |
| `margin_top`, `margin_bottom` | Mai defaults | Mai spacing-size slugs or empty. |

For backward compatibility, a PHP API call that explicitly supplies an atomic `slider_navigation` value but omits `slider_navigation_secondary` keeps that original single-control behavior. Pass both arguments to opt into a combination. The legacy `arrows-dots` aggregate continues to map to Arrows + Circles.

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
- `mai_cyber_gallery_arrow_styles( array $styles ): array` filters the arrow design slug/label pairs shared by the lightbox, Slider, and Filmstrip.
- `mai_cyber_gallery_slider_navigation_choices( array $choices ): array` filters the atomic Slider navigation choices shared by the Primary and Secondary selectors.
- `mai_cyber_gallery_slider_transition_choices( array $choices ): array` filters Slider transition slug/label pairs.
- `mai_cyber_gallery_slider_transition_speed_choices( array $choices ): array` filters Slider transition-speed slug/label pairs.
- `mai_cyber_gallery_slider_navigation_hover_choices( array $choices ): array` filters shared Slider/Filmstrip navigation-hover slug/label pairs.
- `mai_cyber_gallery_slider_overlay_style_choices( array $choices ): array` filters shared image-overlay style slug/label pairs; the original hook name remains stable.
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
- Shared controls inherit Mai's button foundation and variables, lightbox glyphs use Mai Icons when available, image shadows use Mai's `has-drop-shadow` utility, and Slider copy inherits Mai typography and dark-background rules.
- Masonry progressively enhances its CSS multi-column fallback with a shortest-column layout that preserves source order and removes avoidable holes.
- Justified Rows progressively enhances the normal Mai grid and recalculates through `ResizeObserver`.
- Tiled Mosaic uses image ratios to build mixed, close-packed groups while preserving source order.
- Filmstrip keeps native scrolling and scroll snap as its baseline while hiding browser scrollbar chrome; optional accessible autoplay layers on top without taking away touch, mouse, trackpad, or keyboard control.
- Slider uses canonical content rows, a responsive contrast scrim, Mai-styled CTAs, native scroll snap, keyboard navigation, counters, titled status updates, and optional accessible autoplay.
- Non-native Slider presets use an in-place transition compositor for controls and autoplay; direct gestures retain native scrolling. Transitions and navigation hover effects honor `prefers-reduced-motion`.
- Shared image overlays use Mai Engine palette colors when selected and remain separate from Slider's content-legibility scrim.
- The lightbox uses the browser's modal `<dialog>` behavior, supports Escape/arrow/Home/End keys, offers four arrow designs, and returns focus to its trigger.
- No updater is bundled until this fork has its own release repository. It will never use the upstream Mai Galleries update channel.

## License

GPL-2.0-or-later.
