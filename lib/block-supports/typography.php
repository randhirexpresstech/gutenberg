<?php
/**
 * Typography block support flag.
 *
 * @package gutenberg
 */

/**
 * Registers the style and typography block attributes for block types that support it.
 *
 * @param WP_Block_Type $block_type Block Type.
 */
function gutenberg_register_typography_support( $block_type ) {
	if ( ! property_exists( $block_type, 'supports' ) ) {
		return;
	}

	$typography_supports = _wp_array_get( $block_type->supports, array( 'typography' ), false );
	if ( ! $typography_supports ) {
		return;
	}

	$has_font_family_support     = _wp_array_get( $typography_supports, array( '__experimentalFontFamily' ), false );
	$has_font_size_support       = _wp_array_get( $typography_supports, array( 'fontSize' ), false );
	$has_font_style_support      = _wp_array_get( $typography_supports, array( '__experimentalFontStyle' ), false );
	$has_font_weight_support     = _wp_array_get( $typography_supports, array( '__experimentalFontWeight' ), false );
	$has_letter_spacing_support  = _wp_array_get( $typography_supports, array( '__experimentalLetterSpacing' ), false );
	$has_line_height_support     = _wp_array_get( $typography_supports, array( 'lineHeight' ), false );
	$has_text_decoration_support = _wp_array_get( $typography_supports, array( '__experimentalTextDecoration' ), false );
	$has_text_transform_support  = _wp_array_get( $typography_supports, array( '__experimentalTextTransform' ), false );

	$has_typography_support = $has_font_family_support
		|| $has_font_size_support
		|| $has_font_style_support
		|| $has_font_weight_support
		|| $has_letter_spacing_support
		|| $has_line_height_support
		|| $has_text_decoration_support
		|| $has_text_transform_support;

	if ( ! $block_type->attributes ) {
		$block_type->attributes = array();
	}

	if ( $has_typography_support && ! array_key_exists( 'style', $block_type->attributes ) ) {
		$block_type->attributes['style'] = array(
			'type' => 'object',
		);
	}

	if ( $has_font_size_support && ! array_key_exists( 'fontSize', $block_type->attributes ) ) {
		$block_type->attributes['fontSize'] = array(
			'type' => 'string',
		);
	}
}

/**
 * Add CSS classes and inline styles for typography features such as font sizes
 * to the incoming attributes array. This will be applied to the block markup in
 * the front-end.
 *
 * @param  WP_Block_Type $block_type       Block type.
 * @param  array         $block_attributes Block attributes.
 *
 * @return array Typography CSS classes and inline styles.
 */
