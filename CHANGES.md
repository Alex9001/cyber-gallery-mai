# Changelog

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
