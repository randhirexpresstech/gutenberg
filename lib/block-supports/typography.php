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
 * Checks a string for a px or rem value and returns an array consisting of `'value'` and `'unit'`, e.g., [ '42', 'rem' ].
 *
 * @param string $raw_value      Raw size value from theme.json.
 * @param string $preferred_unit Whether to coerce the value to rem or px. Default `'rem'`.
 * @return array                 An array consisting of `'value'` and `'unit'`, e.g., [ '42', 'rem' ]
 */
function gutenberg_get_typography_value_and_unit( $raw_value, $preferred_unit = 'rem' ) {
	$pattern = '/^(\d*\.?\d+)(rem|px){1,1}$/';

	preg_match( $pattern, $raw_value, $matches );

	// We need a number value and a px or rem unit.
	if ( ! isset( $matches[1] ) && isset( $matches[2] ) ) {
		return null;
	}

	$value = $matches[1];
	$unit  = $matches[2];

	if ( 'rem' === $preferred_unit && 'px' === $unit ) {
		// Preferred is rem so we convert px to rem.
		$value = $value / 16;
	}

	if ( 'px' === $preferred_unit && 'rem' === $unit ) {
		// Preferred is px so we convert rem to px.
		$value = $value * 16;
	}

	return array(
		'value' => $value,
		'unit'  => $unit,
	);
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
	if ( ! isset( $typography_settings['responsive'] ) ) {
		return $preset['size'];
	}

	$responsive_settings = $typography_settings['responsive'];

	// Defaults.
	// Up for discussion.
	// We expect these to be in `px`.
	$default_minimum_viewport_width   = '1600px';
	$default_maximum_viewport_width   = '650px';
	$default_minimum_font_size_factor = 0.75;
	$default_maximum_font_size_factor = 1.5;

	// Font sizes.
	$preferred_size = gutenberg_get_typography_value_and_unit( $preset['size'] );

	if ( empty( $preferred_size ) ) {
		return $preset['size'];
	}

	$preferred_unit        = $preferred_size['unit'];
	$maximum_font_size_raw = isset( $preset['max'] ) ? $preset['max'] : $preferred_size['value'] * $default_minimum_font_size_factor . $preferred_unit;
	$minimum_font_size_raw = isset( $preset['min'] ) ? $preset['min'] : $preferred_size['value'] * $default_maximum_font_size_factor . $preferred_unit;
	$maximum_font_size     = gutenberg_get_typography_value_and_unit( $maximum_font_size_raw, $preferred_unit )['value'];
	$minimum_font_size     = gutenberg_get_typography_value_and_unit( $minimum_font_size_raw, $preferred_unit )['value'];

	// Viewport widths.
	// Could we also take these from layout? contentSize and wideSize?
	$maximum_viewport_width_raw = isset( $responsive_settings['maxViewportWidth'] ) ? $responsive_settings['maxViewportWidth'] : $default_maximum_viewport_width;
	$minimum_viewport_width_raw = isset( $responsive_settings['minViewportWidth'] ) ? $responsive_settings['minViewportWidth'] : $default_minimum_viewport_width;
	$maximum_viewport_width     = gutenberg_get_typography_value_and_unit( $maximum_viewport_width_raw, $preferred_unit )['value'];
	$minimum_viewport_width     = gutenberg_get_typography_value_and_unit( $minimum_viewport_width_raw, $preferred_unit )['value'];

	// Calculate fluid font size.
	$rise               = $maximum_font_size - $minimum_font_size;
	$run                = $maximum_viewport_width - $minimum_viewport_width;
	$slope              = $rise / $run;
	$max_font_size_calc = "calc($maximum_font_size * 1{$preferred_unit})";
	$min_font_size_calc = "calc($minimum_font_size * 1{$preferred_unit})";
	$fluid_font_size    = "calc($slope * (100vw - calc($minimum_viewport_width * 1{$preferred_unit})) + $min_font_size_calc)";

	return "clamp($min_font_size_calc, $fluid_font_size, $max_font_size_calc);";
}

// Register the block support.
WP_Block_Supports::get_instance()->register(
	'typography',
	array(
		'register_attribute' => 'gutenberg_register_typography_support',
		'apply'              => 'gutenberg_apply_typography_support',
	)
);