function gutenberg_apply_typography_support( $block_type, $block_attributes ) {
	if ( ! property_exists( $block_type, 'supports' ) ) {
		return array();
	}

	$typography_supports = _wp_array_get( $block_type->supports, array( 'typography' ), false );
	if ( ! $typography_supports ) {
		return array();
	}

	$skip_typography_serialization = _wp_array_get( $typography_supports, array( '__experimentalSkipSerialization' ), false );
	if ( $skip_typography_serialization ) {
		return array();
	}

	$attributes = array();
	$classes    = array();
	$styles     = array();

	$has_font_family_support     = _wp_array_get( $typography_supports, array( '__experimentalFontFamily' ), false );
	$has_font_size_support       = _wp_array_get( $typography_supports, array( 'fontSize' ), false );
	$has_font_style_support      = _wp_array_get( $typography_supports, array( '__experimentalFontStyle' ), false );
	$has_font_weight_support     = _wp_array_get( $typography_supports, array( '__experimentalFontWeight' ), false );
	$has_letter_spacing_support  = _wp_array_get( $typography_supports, array( '__experimentalLetterSpacing' ), false );
	$has_line_height_support     = _wp_array_get( $typography_supports, array( 'lineHeight' ), false );
	$has_text_decoration_support = _wp_array_get( $typography_supports, array( '__experimentalTextDecoration' ), false );
	$has_text_transform_support  = _wp_array_get( $typography_supports, array( '__experimentalTextTransform' ), false );

	if ( $has_font_size_support ) {
		$has_named_font_size  = array_key_exists( 'fontSize', $block_attributes );
		$has_custom_font_size = isset( $block_attributes['style']['typography']['fontSize'] );

		if ( $has_named_font_size ) {
			$classes[] = sprintf( 'has-%s-font-size', _wp_to_kebab_case( $block_attributes['fontSize'] ) );
		} elseif ( $has_custom_font_size ) {
			$styles[] = sprintf( 'font-size: %s;', $block_attributes['style']['typography']['fontSize'] );
		}
	}

	if ( $has_font_family_support ) {
		$has_named_font_family  = array_key_exists( 'fontFamily', $block_attributes );
		$has_custom_font_family = isset( $block_attributes['style']['typography']['fontFamily'] );

		if ( $has_named_font_family ) {
			$classes[] = sprintf( 'has-%s-font-family', _wp_to_kebab_case( $block_attributes['fontFamily'] ) );
		} elseif ( $has_custom_font_family ) {
			// Before using classes, the value was serialized as a CSS Custom Property.
			// We don't need this code path when it lands in core.
			$font_family_custom = $block_attributes['style']['typography']['fontFamily'];
			if ( strpos( $font_family_custom, 'var:preset|font-family' ) !== false ) {
				$index_to_splice    = strrpos( $font_family_custom, '|' ) + 1;
				$font_family_slug   = _wp_to_kebab_case( substr( $font_family_custom, $index_to_splice ) );
				$font_family_custom = sprintf( 'var(--wp--preset--font-family--%s)', $font_family_slug );
			}
			$styles[] = sprintf( 'font-family: %s;', $font_family_custom );
		}
	}

	if ( $has_font_style_support ) {
		$font_style = gutenberg_typography_get_css_variable_inline_style( $block_attributes, 'fontStyle', 'font-style' );
		if ( $font_style ) {
			$styles[] = $font_style;
		}
	}

	if ( $has_font_weight_support ) {
		$font_weight = gutenberg_typography_get_css_variable_inline_style( $block_attributes, 'fontWeight', 'font-weight' );
		if ( $font_weight ) {
			$styles[] = $font_weight;
		}
	}

	if ( $has_line_height_support ) {
		$has_line_height = isset( $block_attributes['style']['typography']['lineHeight'] );
		if ( $has_line_height ) {
			$styles[] = sprintf( 'line-height: %s;', $block_attributes['style']['typography']['lineHeight'] );
		}
	}

	if ( $has_text_decoration_support ) {
		$text_decoration_style = gutenberg_typography_get_css_variable_inline_style( $block_attributes, 'textDecoration', 'text-decoration' );
		if ( $text_decoration_style ) {
			$styles[] = $text_decoration_style;
		}
	}

	if ( $has_text_transform_support ) {
		$text_transform_style = gutenberg_typography_get_css_variable_inline_style( $block_attributes, 'textTransform', 'text-transform' );
		if ( $text_transform_style ) {
			$styles[] = $text_transform_style;
		}
	}

	if ( $has_letter_spacing_support ) {
		$letter_spacing_style = gutenberg_typography_get_css_variable_inline_style( $block_attributes, 'letterSpacing', 'letter-spacing' );
		if ( $letter_spacing_style ) {
			$styles[] = $letter_spacing_style;
		}
	}

	if ( ! empty( $classes ) ) {
		$attributes['class'] = implode( ' ', $classes );
	}
	if ( ! empty( $styles ) ) {
		$attributes['style'] = implode( ' ', $styles );
	}

	return $attributes;
}

/**
 * Generates an inline style for a typography feature e.g. text decoration,
 * text transform, and font style.
 *
 * @param array  $attributes   Block's attributes.
 * @param string $feature      Key for the feature within the typography styles.
 * @param string $css_property Slug for the CSS property the inline style sets.
 *
 * @return string              CSS inline style.
 */
function gutenberg_typography_get_css_variable_inline_style( $attributes, $feature, $css_property ) {
	// Retrieve current attribute value or skip if not found.
	$style_value = _wp_array_get( $attributes, array( 'style', 'typography', $feature ), false );
	if ( ! $style_value ) {
		return;
	}

	// If we don't have a preset CSS variable, we'll assume it's a regular CSS value.
	if ( strpos( $style_value, "var:preset|{$css_property}|" ) === false ) {
		return sprintf( '%s:%s;', $css_property, $style_value );
	}

	// We have a preset CSS variable as the style.
	// Get the style value from the string and return CSS style.
	$index_to_splice = strrpos( $style_value, '|' ) + 1;
	$slug            = substr( $style_value, $index_to_splice );

	// Return the actual CSS inline style e.g. `text-decoration:var(--wp--preset--text-decoration--underline);`.
	return sprintf( '%s:var(--wp--preset--%s--%s);', $css_property, $css_property, $slug );
}

