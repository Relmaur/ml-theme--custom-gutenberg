<?php

declare(strict_types=1);

namespace RigidHybrid\Setup;

use RigidHybrid\Core\Bootable;

/**
 * Class ThemeMode
 *
 * Decides whether this install runs in "builder" mode (clients compose pages
 * freely) or "rigid" mode (clients only edit content inside layouts the
 * developer arranged). See docs/adr/0005-opt-in-rigid-mode.md.
 *
 * The mode is chosen per install in wp-config.php:
 *     define('RIGID_HYBRID_MODE', 'rigid');
 *
 * @package RigidHybrid\Setup
 */
final class ThemeMode implements Bootable
{
    /** Default mode: an install that sets nothing gets the open block builder. */
    public const BUILDER = 'builder';

    /** Opt-in mode: content-only editing for clients. */
    public const RIGID = 'rigid';

    /**
     * Every block this theme ships is registered under this namespace
     * (see block.json "name"). In rigid mode only these may be inserted.
     */
    private const BLOCK_NAMESPACE = 'my-theme/';

    /**
     * Who is allowed to arrange layouts in rigid mode. 'edit_theme_options'
     * belongs to Administrators only, so Editors/Authors get a locked layout.
     */
    private const LAYOUT_CAPABILITY = 'edit_theme_options';

    /**
     * The layout a brand-new post starts with in rigid mode. Without it a
     * locked-down Editor would open an empty page they can't add blocks to.
     */
    private const DEFAULT_LAYOUT = [
        ['my-theme/hero'],
    ];

    /** The resolved mode for this request (one of the constants above). */
    private string $mode;

    public function __construct()
    {
        $this->mode = $this->resolveMode();
    }

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        // Both modes adjust theme.json, just in opposite directions.
        add_filter('wp_theme_json_data_theme', [$this, 'applyDesignSettings']);

        // Builder mode stops here: no block or layout restrictions at all.
        if ($this->mode !== self::RIGID) {
            return;
        }

        add_filter('allowed_block_types_all', [$this, 'limitToThemeBlocks'], 10, 2);
        add_filter('block_editor_settings_all', [$this, 'lockLayout'], 10, 2);
    }

    /**
     * Merge mode-specific settings on top of the theme's theme.json.
     *
     * These can't live in theme.json itself: WordPress expands
     * "appearanceTools": true into individual settings at load time, so a
     * later merge could never switch them back off.
     *
     * @param \WP_Theme_JSON_Data $theme_json Theme-origin theme.json data.
     * @return \WP_Theme_JSON_Data
     */
    public function applyDesignSettings(\WP_Theme_JSON_Data $theme_json): \WP_Theme_JSON_Data
    {
        if ($this->mode === self::BUILDER) {
            // Turn on all the design panels (spacing, borders, etc.) for page builders.
            return $theme_json->update_with([
                'version'  => 3,
                'settings' => ['appearanceTools' => true],
            ]);
        }

        // Rigid: WP core defaults allow custom colors/sizes, so each one must be
        // switched off explicitly. Clients may only pick from the brand palette.
        return $theme_json->update_with([
            'version'  => 3,
            'settings' => [
                'color'      => ['custom' => false],
                'typography' => [
                    'customFontSize' => false,
                    'dropCap'        => false,
                ],
            ],
        ]);
    }

    /**
     * In rigid mode, the inserter only offers this theme's own blocks.
     *
     * @param bool|string[]            $allowed_block_types true = all blocks allowed.
     * @param \WP_Block_Editor_Context $context             Where the editor is running.
     * @return bool|string[]
     */
    public function limitToThemeBlocks($allowed_block_types, \WP_Block_Editor_Context $context)
    {
        // Only restrict the post editor. Other editors (widgets, site editor)
        // have no post and aren't part of the client editing flow.
        if (! $context->post instanceof \WP_Post) {
            return $allowed_block_types;
        }

        // Read the list from the registry instead of hardcoding it, so every
        // new block registered in BlockRegistry is allowed automatically.
        $theme_blocks = [];
        $registered   = \WP_Block_Type_Registry::get_instance()->get_all_registered();

        foreach (array_keys($registered) as $block_name) {
            if (strpos($block_name, self::BLOCK_NAMESPACE) === 0) {
                $theme_blocks[] = $block_name;
            }
        }

        return $theme_blocks;
    }

    /**
     * Lock the layout for anyone who isn't allowed to arrange pages, and give
     * brand-new posts a starting layout.
     *
     * @param array<string, mixed>     $settings Block editor settings.
     * @param \WP_Block_Editor_Context $context  Where the editor is running.
     * @return array<string, mixed>
     */
    public function lockLayout(array $settings, \WP_Block_Editor_Context $context): array
    {
        if (! $context->post instanceof \WP_Post) {
            return $settings;
        }

        // Only NEW posts get the default layout. If we set a template on
        // existing posts, Gutenberg would compare each page against it and
        // offer to "reset" pages whose layout an Administrator changed.
        if ($context->post->post_status === 'auto-draft') {
            $settings['template'] = self::DEFAULT_LAYOUT;
        }

        // 'all' = no inserting, moving or removing blocks. Editing text,
        // images and sidebar fields inside the existing blocks still works.
        if (! current_user_can(self::LAYOUT_CAPABILITY)) {
            $settings['templateLock'] = 'all';
        }

        return $settings;
    }

    /**
     * Read the configured mode and validate it against an allow-list.
     *
     * The value comes from a constant AND a filter, so treat it as untrusted:
     * anything that isn't exactly 'builder' or 'rigid' is rejected.
     *
     * @return string
     */
    private function resolveMode(): string
    {
        // constant() instead of a bare RIGID_HYBRID_MODE: the constant lives in
        // wp-config.php, outside this codebase, so static analysers can't see it.
        $mode = defined('RIGID_HYBRID_MODE') ? constant('RIGID_HYBRID_MODE') : self::BUILDER;

        /**
         * Escape hatch for child themes / mu-plugins (e.g. force rigid on staging).
         *
         * @param mixed $mode Expected 'builder' or 'rigid'; anything else is rejected below.
         */
        $mode = apply_filters('rigid_hybrid/mode', $mode);

        if (in_array($mode, [self::BUILDER, self::RIGID], true)) {
            return $mode;
        }

        // A typo in wp-config shouldn't break the editor. Fall back to the
        // documented default, but say so loudly when WP_DEBUG is on.
        _doing_it_wrong(
            __METHOD__,
            esc_html(sprintf(
                // No quotes around values: esc_html() would turn them into &quot; in logs.
                'Unknown RIGID_HYBRID_MODE: %s. Falling back to %s.',
                is_string($mode) ? $mode : gettype($mode),
                self::BUILDER
            )),
            '1.0.0'
        );

        return self::BUILDER;
    }
}
