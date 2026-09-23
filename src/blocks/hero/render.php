<?php

/**
 * Hero Block - Server-side Rendering
 *
 * This file renders the block on the frontend.
 * Available variables:
 * - $attributes (array) - Block attributes from the editor
 * - $content (string) - Inner blocks content (if any)
 * - $block (WP_Block) - Block instance
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/#render
 *
 * @var array<string, mixed> $attributes Injected by WordPress when it includes this file.
 */

// Attributes come from post content, which anyone who can edit the post (or
// the REST API) controls. Check each type instead of trusting block.json.
$title = is_string($attributes['title'] ?? null) ? $attributes['title'] : '';
$subtitle = is_string($attributes['subtitle'] ?? null) ? $attributes['subtitle'] : '';
$image_url = is_string($attributes['imageUrl'] ?? null) ? $attributes['imageUrl'] : '';

// Decide on the ESCAPED url: esc_url() returns '' for unsafe schemes like
// javascript: or data:, and we don't want to print <img src="">.
$has_image = esc_url($image_url) !== '';
$image_alt = wp_strip_all_tags($title);

// Get block wrapper attributes (includes className, align, color support, etc.).
// The returned string is already escaped by WordPress, so it's echoed as-is below.
$wrapper_attributes = get_block_wrapper_attributes(['class' => 'hero-section']);
?>

<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <div class="section-container">
        <div class="text-col">
            <?php if ($title) : ?>
                <h1><?php echo wp_kses_post($title); ?></h1>
            <?php endif; ?>

            <?php if ($subtitle) : ?>
                <p class="subtitle"><?php echo wp_kses_post($subtitle); ?></p>
            <?php endif; ?>
        </div>
        <div class="img-col">
            <?php if ($has_image) : ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>">
            <?php endif; ?>
        </div>
    </div>
</section>