/**
 * Returns a font-size value based on a given font-size preset. If typography.fluid is enabled it will calculate clamp values.
 *
 * @param array $preset Duotone preset value as seen in theme.json.
 * @return string        Font-size value.
 */
function gutenberg_get_typography_font_size_value( $preset ) {
	$typography_settings = gutenberg_get_global_settings( array( 'typography' ) );

	// This is where we'll keep options I guess.
	if ( ! isset( $typography_settings['fluid'] ) ) {
		return $preset['size'];
	}

	// Up for discussion.
	$default_unit                   = 'rem';
	$default_minimum_viewport_width = '1600px';
	$default_maximum_viewport_width = '650px';

	// Matches rem or px values only.
	$pattern = '/^(\d*\.?\d+)(rem|px)?$/';
	// Could we also take these from layout? contentSize and wideSize?
	$minimum_viewport_width      = isset( $typography_settings['minViewportWidth'] ) ? $typography_settings['minViewportWidth'] : $default_minimum_viewport_width;
	$minimum_viewport_width_unit = $default_unit;
	$maximum_viewport_width      = isset( $typography_settings['maxViewportWidth'] ) ? $typography_settings['maxViewportWidth'] : $default_maximum_viewport_width;
	$maximum_viewport_width_unit = $default_unit;

	// Minimum viewport size.
	preg_match_all( $pattern, $minimum_viewport_width, $minimum_viewport_width_matches );
	if ( isset( $minimum_viewport_width_matches[1][0] ) ) {
		$minimum_viewport_width      = intval( $minimum_viewport_width_matches[1][0] );
		$minimum_viewport_width_unit = isset( $minimum_viewport_width_matches[2][0] ) ? $minimum_viewport_width_matches[2][0] : $minimum_viewport_width_unit;
		if ( 'px' === $minimum_viewport_width_unit ) {
			// Default is rem so we convert px to rem.
			$minimum_viewport_width = $minimum_viewport_width / 16;
		}
	}

	// Maximum viewport size.
	preg_match_all( $pattern, $maximum_viewport_width, $maximum_viewport_width_matches );
	if ( isset( $maximum_viewport_width_matches[1][0] ) ) {
		$maximum_viewport_width      = intval( $maximum_viewport_width_matches[1][0] );
		$maximum_viewport_width_unit = isset( $maximum_viewport_width_matches[2][0] ) ? $maximum_viewport_width_matches[2][0] : $maximum_viewport_width_unit;
		if ( 'px' === $maximum_viewport_width_unit ) {
			// Default is rem so we convert px to rem.
			$maximum_viewport_width = $maximum_viewport_width / 16;
		}
	}

	// Font sizes.
	preg_match_all( $pattern, $preset['size'], $size_matches );
	if ( isset( $size_matches[1][0] ) ) {
		$base_size_value = $size_matches[1][0];

		if ( isset( $size_matches[2][0] ) && 'px' === $size_matches[2][0] ) {
			// Default is rem so we convert px to rem.
			$base_size_value = $base_size_value / 16;
		}

		// How can we offer control over this?
		// Maybe typography.fluid.{min|max}FontSizeFactor.
		// Or picking the first and last sizes in the fontSizes array?
		// Another option is here to accept fontSizes[0]['min'] and fontSizes[0]['max'] from a preset item.
		$minimum_font_size = $base_size_value * 0.9;
		$maximum_font_size = $base_size_value * 1.75;
		$factor            = ( 1 / ( $maximum_viewport_width - $minimum_viewport_width ) ) * ( $maximum_font_size - $minimum_font_size );
		$calc_rem          = $minimum_font_size - ( $minimum_viewport_width * $factor );
		$calc_vw           = 100 * $factor;
		$min               = min( $minimum_font_size, $maximum_font_size );
		$max               = max( $minimum_font_size, $maximum_font_size );

		return "clamp({$min}rem, {$calc_rem}rem + {$calc_vw}vw, {$max}rem)";
	} else {
		return $preset['size'];
	}
}

// Register the block support.
WP_Block_Supports::get_instance()->register(
	'typography',
	array(
		'register_attribute' => 'gutenberg_register_typography_support',
		'apply'              => 'gutenberg_apply_typography_support',
	)
);
