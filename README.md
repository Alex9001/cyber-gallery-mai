# Mai Cyber Gallery

Mai Cyber Gallery is a Mai Engine add-on for advanced image gallery layouts. It follows the same dynamic ACF block, responsive columns, image sizes, spacing controls, and Genesis markup APIs used by official Mai add-ons.

## Requirements

- WordPress 6.4 or later
- PHP 8.1 or later
- Genesis Framework
- A Genesis child theme with Mai Engine support
- Mai Engine 2.39.0 or later

Mai Engine is a deliberate dependency. It supplies ACF Pro, responsive column controls, image sizes, sanitizers, design tokens, and the Genesis/Mai runtime.

## Layouts

- **Mai Grid** — the familiar responsive Mai flex-column gallery.
- **Masonry Columns** — a natural-ratio waterfall with responsive column counts.
- **Justified Rows** — upload-order rows balanced around a configurable target height.
- **Editorial Mosaic** — a responsive five-image bento pattern with a selectable starting side and optional side alternation.
- **Square Tiles** — responsive 1:1 cropped tiles.
- **Circle Tiles** — responsive circular 1:1 tiles.
- **Filmstrip** — touch-friendly horizontal scroll snap with accessible controls.

Every layout uses Mai's column/row gaps and margin controls. Grid, Masonry, Square, Circle, and Filmstrip also use Mai's responsive column counts.

## Editor usage

1. Activate Mai Engine and a Mai child theme.
2. Activate Mai Cyber Gallery.
3. Add the **Mai Cyber Gallery** block.
4. Select images, a click action, captions, and a layout in the block sidebar.

Click actions include no link, the built-in dialog lightbox, the original media file, the attachment page, and per-image custom links.

For Masonry and Justified layouts, choose an uncropped image size such as `large`. Mai Grid and Filmstrip use Mai's selected image orientation when an oriented size exists.

Editorial Mosaic adds a **First Feature Side** control plus an option to alternate that side after every five images.

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

### Gallery arguments

| Argument | Default | Accepted value |
| --- | --- | --- |
| `id` | generated | Optional HTML ID; `anchor` takes precedence. |
| `anchor` | empty | Block/API anchor ID. |
| `class` | empty | Space-separated additional classes. |
| `align` | empty | Empty, `wide`, or `full`. |
| `layout` | `masonry` | `grid`, `masonry`, `justified`, `mosaic`, `square`, `circle`, or `filmstrip`. |
| `mosaic_feature_side` | `left` | `left` or `right`; positions the first feature tile and sets the starting side. |
| `mosaic_alternate` | `false` | Boolean; swaps the feature side every five images. |
| `click_action` | `lightbox` | `none`, `lightbox`, `media`, `attachment`, or `custom`. |
| `images` | empty array | Ordered attachment ID array for every non-custom click action. |
| `images_links` | empty array | Image/link rows used when `click_action` is `custom`; schema shown below. |
| `image_orientation` | `landscape` | A Mai image-orientation slug or `custom`; used by Grid and Filmstrip. |
| `image_size` | `large` | Any registered WordPress image-size slug. |
| `captions` | `below` | `none`, `below`, or `overlay`. Captions come from Media Library attachments. |
| `shadow` | `false` | Boolean value sanitized by Mai Engine. |
| `hover` | `zoom` | `none`, `zoom`, `lift`, or `fade`. |
| `justified_row_height` | `240` | Target pixel height from 100 through 600. |
| `columns` | Mai default (`3`) | Main column count from 1 through 8. |
| `columns_responsive` | Mai default (`false`) | Use explicit breakpoint counts when true; otherwise Mai calculates them. |
| `columns_md`, `columns_sm`, `columns_xs` | Mai defaults | Explicit counts from 1 through 8 when responsive columns are enabled. |
| `align_columns`, `align_columns_vertical` | Mai defaults | Mai column-alignment values. |
| `column_gap`, `row_gap` | Mai defaults | Mai spacing-size slugs or empty for no gap. |
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

The helpers are available after Mai Engine has set up the theme. A gallery rendered through the block is detected early and gets its critical assets in the document head. If a theme template will render a Justified gallery through the PHP API after `wp_head`, enqueue the registered `mai-cyber-gallery` style and script handles from `wp_enqueue_scripts` so the first layout is ready before paint.

## Extension points

- `mai_cyber_gallery_layouts( array $layouts ): array` filters layout slug/label pairs. A custom layout must supply its own CSS and behavior.
- `mai_cyber_gallery_args( array $args ): array` filters parsed arguments before sanitization.
- `mai_cyber_gallery_image_size( string $image_size, array $args ): string` filters the thumbnail size.
- `mai_cyber_gallery_item( array $item, array $args ): array` filters each normalized item.
- `mai_cyber_gallery_items( array $items, array $args ): array` filters the complete ordered item list.

An item contains `id`, rendered `image` HTML, `caption`, `alt`, `title`, numeric `ratio`, `width`, `height`, and a `link` array. Entries returned by the all-items filter are normalized again; non-array entries and entries without image HTML are discarded.

Genesis attribute filters are available for the `mai-cyber-gallery`, `mai-cyber-gallery-items`, `mai-cyber-gallery-item`, and `mai-cyber-gallery-image-link` contexts. The standard Genesis markup open/content/close filters are available for the first three contexts; the image link uses `genesis_attr()` only.

Every Cyber-owned PHP symbol, ACF field key, block name, asset handle, CSS selector, and Genesis context is distinct from Mai Galleries, so both plugins can be active during migration.

## Design notes

- Thumbnail markup comes from `wp_get_attachment_image()` so WordPress controls intrinsic dimensions, responsive sources, lazy loading, decoding, and fetch priority.
- Masonry is CSS multi-column and remains fully functional without JavaScript.
- Justified Rows progressively enhances the normal Mai grid and recalculates through `ResizeObserver`.
- Filmstrip uses native scrolling and never autoplays.
- The lightbox uses the browser's modal `<dialog>` behavior, supports Escape/arrow/Home/End keys, and returns focus to its trigger.
- Motion effects honor `prefers-reduced-motion`.
- No updater is bundled until this fork has its own release repository. It will never use the upstream Mai Galleries update channel.

## License

GPL-2.0-or-later.
