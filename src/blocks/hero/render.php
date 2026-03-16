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
 */

$title = $attributes['title'] ?? '';
$subtitle = $attributes['subtitle'] ?? '';
$image_url = $attributes['imageUrl'] ?? '';

// Get block wrapper attributes (includes className, align, color support, etc.)
$wrapper_attributes = get_block_wrapper_attributes(['class' => 'hero-section']);
?>

<section <?php echo $wrapper_attributes; ?>>
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
            <?php if ($image_url) : ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr(wp_strip_all_tags($title)); ?>">
            <?php endif; ?>
        </div>
    </div>
</section>